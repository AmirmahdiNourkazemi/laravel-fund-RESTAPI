<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'string|required',
            'description' => 'string|nullable',
            'short_description' => 'string|nullable',
            'type' => 'integer|required',
            'min_invest' => 'integer|required',
            'fund_needed' => 'integer|required',
            'expected_profit' => 'numeric|required',
            'profit' => 'numeric|required',
            'start_at' => 'date|required',
            'finish_at' => 'date|required',
            'priority' => 'int',
            'properties' => 'array|nullable',
            'properties.*.key' => 'required|string',
            'properties.*.value' => 'required|string',
            'time_table' => 'array|nullable',
            'time_table.*.title' => 'required|string',
            'time_table.*.date' => 'date|string',
        ]);

        $project = Project::create($data);
        return $project;
    }

    public function index(Request $request)
    {
        $projects = Project::orderBy('priority')->get();
        return $projects;
    }
    public function getComments(Request $request, $uuid)
    {
        $data = $request->validate([
            'per_page' => 'integer',
        ]);

        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        return $project->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies' => fn ($q) => $q->where('verified', true)])
            ->where('verified', true)
            ->paginate($data['per_page'] ?? 30);
    }
    public function show($uuid)
    {
        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        return $project;
    }
    public function update(Request $request, $uuid)
    {
        $data = $request->validate([
            'title' => 'string|nullable',
            'description' => 'string|nullable',
            'short_description' => 'string|nullable',
            'type' => 'integer|nullable',
            'min_invest' => 'integer|nullable',
            'fund_needed' => 'integer|nullable',
            'expected_profit' => 'numeric|nullable',
            'profit' => 'numeric|nullable',
            'start_at' => 'date|nullable',
            'finish_at' => 'date|nullable',
            'priority' => 'int',
            'properties' => 'array|nullable',
            'properties.*.key' => 'required|string',
            'properties.*.value' => 'required|string',
            'time_table' => 'array|nullable',
            'time_table.*.title' => 'required|string',
            'time_table.*.date' => 'date|string',
        ]);
        $user = auth()->user();
        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }
        $project->update($data);
        return $project;
    }


    public function uploadMedia(Request $request, $uuid)
    {
        $data = $request->validate([
            'collection' => 'string|required',
            'name' => 'string',
            'file' => 'file|required',
        ]);
        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        $path = $request->file('file')->store('temps', 'public');

        $media = $project->addMediaFromDisk($path, 'public')
            ->usingName($data['name'] ?? '')
            ->toMediaCollection($data['collection']);

        return response()->json([
            'media' => $media
        ]);


    }

    public function storeComment(Request $request, $uuid)
    {
        $data = $request->validate([
            'body' => 'string|required',
            'parent_id' => 'integer|exists:comments,id|nullable',
        ]);

        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        $project->comments()->create([
            'user_id' => auth()->user()->id,
            'body' => $data['body'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'success'
        ]);
    }  
    public function forceDelete($uuid)
    {
        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        $project->forceDelete();

        return $project;
    }

    public function deleteMedia(Request $request, $uuid, $mediaUuid)
    {
        if (!$project = Project::where('uuid', $uuid)->first()) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        $project->media->where('uuid', $mediaUuid)->first()->delete();

        return response()->json([
            'message' => 'media deleted'
        ]);
    }

}
