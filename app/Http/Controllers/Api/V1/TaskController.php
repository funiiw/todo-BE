<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $task = $user->tasks()->get();
        return response()->json($task);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $plan = $user->plan;
        // Validasi jumlah task
        if ($plan && $plan->task_limit > 0 && $user->tasks()->count() >= $plan->task_limit) {
            return response()->json([
                'message' => 'You have reached the maximum number of tasks allowed for your plan.'
            ], 429); // Too Many Requests
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'nullable|string',
            'image' => 'nullable|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task = $user->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'video' => $request->video ?? null,
        ]);

        $image = $request->file('image');
        if ($image) {
            $imagePath = $user->email . '/tasks/' . $task->title;
            Storage::disk('public')->put($imagePath, $image->getContent());
            $imagePath = Storage::url($imagePath);
            $task->image = $imagePath;
            $task->save();
        }

        $data = $task;
        $data['image'] = $task->image == null ? null : asset($task->image);
        return response()->json($data, 201); // 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();
        $task = $user->tasks()->findOrFail($id);
        if(auth()->id() !== $task->user_id){
            return response()->json([
                'message'=> 'Unauthorized'
            ],403);
        }

        $task->load('substasks');
        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Pastikan task ini milik pengguna yang login
        $user = auth()->user();
        $task = $user->tasks()->findOrFail($id);
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'video' => 'sometimes|nullable|string',
            'image' => 'sometimes|nullable|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task->update($request->only(['title', 'description', 'video']));
        $image = $request->file('image');

        if ($image) {
            $imagePath = $user->email . '/tasks/' . $task->title;
            Storage::disk('public')->put($imagePath, $image->getContent());
            $imagePath = Storage::url($imagePath);
            $task->image = $imagePath;
            $task->save();
        }

        $data = $task;
        $data['image'] = $task->image == null ? null : asset($task->image);
        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //pastikan task ini milik pengguna yang login
        $user = auth()->user();
        $task = $user->tasks()->findOrFail($id);
        if(auth()-> id() !== $task->user_id){
            return response()->json([
                'message'=>
                    'Unauthorized'
            ],403);
        }

        $task->deleted();
        return response()->json(['message'=> 'Task deleted successfully']);
    }
}
