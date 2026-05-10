<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // show profile data
    public function show(Request $request)
    {
        $user = $request->user();
        $user->loadCount('applications');
        $user->load('skills');

        return response()->json([
            'success' => true,
            'data' => [
                'name'               => $user->name,
                'email'              => $user->email,
                'phone'              => $user->phone,
                'linkedin_url'       => $user->linkedin_url,
                'skills'             => $user->skills->pluck('name')->toArray(),
                'applications_count' => $user->applications_count,
                'has_resume'         => (bool) $user->resume_path,
            ],
        ]);
    }

    // update profile
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'linkedin_url' => 'nullable|url',
            'skills'       => 'nullable|array',
            'skills.*'     => 'string|max:100',
            'resume'       => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user->update($request->only('name', 'phone', 'linkedin_url'));

        // Sync skills via the pivot table — find or create each skill by name
        if ($request->has('skills')) {
            $skillIds = collect($request->skills)->map(
                fn($name) => Skill::firstOrCreate(['name' => trim($name)])->id
            );
            $user->skills()->sync($skillIds);
        }

        if ($request->hasFile('resume')) {
            if ($user->resume_path) {
                Storage::disk('private')->delete($user->resume_path);
            }
            $user->resume_path = $request->file('resume')->store('resumes', 'private');
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }

    // download resume from secure disk
    public function downloadResume(Request $request)
    {
        $user = $request->user();

        if (! $user->resume_path || ! Storage::disk('private')->exists($user->resume_path)) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        return Storage::disk('private')->download($user->resume_path, "resume_{$user->name}.pdf");
    }
}