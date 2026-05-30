<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\User;
use App\Models\Application;
use App\Models\Module;
use App\Models\Asset;
use App\Models\Loan;
use App\Models\Chapter;
use App\Models\ChapterGroupProgress;
use App\Models\ExamResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DirectorDashboardController extends Controller
{
    /**
     * Display the Director's dashboard with KPIs.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('Dashboard/Director', [
            'kpis' => $this->getKpis(),
        ]);
    }

    /**
     * Export the monthly report as PDF.
     */
    public function exportPdf(): Response
    {
        $kpis = $this->getKpis();

        $pdf = Pdf::loadView('pdf.monthly-report', [
            'kpis'       => $kpis,
            'generated'  => now()->format('d/m/Y H:i'),
            'month'      => now()->translatedFormat('F Y'),
        ]);

        return $pdf->download('rapport-mensuel-' . now()->format('Y-m') . '.pdf');
    }

    /**
     * Aggregate all KPIs.
     *
     * @return array<string, mixed>
     */
    public function getKpis(): array
    {
        return [
            'attendance_rate'          => $this->getAttendanceRate(),
            'gender_parity'            => $this->getGenderParity(),
            'success_rate'             => $this->getSuccessRate(),
            'total_learners'           => $this->getTotalLearners(),
            'total_trainers'           => User::role('Formateur')->count(),
            'total_groups'             => Group::count(),
            'total_certificates'       => Certificate::count(),
            'operational_hardware'     => $this->getOperationalHardwareRate(),
            'module_validation_rates'  => $this->getModuleValidationRates(),
            'admissions'               => $this->getAdmissionsStats(),
            'logistics'                => $this->getLogisticsStats(),
            'ecosystem'                => $this->getEcosystemStats(),
            'pedagogical'              => $this->getPedagogicalStats(),
            'alerts'                   => $this->getAlerts(),
        ];
    }

    /**
     * KPI: Overall attendance rate (present / total entries).
     */
    private function getAttendanceRate(): float
    {
        $total   = Attendance::count();
        $present = Attendance::where('status', 'present')->count();

        return $total > 0
            ? round(($present / $total) * 100, 1)
            : 0.0;
    }

    /**
     * KPI: Gender parity among learners.
     *
     * @return array{male: int, female: int, ratio: float}
     */
    private function getGenderParity(): array
    {
        $counts = User::role('Apprenant')
            ->join('applications', 'users.id', '=', 'applications.user_id')
            ->select(
                DB::raw("COUNT(CASE WHEN applications.sexe = 'F' THEN 1 END) as female"),
                DB::raw("COUNT(CASE WHEN applications.sexe = 'M' THEN 1 END) as male"),
                DB::raw("COUNT(*) as total"),
            )
            ->first();

        $total  = (int) ($counts->total ?? 0);
        $female = (int) ($counts->female ?? 0);
        $male   = (int) ($counts->male ?? 0);

        return [
            'male'   => $male,
            'female' => $female,
            'total'  => $total,
            'ratio'  => $total > 0 ? round($female / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * KPI: Success rate (learners with certificates / total learners).
     */
    private function getSuccessRate(): float
    {
        $totalLearners    = $this->getTotalLearners();
        $certifiedLearners = Certificate::distinct('user_id')->count('user_id');

        return $totalLearners > 0
            ? round(($certifiedLearners / $totalLearners) * 100, 1)
            : 0.0;
    }

    /**
     * KPI: Operational hardware rate.
     */
    private function getOperationalHardwareRate(): float
    {
        $total = Asset::count();
        $operational = Asset::where('etat', '!=', 'hors_service')->count();

        return $total > 0 ? round(($operational / $total) * 100, 1) : 0.0;
    }

    /**
     * Statistics: Validation rates per module (Bar Chart data).
     *
     * @return array<string, float>
     */
    private function getModuleValidationRates(): array
    {
        return Module::withCount(['certificates'])
            ->get()
            ->mapWithKeys(fn($module) => [
                $module->nom_module => $module->certificates_count
            ])->toArray();
    }

    /**
     * Alerts: Students with >= 2 absences and broken hardware.
     *
     * @return array{learners_at_risk: array, broken_assets: array}
     */
    private function getAlerts(): array
    {
        $learnersAtRisk = Attendance::where('status', 'absent_non_justifie')
            ->select('user_id', DB::raw('COUNT(*) as total_absences'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->with('user:id,name,email')
            ->get();

        $brokenAssets = Asset::where('etat', 'hors_service')->get();

        return [
            'learners_at_risk' => $learnersAtRisk,
            'broken_assets'    => $brokenAssets,
        ];
    }

    /**
     * Total number of learners (users with Apprenant role).
     */
    private function getTotalLearners(): int
    {
        return User::role('Apprenant')->count();
    }

    /**
     * KPI: Admissions statistics.
     */
    private function getAdmissionsStats(): array
    {
        return [
            'total'               => Application::count(),
            'pending'             => Application::where('status', 'pending')->count(),
            'accepted_this_month' => Application::where('status', 'admitted')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    /**
     * KPI: Logistics and Assets statistics.
     */
    private function getLogisticsStats(): array
    {
        return [
            'total_assets'     => Asset::count(),
            'active_loans'     => Loan::whereNull('returned_at')->count(),
            'defective_assets' => Asset::where('etat', 'hors_service')->count(),
        ];
    }

    /**
     * KPI: Ecosystem and Partnerships statistics.
     */
    private function getEcosystemStats(): array
    {
        return [
            'total_partners'  => \App\Models\Partnership::where('status', 'actif')->count(),
            'upcoming_events' => \App\Models\Event::where('status', 'actif')
                ->where('date', '>=', now())
                ->count(),
        ];
    }

    /**
     * KPI: Pedagogical performance statistics.
     */
    private function getPedagogicalStats(): array
    {
        $avgExamScore = \App\Models\ExamResult::avg('score') ?? 0;
        
        $totalChapters = \App\Models\Chapter::count();
        $validatedChapters = \App\Models\ChapterGroupProgress::where('status', 'approved')->count();

        return [
            'avg_exam_score'           => round((float)$avgExamScore, 1),
            'chapters_validated_rate'  => $totalChapters > 0 ? round(($validatedChapters / $totalChapters) * 100, 1) : 0.0,
        ];
    }

    /**
     * Get statistics for the dashboard via API.
     */
    public function apiStats()
    {
        $parity = $this->getGenderParity();

        return response()->json([
            'total_trained' => $this->getTotalLearners(),
            'attendance_rate' => $this->getAttendanceRate(),
            'operational_assets' => $this->getOperationalHardwareRate(),
            'parity_rate' => $parity['ratio'],
            'gender_distribution' => [$parity['male'], $parity['female']],
            'validation_by_module' => $this->getModuleValidationRates(),
            'admissions' => $this->getAdmissionsStats(),
            'logistics' => $this->getLogisticsStats(),
            'ecosystem' => $this->getEcosystemStats(),
            'pedagogical' => $this->getPedagogicalStats(),
        ]);
    }
}
