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

                // Auto-deduce default group period from attendances or module dates
                $firstAttendance = Attendance::where('group_id', $group->id)->min('date');
                $lastAttendance  = Attendance::where('group_id', $group->id)->max('date');

                $defaultStartDate = $firstAttendance ?? $module?->start_date?->format('Y-m-d');
                $defaultEndDate   = $lastAttendance ?? $module?->end_date?->format('Y-m-d');

                $studentsList = $group->students->map(function ($student) use ($group, $module, $defaultStartDate, $defaultEndDate) {
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
                    $unjustifiedCount = $attendances->where('status', 'absent_non_justifie')->count();
                    $totalRecorded = $attendances->whereIn('status', ['present', 'absent_non_justifie', 'justifie', 'late', 'en_retard'])->count();
                    $rate = $totalRecorded > 0 ? (float) round(($presences / $totalRecorded) * 100, 1) : 100.0;

                    $hasTooManyUnjustifiedAbsences = $unjustifiedCount > 3;
                    $isBlockedWithoutGrade = ($score === null);
                    $requiresDirectorJustification = $hasTooManyUnjustifiedAbsences && ($score !== null);

                    $startDate = $cert?->start_date ? $cert->start_date->format('Y-m-d') : $defaultStartDate;
                    $endDate   = $cert?->end_date ? $cert->end_date->format('Y-m-d') : $defaultEndDate;

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                        'telephone' => $student->telephone,
                        'profile_photo_url' => $student->profile_photo_url,
                        'score' => $score,
                        'suggested_type' => $suggestedType,
                        'attendance_rate' => $rate,
                        'unjustified_absences_count' => $unjustifiedCount,
                        'has_too_many_unjustified_absences' => $hasTooManyUnjustifiedAbsences,
                        'is_blocked_without_grade' => $isBlockedWithoutGrade,
                        'requires_director_justification' => $requiresDirectorJustification,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_date_fr' => $startDate ? Carbon::parse($startDate)->format('d/m/Y') : null,
                        'end_date_fr' => $endDate ? Carbon::parse($endDate)->format('d/m/Y') : null,
                        'has_certificate' => (bool) $hasCert,
                        'certificate' => $cert ? [
                            'id' => $cert->id,
                            'uuid' => $cert->uuid,
                            'type' => $cert->type,
                            'score' => $cert->score,
                            'justification' => $cert->justification,
                            'start_date' => $cert->start_date?->format('Y-m-d'),
                            'end_date' => $cert->end_date?->format('Y-m-d'),
                            'start_date_fr' => $cert->start_date?->format('d/m/Y'),
                            'end_date_fr' => $cert->end_date?->format('d/m/Y'),
                            'issued_at' => $cert->issued_at?->format('d/m/Y'),
                            'pdf_path' => $cert->pdf_path,
                        ] : null,
                    ];
                });

                $totalCount = $studentsList->count();
                $certifiedCount = $studentsList->where('has_certificate', true)->count();
                $pendingCount = $totalCount - $certifiedCount;
                $blockedCount = $studentsList->where('is_blocked_without_grade', true)->where('has_certificate', false)->count();
                $requiresDirectorCount = $studentsList->where('requires_director_justification', true)->where('has_certificate', false)->count();

                return [
                    'id' => $group->id,
                    'nom_groupe' => $group->nom_groupe,
                    'annee_academique' => $group->annee_academique,
                    'module_id' => $group->module_id,
                    'module_title' => $module?->titre ?? 'N/A',
                    'formateur_name' => $group->formateur?->name ?? 'N/A',
                    'default_start_date' => $defaultStartDate,
                    'default_end_date' => $defaultEndDate,
                    'default_start_date_fr' => $defaultStartDate ? Carbon::parse($defaultStartDate)->format('d/m/Y') : null,
                    'default_end_date_fr' => $defaultEndDate ? Carbon::parse($defaultEndDate)->format('d/m/Y') : null,
                    'students_count' => $totalCount,
                    'certified_count' => $certifiedCount,
                    'pending_count' => $pendingCount,
                    'blocked_count' => $blockedCount,
                    'requires_director_count' => $requiresDirectorCount,
                    'students' => $studentsList->values(),
                ];
            });

        // 2. Global student listing with module progress & closed group flags
        $isDirecteur = auth()->user()?->hasRole('Directeur');
        $studentsQuery = User::role('Apprenant')
            ->with(['certificates', 'studentGroups.module', 'particularModules']);

        if (!$isDirecteur) {
            $studentsQuery->where('is_particulier', false);
        }

        $students = $studentsQuery->get()
            ->map(function ($student) use ($modules) {
                $student->progress = $modules->map(function ($module) use ($student) {
                    $group = $student->studentGroups->where('module_id', $module->id)->first();
                    $isParticularModule = $student->is_particulier && $student->particularModules->contains('id', $module->id);

                    if (!$group && !$isParticularModule) {
                        return null;
                    }

                    $totalChapters = $module->chapters->count();
                    $approvedAtGroupLevel = $group ? \App\Models\ChapterGroupProgress::where('group_id', $group->id)
                        ->where('status', 'approved')
                        ->whereIn('chapter_id', $module->chapters->pluck('id'))
                        ->pluck('chapter_id')->toArray() : [];

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

                    $unjustifiedCount = $this->countUnjustifiedAbsences($student, $module, $group);
                    $hasTooManyUnjustifiedAbsences = $unjustifiedCount > 3;
                    $isBlockedWithoutGrade = ($score === null);
                    $requiresDirectorJustification = $hasTooManyUnjustifiedAbsences && ($score !== null);

                    $firstAttendance = $group ? Attendance::where('group_id', $group->id)->min('date') : null;
                    $lastAttendance  = $group ? Attendance::where('group_id', $group->id)->max('date') : null;

                    $defaultStartDate = $firstAttendance ?? $module->start_date?->format('Y-m-d');
                    $defaultEndDate   = $lastAttendance ?? $module->end_date?->format('Y-m-d');

                    $startDate = $cert?->start_date ? $cert->start_date->format('Y-m-d') : $defaultStartDate;
                    $endDate   = $cert?->end_date ? $cert->end_date->format('Y-m-d') : $defaultEndDate;

                    return [
                        'module_id' => $module->id,
                        'module_title' => $module->titre,
                        'group_id' => $group?->id,
                        'group_name' => $group?->nom_groupe ?? 'Suivi Particulier',
                        'group_status' => $group?->status ?? 'particulier',
                        'is_group_closed' => $group ? $group->status === 'closed' : true,
                        'is_particulier' => (bool) $student->is_particulier,
                        'score' => $score,
                        'suggested_type' => $suggestedType,
                        'unjustified_absences_count' => $unjustifiedCount,
                        'has_too_many_unjustified_absences' => $hasTooManyUnjustifiedAbsences,
                        'is_blocked_without_grade' => $isBlockedWithoutGrade,
                        'requires_director_justification' => $requiresDirectorJustification,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_date_fr' => $startDate ? Carbon::parse($startDate)->format('d/m/Y') : null,
                        'end_date_fr' => $endDate ? Carbon::parse($endDate)->format('d/m/Y') : null,
                        'completed' => ($totalChapters > 0 && $completedCount === $totalChapters) || ($group && $group->status === 'closed') || ($student->is_particulier && $score !== null),
                        'total_chapters' => $totalChapters,
                        'completed_count' => $completedCount,
                        'progress_pct' => $totalChapters > 0 ? (int) round(($completedCount / $totalChapters) * 100) : 0,
                        'has_certificate' => (bool) $hasCert,
                        'certificate' => $cert ? [
                            'id' => $cert->id,
                            'uuid' => $cert->uuid,
                            'type' => $cert->type,
                            'score' => $cert->score,
                            'justification' => $cert->justification,
                            'start_date' => $cert->start_date?->format('Y-m-d'),
                            'end_date' => $cert->end_date?->format('Y-m-d'),
                            'start_date_fr' => $cert->start_date?->format('d/m/Y'),
                            'end_date_fr' => $cert->end_date?->format('d/m/Y'),
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
            'isDirecteur' => (bool) $isDirecteur,
        ]);
    }

    /**
     * Generate or regenerate a certificate for a student and module.
     */
    public function generate(Request $request, User $student, Module $module): RedirectResponse
    {
        if (!auth()->user()?->hasRole('Directeur')) {
            abort(403, "Accès refusé : seul le Directeur de l'établissement a l'habilitation d'attester et de délivrer des attestations officielles.");
        }

        $group = null;
        if ($request->filled('group_id')) {
            $group = Group::find($request->input('group_id'));
        }
        if (!$group) {
            $group = $student->studentGroups()->where('module_id', $module->id)->first();
        }

        $calculatedScore = $this->calculateLearnerGrade($student, $module, $group);
        $score = ($request->has('score') && $request->input('score') !== null && $request->input('score') !== '')
            ? (float) $request->input('score')
            : $calculatedScore;

        $unjustifiedAbsences = $this->countUnjustifiedAbsences($student, $module, $group);

        // RULE 1: Tout apprenant n'ayant pas de note n'a pas droit à une attestation
        if ($score === null) {
            return back()->withErrors([
                'score' => "Impossible de valider et générer l'attestation : tout apprenant n'ayant pas de note n'a pas droit à une attestation. Une note doit obligatoirement être enregistrée.",
            ])->with('error', "Génération bloquée : {$student->name} ne dispose d'aucune note.");
        }

        // RULE 2: Even if the learner has a grade, if they have > 3 unjustified absences:
        // Only the Directeur can judge and validate, with written justification required.
        $justification = null;
        if ($unjustifiedAbsences > 3) {
            $justification = trim((string) $request->input('justification'));
            if (empty($justification)) {
                return back()->withErrors([
                    'justification' => "Une justification écrite du Directeur est obligatoire pour autoriser la délivrance de l'attestation d'un apprenant cumulant {$unjustifiedAbsences} absences non justifiées.",
                ])->with('error', "Justification écrite du Directeur obligatoire (plus de 3 absences non justifiées).");
            }
        } else {
            $justification = $request->filled('justification') ? trim((string) $request->input('justification')) : null;
        }

        $type = $request->input('type');
        if (!in_array($type, ['reussite', 'participation'], true)) {
            $type = $this->determineCertificateType($score) ?? 'participation';
        }

        // Custom or auto-deduced training period dates
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        if (!$startDate) {
            $firstAttendance = $group ? Attendance::where('group_id', $group->id)->min('date') : null;
            $startDate = $firstAttendance ?? $module->start_date?->format('Y-m-d');
        }

        if (!$endDate) {
            $lastAttendance = $group ? Attendance::where('group_id', $group->id)->max('date') : null;
            $endDate = $lastAttendance ?? $module->end_date?->format('Y-m-d');
        }

        $certificate = Certificate::updateOrCreate(
            [
                'user_id'   => $student->id,
                'module_id' => $module->id,
            ],
            [
                'group_id'      => $group?->id,
                'type'          => $type,
                'score'         => $score,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'justification' => $justification,
                'issued_at'     => now(),
            ]
        );

        $this->renderAndStorePdf($certificate, $student, $module, $group, $type, $score);

        $student->notify(new CertificateIssuedNotification($certificate, $module));

        $label = $type === 'participation' ? 'de participation' : 'de réussite';
        $successMsg = "Attestation {$label} générée et transmise à l'apprenant avec succès.";
        if ($unjustifiedAbsences > 3) {
            $successMsg .= " (Appréciation dérogatoire du Directeur enregistrée avec justificatif écrit).";
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Generate certificates in batch for all students of a closed group.
     */
    public function generateForGroup(Request $request, Group $group): RedirectResponse
    {
        if (!auth()->user()?->hasRole('Directeur')) {
            abort(403, "Accès refusé : seul le Directeur de l'établissement a l'habilitation d'attester et de délivrer des attestations officielles.");
        }

        $module = $group->module;
        if (!$module) {
            return back()->withErrors(['group' => 'Ce groupe n\'a pas de module associé.']);
        }

        $students = $group->students;
        if ($students->isEmpty()) {
            return back()->withErrors(['group' => 'Ce groupe ne contient aucun apprenant.']);
        }

        // Custom or auto-deduced group dates
        $groupStartDate = $request->input('start_date');
        $groupEndDate   = $request->input('end_date');

        if (!$groupStartDate) {
            $groupStartDate = Attendance::where('group_id', $group->id)->min('date') ?? $module->start_date?->format('Y-m-d');
        }
        if (!$groupEndDate) {
            $groupEndDate = Attendance::where('group_id', $group->id)->max('date') ?? $module->end_date?->format('Y-m-d');
        }

        $reussiteCount = 0;
        $participationCount = 0;
        $skippedWithoutGrade = [];
        $skippedNeedDirector = [];

        foreach ($students as $student) {
            $unjustifiedCount = $this->countUnjustifiedAbsences($student, $module, $group);
            $score = $this->calculateLearnerGrade($student, $module, $group);

            // 1) Tout apprenant n'ayant pas de note n'a pas droit à une attestation
            if ($score === null) {
                $skippedWithoutGrade[] = $student->name;
                continue;
            }

            // 2) Plus de 3 absences non justifiées : appréciation individuelle requise par le Directeur
            if ($unjustifiedCount > 3) {
                $skippedNeedDirector[] = $student->name;
                continue;
            }

            $type = $this->determineCertificateType($score) ?? 'participation';

            $certificate = Certificate::updateOrCreate(
                [
                    'user_id'   => $student->id,
                    'module_id' => $module->id,
                ],
                [
                    'group_id'   => $group->id,
                    'type'       => $type,
                    'score'      => $score,
                    'start_date' => $groupStartDate,
                    'end_date'   => $groupEndDate,
                    'issued_at'  => now(),
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

        if ($totalGenerated === 0 && (!empty($skippedWithoutGrade) || !empty($skippedNeedDirector))) {
            $reasonParts = [];
            if (!empty($skippedWithoutGrade)) {
                $reasonParts[] = count($skippedWithoutGrade) . " apprenant(s) sans note (" . implode(', ', $skippedWithoutGrade) . ") n'ont pas droit à une attestation";
            }
            if (!empty($skippedNeedDirector)) {
                $reasonParts[] = count($skippedNeedDirector) . " apprenant(s) avec plus de 3 absences (" . implode(', ', $skippedNeedDirector) . ") requièrent l'appréciation du Directeur avec justificatif écrit";
            }
            return back()->withErrors([
                'group' => "Aucune attestation n'a été générée. " . implode(' et ', $reasonParts) . ".",
            ])->with('error', "Génération impossible : aucun apprenant n'est éligible (note requise et/ou avis Directeur requis).");
        }

        $msg = "{$totalGenerated} attestations générées avec succès pour le groupe « {$group->nom_groupe} » ({$reussiteCount} de réussite, {$participationCount} de participation).";
        $notes = [];
        if (!empty($skippedWithoutGrade)) {
            $notes[] = count($skippedWithoutGrade) . " apprenant(s) sans note (" . implode(', ', $skippedWithoutGrade) . ")";
        }
        if (!empty($skippedNeedDirector)) {
            $notes[] = count($skippedNeedDirector) . " apprenant(s) à apprécier individuellement par le Directeur avec justificatif écrit (" . implode(', ', $skippedNeedDirector) . ")";
        }
        if (!empty($notes)) {
            $msg .= " Note : " . implode(' et ', $notes) . " ont été exclu(s) de la production groupée.";
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete a certificate.
     */
    public function destroy(Certificate $certificate): RedirectResponse
    {
        if (!auth()->user()?->hasRole('Directeur')) {
            abort(403, "Accès refusé : seul le Directeur de l'établissement a l'habilitation de supprimer des attestations officielles.");
        }

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
        } elseif ($student->is_particulier) {
            $examsQuery->where(function ($q) use ($student) {
                $q->whereHas('particularStudents', fn($ps) => $ps->where('users.id', $student->id));
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
     * Returns null if no score is recorded.
     */
    public function determineCertificateType(?float $score): ?string
    {
        if ($score !== null) {
            return $score >= 10.0 ? 'reussite' : 'participation';
        }

        return null;
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

        $anneeAcademique = $group?->annee_academique ?? date('Y');

        $firstAttendance = $group ? Attendance::where('group_id', $group->id)->min('date') : null;
        $lastAttendance = $group ? Attendance::where('group_id', $group->id)->max('date') : null;

        $dateDebut = $certificate->start_date 
            ? Carbon::parse($certificate->start_date)->format('d/m/Y') 
            : ($firstAttendance ? Carbon::parse($firstAttendance)->format('d/m/Y') : ($module->start_date ? Carbon::parse($module->start_date)->format('d/m/Y') : null));

        $dateFin = $certificate->end_date 
            ? Carbon::parse($certificate->end_date)->format('d/m/Y') 
            : ($lastAttendance ? Carbon::parse($lastAttendance)->format('d/m/Y') : ($module->end_date ? Carbon::parse($module->end_date)->format('d/m/Y') : null));

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

    /**
     * Helper to count unjustified absences for a learner in a group or module.
     */
    public function countUnjustifiedAbsences(User $student, ?Module $module = null, ?Group $group = null): int
    {
        $query = Attendance::where('user_id', $student->id)
            ->where('status', 'absent_non_justifie');

        if ($group) {
            $query->where('group_id', $group->id);
        } elseif ($module) {
            $groupIds = $student->studentGroups()->where('module_id', $module->id)->pluck('groups.id');
            if ($groupIds->isNotEmpty()) {
                $query->whereIn('group_id', $groupIds);
            } else {
                return 0;
            }
        }

        return $query->count();
    }
}
