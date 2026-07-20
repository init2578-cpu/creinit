<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChapterProgress\ApproveChapterProgressRequest;
use App\Http\Requests\ChapterProgress\SubmitChapterProgressRequest;
use App\Models\ChapterGroupProgress;
use App\Models\Group;
use App\Notifications\ChapterSubmittedNotification;
use App\Notifications\ChapterRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChapterProgressController extends Controller
{
    /**
     * Display groups assigned to the trainer for progression.
     */
    public function groupsIndex(\Illuminate\Http\Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole('Apprenant')) {
            $groups = $user->studentGroups()->with('module')->get();
        } else {
            $trainerIds = [$user->id];
            if ($user->hasRole('Stagiaire') && $user->internshipRecord?->tuteur_id) {
                $trainerIds[] = $user->internshipRecord->tuteur_id;
            }

            $groups = Group::with('module')
                ->whereIn('formateur_id', $trainerIds)
                ->get();
        }

        // Mark related notifications as read
        if ($user) {
            $user->unreadNotifications()
                 ->whereIn('type', [
                     \App\Notifications\ChapterSubmittedNotification::class,
                     \App\Notifications\ChapterRejectedNotification::class,
                 ])
                 ->get()
                 ->markAsRead();
        }

        return Inertia::render('ChapterProgress/GroupsIndex', [
            'groups' => $groups,
        ]);
    }

    /**
     * Display chapter progress for a specific group.
     */
    public function index(Group $group): Response
    {
        $group->load(['module.chapters' => function ($q) {
            $q->where(fn($sub) => $sub->whereNull('exercise_type')->orWhere('exercise_type', 'none'))->orderBy('ordre');
        }, 'formateur', 'responsableGroupe']);

        $progress = ChapterGroupProgress::with(['chapter', 'submitter', 'validator'])
            ->where(fn($query) => $query->where('group_id', $group->id))
            ->get()
            ->keyBy('chapter_id');

        $user = auth()->user();
        $component = 'ChapterProgress/Index';

        if ($user->isTrainer()) {
            $component = 'ChapterProgress/TeacherProgression';
        } elseif ($user->hasRole('Responsable Groupe') || $user->hasRole('Directeur')) {
            $component = 'ChapterProgress/GroupLeaderValidation';
        } elseif ($user->hasRole('Apprenant')) {
            $component = 'ChapterProgress/StudentValidation';
        }

        return Inertia::render($component, [
            'group'    => $group,
            'progress' => $progress,
        ]);
    }


    /**
     * Submit a chapter as completed (Formateur).
     */
    public function submit(SubmitChapterProgressRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $trainer */
        $trainer = auth()->user();
        $groupId = $request->validated('group_id');
        $chapterIds = $request->validated('chapter_ids');
        $submittedCount = 0;

        foreach ($chapterIds as $chapterId) {
            $progress = ChapterGroupProgress::firstOrNew([
                'group_id'   => $groupId,
                'chapter_id' => $chapterId,
            ]);

            if (!$progress->exists || $progress->status === 'rejected') {
                $progress->status = 'pending';
                $progress->submitted_by = $trainer->id;
                $progress->save();

                $progress->load(['group.responsableGroupe', 'chapter', 'submitter']);
                
                // Notify the group supervisor
                $responsable = $progress->group->responsableGroupe;
                if ($responsable) {
                    $responsable->notify(new ChapterSubmittedNotification($progress));
                }
                $submittedCount++;
            }
        }

        return redirect()
            ->back()
            ->with('success', "$submittedCount chapitre(s) soumis pour validation.");
    }

    /**
     * Approve a submitted chapter (Responsable Groupe).
     */
    public function approve(ApproveChapterProgressRequest $request, ChapterGroupProgress $chapterGroupProgress): RedirectResponse
    {
        if ($chapterGroupProgress->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Ce chapitre a déjà été validé.');
        }

        /** @var \App\Models\User $validator */
        $validator = $request->user();

        $chapterGroupProgress->update([
            'status'       => 'approved',
            'validated_by' => $validator->id,
            'validated_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Chapitre validé avec succès.');
    }

    /**
     * Reject a submitted chapter.
     */
    public function reject(ApproveChapterProgressRequest $request, ChapterGroupProgress $chapterGroupProgress): RedirectResponse
    {
        if ($chapterGroupProgress->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Seuls les chapitres en attente peuvent être rejetés.');
        }

        $chapterGroupProgress->update([
            'status'       => 'rejected',
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
        ]);

        $chapterGroupProgress->load(['submitter', 'chapter', 'group']);
        if ($chapterGroupProgress->submitter) {
            $chapterGroupProgress->submitter->notify(new ChapterRejectedNotification($chapterGroupProgress));
        }

        return redirect()
            ->back()
            ->with('success', 'La progression a été rejetée et renvoyée au formateur.');
    }

    /**
     * Cancel a chapter progress validation (Formateur).
     */
    public function cancel(ChapterGroupProgress $chapterGroupProgress): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user->isTrainer()) {
            abort(403, 'Seul le formateur de ce groupe peut annuler cette progression.');
        }

        $chapterGroupProgress->delete();

        return redirect()
            ->back()
            ->with('success', 'La progression a été annulée.');
    }
}
