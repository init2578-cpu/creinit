<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display the list of modules available to the signed-in student.
     */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->hasRole('Directeur') || $user->isTrainer()) {
            $modules = Module::with(['chapters' => function($query) {
                $query->where(fn($sub) => $sub->whereNull('exercise_type')->orWhere('exercise_type', 'none'))->where('is_published', true)->orderBy('ordre');
            }])->get();
            
            // Format to look like the $groups->pluck('module') structure if needed
            // For now, let's just send the modules
            return Inertia::render('Student/Courses', [
                'modules' => $modules,
                'is_admin' => true
            ]);
        }

        $groups = $user->studentGroups()->with(['module.chapters' => function($query) {
            $query->where(fn($sub) => $sub->whereNull('exercise_type')->orWhere('exercise_type', 'none'))->where('is_published', true)->where('is_approved', true)->orderBy('ordre');
        }])->get();

        return Inertia::render('Student/Courses', [
            'modules' => $groups->pluck('module'),
        ]);
    }

    /**
     * Display a specific chapter's content.
     */
    public function showChapter(Module $module, Chapter $chapter): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Security check: Is user enrolled in a group for this module?
        $isEnrolled = $user->studentGroups()->where(function($query) use ($module) {
            $query->where('module_id', $module->id);
        })->exists();
        if (!$isEnrolled && !$user->hasRole('Directeur') && !$user->isTrainer()) {
            abort(403);
        }

        if (!$user->hasRole('Directeur') && !$user->isTrainer() && !$chapter->is_approved) {
            abort(403, 'Ce chapitre n\'a pas encore été validé par la direction.');
        }

        $chapter->load(['module.phases', 'phase']);

        $allChaptersQuery = $module->chapters()->where(fn($sub) => $sub->whereNull('exercise_type')->orWhere('exercise_type', 'none'))->with('phase')->where('is_published', true);
        if (!$user->hasRole('Directeur') && !$user->isTrainer()) {
            $allChaptersQuery->where('is_approved', true);
        }

        return Inertia::render('Student/CoursePlayer', [
            'module' => $module->load('phases'),
            'currentChapter' => $chapter,
            'allChapters' => $allChaptersQuery->orderBy('ordre')->get(),
        ]);
    }
}
