<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\Module;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $moduleId,
        private readonly string $type = 'reussite',
        private readonly ?float $score = null,
        private readonly ?int $groupId = null,
    ) {}

    public function handle(): void
    {
        $user   = User::findOrFail($this->userId);
        $module = Module::findOrFail($this->moduleId);

        // Create or update certificate record
        $certificate = Certificate::updateOrCreate(
            [
                'user_id'   => $user->id,
                'module_id' => $module->id,
            ],
            [
                'issued_at' => now(),
                'type'      => $this->type,
                'score'     => $this->score,
                'group_id'  => $this->groupId,
            ],
        );

        // Generate QR Code pointing to public verification route
        $verificationUrl = url("/verify-certificate/{$certificate->uuid}");
        $qrCode       = base64_encode((string)QrCode::format('svg')
            ->size(200)
            ->errorCorrection('H')
            ->generate($verificationUrl));

        // Prepare dynamic parameters for official CRE Kolda attestation
        $application = $user->application;
        $dateNaissance = $application?->date_naissance 
            ? \Carbon\Carbon::parse($application->date_naissance)->format('d/m/Y') 
            : ($user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d/m/Y') : null);
        $lieuNaissance = $application?->lieu_naissance ?? $user->place_of_birth ?? null;
        
        $civilite = 'M./Mme';
        if ($application?->sexe === 'M') {
            $civilite = 'M.';
        } elseif ($application?->sexe === 'F') {
            $civilite = 'Mme';
        }

        $group = $user->studentGroups()->where('module_id', $module->id)->first();
        $anneeAcademique = $group->annee_academique ?? date('Y');
        
        $firstAttendance = $group ? \App\Models\Attendance::where('group_id', $group->id)->min('date') : null;
        $lastAttendance = $group ? \App\Models\Attendance::where('group_id', $group->id)->max('date') : null;
        
        $dateDebut = $firstAttendance ? \Carbon\Carbon::parse($firstAttendance)->format('d/m/Y') : null;
        $dateFin = $lastAttendance ? \Carbon\Carbon::parse($lastAttendance)->format('d/m/Y') : null;
        $issuedDate = $certificate->issued_at ? $certificate->issued_at->format('d/m/Y') : date('d/m/Y');

        // Generate PDF
        $pdf = Pdf::loadView('pdf.attestation', [
            'student'         => $user,
            'module'          => $module,
            'certificate'     => $certificate,
            'type'            => $certificate->type ?? 'reussite',
            'score'           => $certificate->score,
            'qrCode'          => $qrCode,
            'verifyUrl'       => $verificationUrl,
            'civilite'        => $civilite,
            'dateNaissance'   => $dateNaissance,
            'lieuNaissance'   => $lieuNaissance,
            'dateDebut'       => $dateDebut,
            'dateFin'         => $dateFin,
            'anneeAcademique' => $anneeAcademique,
            'issuedDate'      => $issuedDate,
        ])->setPaper('a4', 'landscape');

        // Store PDF
        $pdfPath = "certificates/{$certificate->uuid}.pdf";
        Storage::disk('local')->put($pdfPath, $pdf->output());

        // Update certificate with PDF path
        $certificate->update(['pdf_path' => $pdfPath]);
    }
}
