<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ATTESTATION - {{ $student->name }}</title>
    <style>
        @page {
            margin: 0;
            size: a4 landscape;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #1e293b;
            width: 297mm;
            height: 210mm;
            position: relative;
        }

        /* Outer Frame & Borders */
        .page-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 12mm 18mm;
            box-sizing: border-box;
        }

        /* Black L-shaped Corners */
        .corner-top-left {
            position: absolute;
            top: 8mm;
            left: 10mm;
            width: 75mm;
            height: 48mm;
            border-top: 7px solid #000000;
            border-left: 7px solid #000000;
            z-index: 10;
        }

        .corner-bottom-right {
            position: absolute;
            bottom: 8mm;
            right: 10mm;
            width: 75mm;
            height: 48mm;
            border-bottom: 7px solid #000000;
            border-right: 7px solid #000000;
            z-index: 10;
        }

        /* Dashed Cyan Inner Border */
        .inner-dashed-border {
            position: absolute;
            top: 13mm;
            left: 15mm;
            right: 15mm;
            bottom: 13mm;
            border: 2px dashed #00d2ff;
            z-index: 5;
        }

        /* Background Watermark (Golden Laurel Wreath) */
        .watermark-container {
            position: absolute;
            top: 48%;
            left: 50%;
            width: 140mm;
            height: 140mm;
            margin-top: -70mm;
            margin-left: -70mm;
            opacity: 0.12;
            z-index: 1;
            text-align: center;
        }

        /* Bottom-Left Wave Lines Accent */
        .bottom-left-waves {
            position: absolute;
            bottom: 0mm;
            left: 0mm;
            width: 75mm;
            height: 50mm;
            z-index: 2;
            opacity: 0.7;
        }

        /* Top Banner Triangles Decor */
        .top-triangles-bar {
            position: absolute;
            top: 2mm;
            left: 15mm;
            right: 15mm;
            height: 8mm;
            z-index: 3;
            text-align: center;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            position: relative;
            z-index: 20;
            margin-top: 4mm;
        }

        .ref-col {
            width: 22%;
            vertical-align: top;
            font-size: 11pt;
            font-weight: bold;
            color: #000000;
            padding-left: 8mm;
            padding-top: 2mm;
        }

        .header-center-col {
            width: 56%;
            text-align: center;
            vertical-align: top;
        }

        .logo-col {
            width: 22%;
            text-align: right;
            vertical-align: top;
            padding-right: 8mm;
        }

        .logo-img {
            max-height: 25mm;
            width: auto;
        }

        .republique {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 5px;
            color: #000000;
            margin: 0;
            text-transform: uppercase;
        }

        .devise {
            font-size: 9.5pt;
            font-style: italic;
            color: #334155;
            margin-top: 2px;
            margin-bottom: 4px;
        }

        /* Flag bar with Star */
        .flag-bar-table {
            width: 180px;
            height: 4px;
            margin: 3px auto 6px auto;
            border-collapse: collapse;
        }

        .flag-green {
            width: 33%;
            height: 4px;
            background-color: #00853f;
        }

        .flag-yellow {
            width: 34%;
            height: 4px;
            background-color: #fdef42;
            text-align: center;
            position: relative;
        }

        .flag-red {
            width: 33%;
            height: 4px;
            background-color: #e31b23;
        }

        .ministere {
            font-size: 10pt;
            font-weight: bold;
            color: #000000;
            margin: 2px 0 1px 0;
        }

        .centre-title {
            font-size: 11pt;
            font-weight: bold;
            color: #000000;
            margin: 0;
        }

        /* Certificate Main Title */
        .title-section {
            text-align: center;
            margin-top: 4mm;
            position: relative;
            z-index: 20;
        }

        .main-title {
            font-size: 32pt;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 14px;
            margin: 0;
            text-transform: uppercase;
        }

        .ornament-table {
            width: 300px;
            margin: 6px auto 0 auto;
            border-collapse: collapse;
        }

        .ornament-dot-sm {
            width: 8px;
            height: 8px;
            background-color: #2563eb;
            border-radius: 50%;
            margin: 0 auto;
        }

        .ornament-dot-lg {
            width: 12px;
            height: 12px;
            background-color: #2563eb;
            border-radius: 50%;
            margin: 0 auto;
        }

        .ornament-line-bar {
            height: 2px;
            background-color: #000000;
            width: 100%;
        }

        /* Body Text Section */
        .body-section {
            width: 84%;
            margin: 6mm auto 0 auto;
            text-align: center;
            font-size: 12.5pt;
            line-height: 1.5;
            color: #000000;
            position: relative;
            z-index: 20;
        }

        .attest-intro {
            font-style: italic;
            font-size: 12pt;
            margin-bottom: 3mm;
        }

        .student-line {
            font-size: 14pt;
            margin: 2mm 0;
        }

        .student-name {
            font-weight: bold;
            font-size: 16.5pt;
            color: #000000;
            font-style: italic;
        }

        .birth-info {
            font-style: italic;
            font-size: 12pt;
            margin-bottom: 3mm;
        }

        .success-line {
            font-style: italic;
            font-size: 12pt;
            margin-bottom: 2mm;
        }

        .module-title {
            font-weight: bold;
            font-size: 15.5pt;
            color: #2563eb;
            font-style: italic;
            margin-bottom: 4mm;
        }

        .period-line {
            font-size: 12.5pt;
            margin-bottom: 4mm;
        }

        .period-bold {
            font-weight: bold;
            font-style: italic;
        }

        .conclusion-line {
            font-style: italic;
            font-size: 11pt;
            margin-top: 3mm;
        }

        /* Footer Section */
        .footer-table {
            width: 88%;
            margin: 5mm auto 0 auto;
            border-collapse: collapse;
            position: relative;
            z-index: 20;
        }

        .qr-cell {
            width: 40%;
            vertical-align: bottom;
            text-align: left;
            padding-left: 5mm;
        }

        .qr-img {
            width: 25mm;
            height: 25mm;
        }

        .qr-caption {
            font-size: 8pt;
            font-style: italic;
            color: #475569;
            margin-top: 2px;
        }

        .sign-cell {
            width: 60%;
            vertical-align: top;
            text-align: right;
            padding-right: 5mm;
        }

        .location-date {
            font-size: 11.5pt;
            font-style: italic;
            color: #000000;
            margin-bottom: 2px;
        }

        .sign-title {
            font-size: 12.5pt;
            font-weight: bold;
            color: #000000;
            margin-top: 1mm;
        }

        .signature-space {
            height: 16mm;
        }
    </style>
</head>
<body>

    <!-- Outer Corner Decorations -->
    <div class="corner-top-left"></div>
    <div class="corner-bottom-right"></div>
    <div class="inner-dashed-border"></div>

    <!-- Top Geometric Banner Triangles Decor -->
    <div class="top-triangles-bar">
        <svg width="600" height="24" viewBox="0 0 600 24" xmlns="http://www.w3.org/2000/svg">
            <polygon points="10,0 25,20 40,0" fill="#00853f" opacity="0.8"/>
            <polygon points="60,0 75,18 90,0" fill="#fdef42" opacity="0.8"/>
            <polygon points="110,0 125,22 140,0" fill="#e31b23" opacity="0.8"/>
            <polygon points="160,0 175,16 190,0" fill="#2563eb" opacity="0.8"/>
            <polygon points="210,0 225,20 240,0" fill="#f97316" opacity="0.8"/>
            <polygon points="260,0 275,18 290,0" fill="#00853f" opacity="0.8"/>
            <polygon points="310,0 325,22 340,0" fill="#e31b23" opacity="0.8"/>
            <polygon points="360,0 375,16 390,0" fill="#2563eb" opacity="0.8"/>
            <polygon points="410,0 425,20 440,0" fill="#fdef42" opacity="0.8"/>
            <polygon points="460,0 475,18 490,0" fill="#f97316" opacity="0.8"/>
            <polygon points="510,0 525,22 540,0" fill="#00853f" opacity="0.8"/>
            <polygon points="560,0 575,16 590,0" fill="#e31b23" opacity="0.8"/>
        </svg>
    </div>

    <!-- Bottom-Left Wave Lines Accent -->
    <div class="bottom-left-waves">
        <svg width="220" height="150" viewBox="0 0 220 150" xmlns="http://www.w3.org/2000/svg">
            <path d="M 0 150 Q 50 100 100 125 T 200 100 T 300 125" fill="none" stroke="#00d2ff" stroke-width="1.5" opacity="0.6"/>
            <path d="M 0 140 Q 60 90 110 115 T 210 90 T 310 115" fill="none" stroke="#38bdf8" stroke-width="1" opacity="0.7"/>
            <path d="M 0 130 Q 70 80 120 105 T 220 80 T 320 105" fill="none" stroke="#0284c7" stroke-width="0.8" opacity="0.5"/>
            <path d="M 0 120 Q 80 70 130 95 T 230 70 T 330 95" fill="none" stroke="#93c5fd" stroke-width="0.6" opacity="0.4"/>
        </svg>
    </div>

    <!-- Background Watermark -->
    <div class="watermark-container">
        <svg viewBox="0 0 200 200" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" stroke="#d97706" stroke-width="1.5">
                <circle cx="100" cy="100" r="85" stroke-dasharray="3,3"/>
                <circle cx="100" cy="100" r="75"/>
                <path d="M 60 140 C 40 100, 50 60, 100 45 C 150 60, 160 100, 140 140" stroke-width="2"/>
                <path d="M 50 130 C 35 95, 45 65, 80 50" stroke-width="1"/>
                <path d="M 150 130 C 165 95, 155 65, 120 50" stroke-width="1"/>
            </g>
            <text x="100" y="95" text-anchor="middle" fill="#d97706" font-size="11" font-weight="bold" font-family="DejaVu Sans">ATTESTATION</text>
            <text x="100" y="112" text-anchor="middle" fill="#d97706" font-size="10" font-weight="bold" font-family="DejaVu Sans">DE REUSSITE</text>
            <polygon points="100,68 104,78 114,78 106,84 109,94 100,88 91,94 94,84 86,78 96,78" fill="#d97706" opacity="0.5"/>
        </svg>
    </div>

    <div class="page-container">

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="ref-col">
                    N° {{ sprintf('%03d', $certificate->id ?? 1) }} / {{ $anneeAcademique ?? date('Y') }}
                </td>
                <td class="header-center-col">
                    <h1 class="republique">REPUBLIQUE DU SENEGAL</h1>
                    <div class="devise">Un Peuple - Un But - Une Foi</div>
                    <table class="flag-bar-table">
                        <tr>
                            <td class="flag-green"></td>
                            <td class="flag-yellow"><span style="color: #00853f; font-size: 8px; position: absolute; top: -6px; left: 50%; margin-left: -4px;">★</span></td>
                            <td class="flag-red"></td>
                        </tr>
                    </table>
                    <div class="ministere">Ministère de l'Enseignement Supérieur, de la Recherche et de l'Innovation</div>
                    <div class="centre-title">Centre de Recherche et d'Essais (CRE) de Kolda</div>
                </td>
                <td class="logo-col">
                    @if(file_exists(public_path('images/logo-cre.png')))
                        <img src="{{ public_path('images/logo-cre.png') }}" class="logo-img" alt="CRE KOLDA">
                    @else
                        <div style="font-weight: bold; color: #2563eb; font-size: 16pt;">CRE KOLDA</div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Main Title -->
        <div class="title-section">
            <h2 class="main-title">A T T E S T A T I O N</h2>
            <table class="ornament-table">
                <tr>
                    <td style="width: 14px; text-align: center;"><div class="ornament-dot-sm"></div></td>
                    <td style="vertical-align: middle;"><div class="ornament-line-bar"></div></td>
                    <td style="width: 18px; text-align: center;"><div class="ornament-dot-lg"></div></td>
                    <td style="vertical-align: middle;"><div class="ornament-line-bar"></div></td>
                    <td style="width: 14px; text-align: center;"><div class="ornament-dot-sm"></div></td>
                </tr>
            </table>
        </div>

        <!-- Body Text -->
        <div class="body-section">
            <div class="attest-intro">
                Le gestionnaire du Centre de Recherche et d'Essais (CRE) de Kolda soussigné, atteste que :
            </div>

            <div class="student-line">
                <span style="font-style: italic;">{{ $civilite ?? 'M./Mme' }}</span> 
                <span class="student-name">{{ $student->name }}</span>
            </div>

            <div class="birth-info">
                Né(e) le <strong>{{ $dateNaissance ?? '........................' }}</strong> à <strong>{{ $lieuNaissance ?? '........................' }}</strong>
            </div>

            <div class="success-line">
                a réussi avec succès à la formation en informatique aux Modules :
            </div>

            <div class="module-title">
                {{ $module->titre ?? $module->title }}
            </div>

            <div class="period-line">
                <span class="period-bold">Période de la formation : Du {{ $dateDebut ?? '...................' }} au {{ $dateFin ?? '...................' }}</span>
            </div>

            <div class="conclusion-line">
                En foi de quoi cette présente Attestation est délivrée pour servir et valoir ce que de droit.
            </div>
        </div>

        <!-- Footer -->
        <table class="footer-table">
            <tr>
                <td class="qr-cell">
                    @if(isset($qrCode) && $qrCode)
                        <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr-img" alt="QR Code">
                    @endif
                    <div class="qr-caption">Le cachet sec faisant foi.</div>
                </td>
                <td class="sign-cell">
                    <div class="location-date">Fait à Kolda le {{ $issuedDate ?? date('d/m/Y') }}</div>
                    <div class="sign-title">Le Gestionnaire</div>
                    <div class="signature-space"></div>
                </td>
            </tr>
        </table>

    </div>

</body>
</html>


