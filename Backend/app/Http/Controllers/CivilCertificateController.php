<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CivilCertificate;
use App\Enums\CivilCertificateType;
use App\Services\CivilCertificateRegistryService;
use App\Services\CivilCertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CivilCertificateController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');
        $query = CivilCertificate::orderBy('created_at', 'desc');
        
        if ($type && CivilCertificateType::tryFrom($type)) {
            $query->where('type', $type);
        }

        $certificates = $query->paginate(20);

        return Inertia::render('CivilCertificates/Index', [
            'certificates' => $certificates,
            'types' => array_column(CivilCertificateType::cases(), 'value'),
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('type', CivilCertificateType::RESIDENCE->value);
        return Inertia::render('CivilCertificates/Form', [
            'type' => $type,
            'types' => array_column(CivilCertificateType::cases(), 'value'),
        ]);
    }

    public function store(Request $request, CivilCertificateRegistryService $registryService)
    {
        $validatedData = $request->validate([
            'type' => ['required', 'string'],
            'applicant_first_name' => ['required', 'string', 'max:255'],
            'applicant_last_name' => ['required', 'string', 'max:255'],
            'applicant_cni' => ['nullable', 'string', 'max:50'],
            'data' => ['required', 'array'],
        ]);

        $enumType = CivilCertificateType::from($validatedData['type']);
        $centerCode = $request->input('data.center_code', 'DEF');

        $certificate = new CivilCertificate();
        $certificate->type = $enumType;
        $certificate->reference_number = $registryService->generateReference($enumType, $centerCode);
        $certificate->applicant_first_name = $validatedData['applicant_first_name'];
        $certificate->applicant_last_name = $validatedData['applicant_last_name'];
        $certificate->applicant_cni = $validatedData['applicant_cni'] ?? null;
        $certificate->data = $validatedData['data'];
        $certificate->status = 'pending';
        $certificate->save();

        return redirect()->route('civil-certificates.index')->with('success', 'Certificat créé avec succès et en attente de validation.');
    }

    public function show(CivilCertificate $civilCertificate)
    {
        return Inertia::render('CivilCertificates/Show', [
            'certificate' => $civilCertificate,
        ]);
    }

    public function approve(CivilCertificate $civilCertificate, CivilCertificatePdfService $pdfService)
    {
        // Require specific permission or role in a real prod env
        // $this->authorize('approve', $civilCertificate);

        if ($civilCertificate->status === 'validated') {
            return back()->with('error', 'Ce certificat est déjà validé et bloqué.');
        }

        $civilCertificate->status = 'validated';
        $civilCertificate->validated_by = Auth::id() ?? 1; // Fallback to 1 if not logged in for testing
        $civilCertificate->validated_at = now();
        
        // Generate PDF securely
        $pdfPath = $pdfService->generateFor($civilCertificate);
        $civilCertificate->pdf_path = $pdfPath;
        
        $civilCertificate->save();

        return back()->with('success', 'Certificat validé avec succès. PDF généré et registre mis à jour.');
    }
}
