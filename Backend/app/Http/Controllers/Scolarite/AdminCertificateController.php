<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminCertificateController extends Controller
{
    /**
     * Display a listing of students by module for certificate management.
     */
    public function index(): Response
    {
        $modules = Module::with(['chapters'])->get();
        
        $students = User::role('Apprenant')
            ->with(['certificates', 'studentGroups'])
            ->get()
            ->map(function ($student) use ($modules) {
                $student->progress = $modules->map(function ($module) use ($student) {
                    // Find the group of this student for this module
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
                        // 1. Is it approved for the whole group?
                        if (in_array($chapter->id, $approvedAtGroupLevel)) {
                            return true;
                        }

                        // 2. Or did the student personally complete and get graded on an exercise?
                        if (in_array($chapter->exercise_type, ['online', 'file'])) {
                            return \App\Models\ExerciseSubmission::where('chapter_id', $chapter->id)
                                ->where('user_id', $student->id)
                                ->where('status', 'graded')
                                ->exists();
                        }

                        return false;
                    })->count();
                    
                    $cert = $student->certificates->where('module_id', $module->id)->first();
                    $hasCert = $cert && $cert->pdf_path && Storage::disk('local')->exists($cert->pdf_path);

                    return [
                        'module_id' => $module->id,
                        'module_title' => $module->titre,
                        'completed' => $totalChapters > 0 && $completedCount === $totalChapters,
                        'total_chapters' => $totalChapters,
                        'completed_count' => $completedCount,
                        'progress_pct' => $totalChapters > 0 ? round(($completedCount / $totalChapters) * 100) : 0,
                        'has_certificate' => $hasCert,
                        'certificate' => $cert,
                    ];
                })->filter()->values();
                return $student;
            });

        return Inertia::render('Scolarite/CertificatesIndex', [
            'students' => $students,
            'modules' => $modules,
        ]);
    }

    /**
     * Generate a certificate for a student and module.
     */
    public function generate(User $student, Module $module)
    {
        // Check if already exists - but allow overwrite for design updates
        $certificate = Certificate::where('user_id', $student->id)
            ->where('module_id', $module->id)
            ->first();

        if (!$certificate) {
            $certificate = Certificate::create([
                'user_id' => $student->id,
                'module_id' => $module->id,
                'issued_at' => now(),
            ]);
        }

        // Generate QR Code
        $verifyUrl = route('certificates.verify', $certificate->uuid);
        $qrCode = base64_encode((string)QrCode::format('svg')
            ->size(200)
            ->errorCorrection('H')
            ->generate($verifyUrl));

        // Prepare dynamic parameters for official CRE Kolda attestation
        $application = $student->application;
        $dateNaissance = $application?->date_naissance 
            ? \Carbon\Carbon::parse($application->date_naissance)->format('d/m/Y') 
            : ($student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : null);
        $lieuNaissance = $application?->lieu_naissance ?? $student->place_of_birth ?? null;
        
        $civilite = 'M./Mme';
        if ($application?->sexe === 'M') {
            $civilite = 'M.';
        } elseif ($application?->sexe === 'F') {
            $civilite = 'Mme';
        }

        $group = $student->studentGroups()->where('module_id', $module->id)->first();
        $anneeAcademique = $group->annee_academique ?? date('Y');
        
        $firstAttendance = $group ? \App\Models\Attendance::where('group_id', $group->id)->min('date') : null;
        $lastAttendance = $group ? \App\Models\Attendance::where('group_id', $group->id)->max('date') : null;
        
        $dateDebut = $firstAttendance ? \Carbon\Carbon::parse($firstAttendance)->format('d/m/Y') : null;
        $dateFin = $lastAttendance ? \Carbon\Carbon::parse($lastAttendance)->format('d/m/Y') : null;
        $issuedDate = $certificate->issued_at ? $certificate->issued_at->format('d/m/Y') : date('d/m/Y');

        // Generate PDF
        $pdf = Pdf::loadView('pdf.attestation', [
            'certificate'     => $certificate,
            'student'         => $student,
            'module'          => $module,
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

        // Notify student of the achievement
        $student->notify(new CertificateIssuedNotification($certificate, $module));

        return back()->with('success', 'Attestation générée et envoyée à l\'apprenant avec succès.');
    }

    /**
     * Delete a certificate.
     */
    public function destroy(Certificate $certificate)
    {
        if ($certificate->pdf_path) {
            Storage::disk('local')->delete($certificate->pdf_path);
        }
        
        $certificate->delete();
        
        return back()->with('success', 'Attestation supprimée.');
    }
}
