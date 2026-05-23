<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CivilCertificate;

class CivilCertificatePdfService
{
    /**
     * Generates a PDF for a validated CivilCertificate.
     * In a real application, this would use Snappy or DomPDF.
     * Here, we simulate PDF generation by creating a dummy PDF or text file.
     */
    public function generateFor(CivilCertificate $certificate): string
    {
        // Define directory
        $dir = storage_path('app/public/civil_certificates/' . $certificate->type->value);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $certificate->reference_number . '.pdf';
        $path = $dir . '/' . $filename;

        // Dummy PDF content (Simulating a real PDF for demonstration)
        $content = "Certificat Type: " . $certificate->type->label() . "\n";
        $content .= "Reference: " . $certificate->reference_number . "\n";
        $content .= "Demandeur: " . $certificate->applicant_first_name . ' ' . $certificate->applicant_last_name . "\n";
        $content .= "Validé par: Admin\n";
        
        file_put_contents($path, $content);

        return 'civil_certificates/' . $certificate->type->value . '/' . $filename;
    }
}
