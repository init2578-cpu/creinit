<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CertificateVerificationController extends Controller
{
    /**
     * Public route: verify the authenticity of a certificate.
     * GET /verify-certificate/{uuid}
     */
    public function verify(string $uuid): InertiaResponse
    {
        $certificate = Certificate::with(['user', 'module'])
            ->where('uuid', $uuid)
            ->first();

        if (!$certificate) {
            return Inertia::render('Certificates/VerifyCertificate', [
                'valid'       => false,
                'message'     => 'Attestation introuvable. Ce document n\'est pas authentique.',
                'certificate' => null,
            ]);
        }

        return Inertia::render('Certificates/VerifyCertificate', [
            'valid'       => true,

            'message'     => 'Cette attestation est authentique et vérifiée.',
            'certificate' => [
                'uuid'       => $certificate->uuid,
                'learner'    => $certificate->user->name,
                'module'     => $certificate->module->titre,
                'code_module' => $certificate->module->code_module,
                'type'       => $certificate->type ?? 'reussite',
                'type_label' => ($certificate->type ?? 'reussite') === 'participation' ? 'Attestation de Participation' : 'Attestation de Réussite',
                'score'      => $certificate->score,
                'issued_at'  => $certificate->issued_at->format('d/m/Y'),
                'profile_photo_url' => $certificate->user->profile_photo_url,
            ],
        ]);
    }

    /**
     * Public route: view the high-end digital certificate.
     */
    public function show(string $uuid): InertiaResponse
    {
        $certificate = Certificate::with(['user', 'module'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return Inertia::render('Certificates/ShowCertificate', [
            'certificate' => [
                'id' => $certificate->id,
                'uuid' => $certificate->uuid,
                'type' => $certificate->type ?? 'reussite',
                'type_label' => ($certificate->type ?? 'reussite') === 'participation' ? 'Attestation de Participation' : 'Attestation de Réussite',
                'score' => $certificate->score,
                'issued_at' => $certificate->issued_at->format('Y-m-d'),
                'issued_at_fr' => $certificate->issued_at->format('d/m/Y'),
                'student_name' => $certificate->user->name,
                'module_title' => $certificate->module->titre,
                'module_code' => $certificate->module->code_module,
                'profile_photo_url' => $certificate->user->profile_photo_url,
            ],
            'verify_url' => route('certificates.verify', $certificate->uuid)
        ]);
    }

    /**
     * Download a certificate PDF.
     */
    public function download(Certificate $certificate): Response
    {
        if (!$certificate->pdf_path || !Storage::disk('local')->exists($certificate->pdf_path)) {
            abort(404, 'Le PDF de cette attestation n\'est pas disponible.');
        }

        return response(
            Storage::disk('local')->get($certificate->pdf_path),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"attestation-{$certificate->uuid}.pdf\"",
            ],
        );
    }
}
