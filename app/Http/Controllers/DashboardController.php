<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Project::where('user_id', auth()->id())
            ->withCount([
                'pages as total_pages',
                'pages as completed_pages' => fn($q) => $q->where('status', 'completed'),
                'pages as generating_pages' => fn($q) => $q->whereIn('status', ['submitted', 'generating', 'queued']),
                'pages as failed_pages' => fn($q) => $q->where('status', 'failed'),
            ])
            ->latest()
            ->get();

        return view('dashboard.index', compact('projects'));
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        // Remove project storage directory safely
        Storage::disk('local')->deleteDirectory("projects/{$project->uuid}");

        $project->delete();

        return redirect()->route('dashboard')->with('success', 'تم حذف المشروع بنجاح.');
    }
}
