<?php

declare(strict_types=1);

namespace App\Enums;

enum CivilCertificateType: string
{
    case RESIDENCE = 'residence';
    case COUTUME = 'coutume';
    case INDIGENCE = 'indigence';
    case INDIVIDUALITE = 'individualite';
    case VIE_COLLECTIVE = 'vie_collective';
    case VIE_INDIVIDUEL = 'vie_individuel';
    case NON_INSCRIT_NAISSANCE = 'non_inscrit_naissance';
    case ACTE_NON_INEXISTANT = 'acte_non_inexistant';

    public function label(): string
    {
        return match($this) {
            self::RESIDENCE => 'Certificat de résidence',
            self::COUTUME => 'Certificat de coutume',
            self::INDIGENCE => 'Certificat d\'indigence',
            self::INDIVIDUALITE => 'Certificat d\'individualité',
            self::VIE_COLLECTIVE => 'Certificat de vie collective',
            self::VIE_INDIVIDUEL => 'Certificat de vie individuel',
            self::NON_INSCRIT_NAISSANCE => 'Certificat de non inscrit de naissance',
            self::ACTE_NON_INEXISTANT => 'Certificat d\'acte non inexistant',
        };
    }
}
