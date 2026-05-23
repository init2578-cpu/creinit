<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CivilCertificate;
use App\Enums\CivilCertificateType;
use Carbon\Carbon;

class CivilCertificateRegistryService
{
    /**
     * Generates a unique registry reference based on certificate rules.
     */
    public function generateReference(CivilCertificateType $type, ?string $centerCode = 'DEF'): string
    {
        $year = Carbon::now()->year;
        
        $prefix = match($type) {
            CivilCertificateType::RESIDENCE => 'RES-' . $year,
            CivilCertificateType::COUTUME => 'COU-' . $centerCode . '-' . $year,
            CivilCertificateType::INDIGENCE => 'IND-' . $year,
            CivilCertificateType::INDIVIDUALITE => 'IDV-' . $centerCode . '-' . $year,
            CivilCertificateType::VIE_COLLECTIVE => 'VCL-' . $year,
            CivilCertificateType::VIE_INDIVIDUEL => 'VIL-' . $centerCode . '-' . $year,
            CivilCertificateType::NON_INSCRIT_NAISSANCE => 'NIN-' . $year,
            CivilCertificateType::ACTE_NON_INEXISTANT => 'ANI-' . Carbon::now()->format('YmdHis'),
        };

        // Find the latest certificate with this prefix to increment.
        // We use like "$prefix-%"
        $lastCert = CivilCertificate::where('reference_number', 'like', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastCert) {
            $parts = explode('-', $lastCert->reference_number);
            $lastSeq = (int) end($parts);
            $sequence = $lastSeq + 1;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}
