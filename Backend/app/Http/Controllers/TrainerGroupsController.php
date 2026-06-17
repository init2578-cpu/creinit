<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\ChapterGroupProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainerGroupsController extends Controller
{
    /**
     * Display groups assigned to the trainer with their students.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $trainerIds = [$user->id];
        if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
            $trainerIds[] = $user->internshipRecord->tuteur_id;
        }

        $groups = Group::with(['module', 'students' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email', 'users.adresse', 'users.profile_photo_path')
                  ->with(['application' => function ($appQuery) {
                      $appQuery->select('id', 'user_id', 'niveau_etude', 'etablissement', 'date_naissance', 'lieu_naissance');
                  }])
                  ->with('attendances')
                  ->with('nominations')
                  ->orderBy('users.name');
        }])
        ->whereIn('formateur_id', $trainerIds)
        ->get();

        // Compute absences and progression for each student specific to the group
        foreach ($groups as $group) {
            $totalChapters = $group->module->chapters()->count();
            $completedChapters = ChapterGroupProgress::where('group_id', $group->id)
                ->where('status', 'approved')
                ->count();
            
            $groupProgression = $totalChapters > 0 
                ? (int) round(($completedChapters / $totalChapters) * 100) 
                : 0;

            $totalExercises = $group->module->chapters()->whereNotNull('exercise_type')->count();
            $studentIds = $group->students->pluck('id')->toArray();
            
            $submissionsCounts = \App\Models\ExerciseSubmission::whereIn('user_id', $studentIds)
                ->whereHas('chapter', function($q) use ($group) {
                    $q->where('module_id', $group->module_id);
                })
                ->selectRaw('user_id, count(distinct chapter_id) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id');

            foreach ($group->students as $student) {
                $student->absences_count = $student->attendances
                    ->where('group_id', $group->id)
                    ->whereIn('status', ['absent_non_justifie', 'justifie'])
                    ->count();
                
                $submittedCount = $submissionsCounts[$student->id] ?? 0;
                $student->progression_percentage = $totalExercises > 0 
                    ? (int) round(($submittedCount / $totalExercises) * 100) 
                    : 0;
                
                $student->group_progression = $groupProgression;
                
                // Attach nomination status for this specific group
                $student->active_nomination = $student->nominations
                    ->where('group_id', $group->id)
                    ->sortByDesc('created_at')
                    ->first();

                // Hide collections to keep JSON minimal
                unset($student->attendances);
                unset($student->nominations);
            }
        }

        return Inertia::render('Trainer/MyGroups', [
            'groups' => $groups,
        ]);
    }
}
