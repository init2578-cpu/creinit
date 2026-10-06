<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExerciseSubmission;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\CertificateIssuedNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminCertificateController extends Controller
{
    /**
     * Display a listing of students and closed groups for certificate management.
     */
    public function index(): Response
    {
        $modules = Module::with(['chapters'])->get();

        // 1. Groups that are closed and need or have certificates validated
        $closedGroups = Group::where('status', 'closed')
            ->with([
                'module.chapters',
                'formateur',
                'students.application',
                'students.certificates',
            ])
            ->latest()
            ->get()
            ->map(function ($group) {
                $module = $group->module;
                $studentsList = $group->students->map(function ($student) use ($group, $module) {
                    $score = $this->calculateLearnerGrade($student, $module, $group);
                    $suggestedType = $this->determineCertificateType($score);

                    // Existing certificate for this module
                    $cert = $student->certificates->firstWhere('module_id', $module?->id);
                    $hasCert = $cert && $cert->pdf_path && Storage::disk('local')->exists($cert->pdf_path);

                    // Attendance rate in this group
                    $attendances = Attendance::where('group_id', $group->id)
                        ->where('user_id', $student->id)
                        ->get();
                    $presences = $attendances->where('status', 'present')->count();
                    $totalRecorded = $attendances->whereIn('status', ['present', 'absent_non_justifie', 'justifie', 'late', 'en_retard'])->count();
                    $rate = $totalRecorded > 0 ? (float) round(($presences / $totalRecorded) * 100, 1) : 100.0;

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                        'telephone' => $student->telephone,
                        'profile_photo_url' => $student->profile_photo_url,
                        'score' => $score,
                        'suggested_type' => $suggestedType,
                        'attendance_rate' => $rate,
                        'has_certificate' => (bool) $hasCert,
                        'certificate' => $cert ? [
                            'id' => $cert->id,
                            'uuid' => $cert->uuid,
                            'type' => $cert->type,
                            'score' => $cert->score,
                            'issued_at' => $cert->issued_at?->format('d/m/Y'),
                            'pdf_path' => $cert->pdf_path,
                        ] : null,
                    ];
                });

                $totalCount = $studentsList->count();
                $certifiedCount = $studentsList->where('has_certificate', true)->count();
                $pendingCount = $totalCount - $certifiedCount;

                return [
                    'id' => $group->id,
                    'nom_groupe' => $group->nom_groupe,
                    'annee_academique' => $group->annee_academique,
                    'module_id' => $group->module_id,
                    'module_title' => $module?->titre ?? 'N/A',
                    'formateur_name' => $group->formateur?->name ?? 'N/A',
                    'students_count' => $totalCount,
                    'certified_count' => $certifiedCount,
                    'pending_count' => $pendingCount,
                    'students' => $studentsList->values(),
                ];
            });

        // 2. Global student listing with module progress & closed group flags
        $students = User::role('Apprenant')
            ->with(['certificates', 'studentGroups.module'])
            ->get()
            ->map(function ($student) use ($modules) {
                $student->progress = $modules->map(function ($module) use ($student) {
                    $group = $student->studentGroups->where('module_id', $module->id)->first();

                    if (!$group) {
                        return null;
                    }

                    $totalChapters = $module->chapters->count();
                    $approvedAtGroupLevel = \App\Models\ChapterGroupProgress::where('group_id', $group->id)
                        ->where('status', 'approved')
                        ->whereIn('chapter_id', $module->chapters->pluck('id'))
                        ->pluck('chapter_id')->toArray();

                    $completedCount = $module->chapters->filter(function ($chapter) use ($student, $approvedAtGroupLevel) {
                        if (in_array($chapter->id, $approvedAtGroupLevel, true)) {
                            return true;
                        }

                        if (in_array($chapter->exercise_type, ['online', 'file'], true)) {
                            return ExerciseSubmission::where('chapter_id', $chapter->id)
                                ->where('user_id', $student->id)
                                ->where('status', 'graded')
                                ->exists();
                        }

                        return false;
                    })->count();

                    $cert = $student->certificates->where('module_id', $module->id)->first();
                    $hasCert = $cert && $cert->pdf_path && Storage::disk('local')->exists($cert->pdf_path);

                    $score = $this->calculateLearnerGrade($student, $module, $group);
                    $suggestedType = $this->determineCertificateType($score);

                    return [
                        'module_id' => $module->id,
                        'module_title' => $module->titre,
                        'group_id' => $group->id,
                        'group_name' => $group->nom_groupe,
                        'group_status' => $group->status,
                        'is_group_closed' => $group->status === 'closed',
                        'score' => $score,
                        'suggested_type' => $suggestedType,
                        'completed' => ($totalChapters > 0 && $completedCount === $totalChapters) || $group->status === 'closed',
                        'total_chapters' => $totalChapters,
                        'completed_count' => $completedCount,
                        'progress_pct' => $totalChapters > 0 ? (int) round(($completedCount / $totalChapters) * 100) : 0,
                        'has_certificate' => (bool) $hasCert,
                        'certificate' => $cert ? [
                            'id' => $cert->id,
                            'uuid' => $cert->uuid,
                            'type' => $cert->type,
                            'score' => $cert->score,
                            'issued_at' => $cert->issued_at?->format('d/m/Y'),
                            'pdf_path' => $cert->pdf_path,
                        ] : null,
                    ];
                })->filter()->values();

                return $student;
            });

        // 3. Stats for high-level dashboard
        $stats = [
            'total_students' => $students->count(),
            'total_certificates' => Certificate::count(),
            'total_reussite' => Certificate::where('type', 'reussite')->count(),
            'total_participation' => Certificate::where('type', 'participation')->count(),
            'closed_groups_count' => $closedGroups->count(),
            'closed_groups_pending_count' => $closedGroups->where('pending_count', '>', 0)->count(),
        ];

        return Inertia::render('Scolarite/CertificatesIndex', [
            'students' => $students,
            'modules' => $modules,
            'closedGroups' => $closedGroups,
            'stats' => $stats,
        ]);
    }

    /**
     * Generate or regenerate a certificate for a student and module.
     */
    public function generate(Request $request, User $student, Module $module): RedirectResponse
    {
        $group = null;
        if ($request->filled('group_id')) {
            $group = Group::find($request->input('group_id'));
        }
        if (!$group) {
            $group = $student->studentGroups()->where('module_id', $module->id)->first();
        }

        $calculatedScore = $this->calculateLearnerGrade($student, $module, $group);
        $score = $request->filled('score') ? (float) $request->input('score') : $calculatedScore;

        $type = $request->input('type');
        if (!in_array($type, ['reussite', 'participation'], true)) {
            $type = $this->determineCertificateType($score);
        }

        $certificate = Certificate::updateOrCreate(
            [
                'user_id'   => $student->id,
                'module_id' => $module->id,
            ],
            [
                'group_id'  => $group?->id,
                'type'      => $type,
                'score'     => $score,
                'issued_at' => now(),
            ]
        );

        $this->renderAndStorePdf($certificate, $student, $module, $group, $type, $score);

        $student->notify(new CertificateIssuedNotification($certificate, $module));

        $label = $type === 'participation' ? 'de participation' : 'de réussite';
        return back()->with('success', "Attestation {$label} générée et transmise à l'apprenant avec succès.");
    }

    /**
     * Generate certificates in batch for all students of a closed group.
     */
    public function generateForGroup(Request $request, Group $group): RedirectResponse
    {
        $module = $group->module;
        if (!$module) {
            return back()->withErrors(['group' => 'Ce groupe n\'a pas de module associé.']);
        }

        $students = $group->students;
        if ($students->isEmpty()) {
            return back()->withErrors(['group' => 'Ce groupe ne contient aucun apprenant.']);
        }

        $reussiteCount = 0;
        $participationCount = 0;

        foreach ($students as $student) {
            $score = $this->calculateLearnerGrade($student, $module, $group);
            $type = $this->determineCertificateType($score);

            $certificate = Certificate::updateOrCreate(
                [
                    'user_id'   => $student->id,
                    'module_id' => $module->id,
                ],
                [
                    'group_id'  => $group->id,
                    'type'      => $type,
                    'score'     => $score,
                    'issued_at' => now(),
                ]
            );

            $this->renderAndStorePdf($certificate, $student, $module, $group, $type, $score);
            $student->notify(new CertificateIssuedNotification($certificate, $module));

            if ($type === 'reussite') {
                $reussiteCount++;
            } else {
                $participationCount++;
            }
        }

        $totalGenerated = $reussiteCount + $participationCount;
        return back()->with(
            'success',
            "{$totalGenerated} attestations générées avec succès pour le groupe « {$group->nom_groupe} » ({$reussiteCount} de réussite, {$participationCount} de participation)."
        );
    }

    /**
     * Delete a certificate.
     */
    public function destroy(Certificate $certificate): RedirectResponse
    {
        if ($certificate->pdf_path) {
            Storage::disk('local')->delete($certificate->pdf_path);
        }

        $certificate->delete();

        return back()->with('success', 'Attestation supprimée.');
    }

    /**
     * Helper to compute a learner's grade average for a module on 20.
     */
    public function calculateLearnerGrade(User $student, ?Module $module, ?Group $group = null): ?float
    {
        if (!$module) {
            return null;
        }

        // 1. Primary: non-practice exams for this module
        $examsQuery = Exam::where('module_id', $module->id)
            ->where('is_practice', false);

        if ($group) {
            $examsQuery->where(function ($q) use ($group) {
                $q->whereDoesntHave('groups')
                  ->orWhereHas('groups', fn($g) => $g->where('groups.id', $group->id));
            });
        }

        $examIds = $examsQuery->pluck('id');

        $examResults = ExamResult::where('user_id', $student->id)
            ->whereIn('exam_id', $examIds)
            ->whereNotNull('score')
            ->get();

        if ($examResults->count() > 0) {
            return round((float) $examResults->avg('score'), 2);
        }

        // 2. Secondary: graded chapter exercise submissions
        $chapterIds = $module->chapters->pluck('id');
        $exerciseSubmissions = ExerciseSubmission::with('chapter')
            ->where('user_id', $student->id)
            ->whereIn('chapter_id', $chapterIds)
            ->where('status', 'graded')
            ->whereNotNull('grade')
            ->get();

        if ($exerciseSubmissions->count() > 0) {
            $sum = 0;
            $count = 0;
            foreach ($exerciseSubmissions as $sub) {
                $max = $sub->chapter?->exercise_points ?? 20;
                if ($max > 0) {
                    $sum += ((float) $sub->grade / $max) * 20;
                    $count++;
                }
            }
            if ($count > 0) {
                return round($sum / $count, 2);
            }
        }

        return null;
    }

    /**
     * Determine certificate type based on average score (>= 10 = reussite, < 10 = participation).
     */
    public function determineCertificateType(?float $score): string
    {
        if ($score !== null) {
            return $score >= 10.0 ? 'reussite' : 'participation';
        }

        return 'reussite'; // Default fallback when no numerical exam is configured
    }

    /**
     * Render and store the official certificate PDF.
     */
    private function renderAndStorePdf(
        Certificate $certificate,
        User $student,
        Module $module,
        ?Group $group,
        string $type,
        ?float $score
    ): void {
        $verifyUrl = route('certificates.verify', $certificate->uuid);
        $qrCode = base64_encode((string) QrCode::format('svg')
            ->size(200)
            ->errorCorrection('H')
            ->generate($verifyUrl));

        $application = $student->application;
        $dateNaissance = $application?->date_naissance
            ? Carbon::parse($application->date_naissance)->format('d/m/Y')
            : ($student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('d/m/Y') : null);
        $lieuNaissance = $application?->lieu_naissance ?? $student->place_of_birth ?? null;

        $civilite = 'M./Mme';
        if ($application?->sexe === 'M') {
            $civilite = 'M.';
        } elseif ($application?->sexe === 'F') {
            $civilite = 'Mme';
        }

        $anneeAcademique = $group->annee_academique ?? date('Y');

        $firstAttendance = $group ? Attendance::where('group_id', $group->id)->min('date') : null;
        $lastAttendance = $group ? Attendance::where('group_id', $group->id)->max('date') : null;

        $dateDebut = $firstAttendance ? Carbon::parse($firstAttendance)->format('d/m/Y') : null;
        $dateFin = $lastAttendance ? Carbon::parse($lastAttendance)->format('d/m/Y') : null;
        $issuedDate = $certificate->issued_at ? $certificate->issued_at->format('d/m/Y') : date('d/m/Y');

        $pdf = Pdf::loadView('pdf.attestation', [
            'certificate'     => $certificate,
            'student'         => $student,
            'module'          => $module,
            'type'            => $type,
            'score'           => $score,
            'qrCode'          => $qrCode,
            'verifyUrl'       => $verifyUrl,
            'civilite'        => $civilite,
            'dateNaissance'   => $dateNaissance,
            'lieuNaissance'   => $lieuNaissance,
            'dateDebut'       => $dateDebut,
            'dateFin'         => $dateFin,
            'anneeAcademique' => $anneeAcademique,
            'issuedDate'      => $issuedDate,
        ])->setPaper('a4', 'landscape');

        $path = "certificates/attestation-{$certificate->uuid}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $certificate->update(['pdf_path' => $path]);
    }
}
