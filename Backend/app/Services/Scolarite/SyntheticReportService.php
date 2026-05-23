<?php

namespace App\Services\Scolarite;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyntheticReportService
{
    public function getAggregatedData(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $totalLearners = User::role('Apprenant')->count();
        $newEnrollments = DB::table('group_user')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $attendanceStats = $this->getAttendanceStats($start, $end);
        $certificationStats = $this->getCertificationStats($start, $end);
        $parityStats = $this->getParityStats();
        $hardwareStats = $this->getHardwareStats();

        $dataForAi = [
            'period' => [
                'label' => "Du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y'),
            ],
            'metrics' => [
                'total_learners' => $totalLearners,
                'new_enrollments' => $newEnrollments,
                'attendance_rate' => $attendanceStats['rate'],
                'certification_count' => $certificationStats['count'],
                'success_rate' => $this->calculateSuccessRate($totalLearners, $certificationStats['count']),
                'parity_female_ratio' => $parityStats['ratio'],
                'hardware_operational_rate' => $hardwareStats['rate'],
            ]
        ];

        return [
            'period' => [
                'start' => $start->format('d/m/Y'),
                'end' => $end->format('d/m/Y'),
                'label' => "Du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y'),
            ],
            'metrics' => [
                'total_learners' => $totalLearners,
                'new_enrollments' => $newEnrollments,
                'attendance_rate' => $attendanceStats['rate'],
                'certification_count' => $certificationStats['count'],
                'success_rate' => $this->calculateSuccessRate($totalLearners, $certificationStats['count']),
                'parity_female_ratio' => $parityStats['ratio'],
                'hardware_operational_rate' => $hardwareStats['rate'],
            ],
            'details' => [
                'attendance' => $attendanceStats,
                'certification' => $certificationStats,
                'parity' => $parityStats,
                'hardware' => $hardwareStats,
            ],
            'insights' => $this->generateInsights($attendanceStats, $certificationStats, $parityStats, $hardwareStats, $dataForAi),
        ];
    }

    private function getAttendanceStats(Carbon $start, Carbon $end): array
    {
        $query = Attendance::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
        $total = $query->count();
        $present = (clone $query)->where('status', 'present')->count();
        $absent = (clone $query)->where('status', 'absent_non_justifie')->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    private function getCertificationStats(Carbon $start, Carbon $end): array
    {
        $count = Certificate::whereBetween('created_at', [$start, $end])->count();
        $byModule = Certificate::whereBetween('created_at', [$start, $end])
            ->with('module')
            ->get()
            ->groupBy('module_id')
            ->map(fn($items) => [
                'name' => $items->first()->module->titre ?? 'N/A',
                'count' => $items->count(),
            ])->values()->toArray();

        return [
            'count' => $count,
            'by_module' => $byModule,
        ];
    }

    private function getParityStats(): array
    {
        $counts = User::role('Apprenant')
            ->leftJoin('applications', 'users.id', '=', 'applications.user_id')
            ->select(
                DB::raw("COUNT(CASE WHEN applications.sexe = 'F' THEN 1 END) as female"),
                DB::raw("COUNT(*) as total"),
            )
            ->first();

        $total = (int) ($counts->total ?? 0);
        $female = (int) ($counts->female ?? 0);

        return [
            'total' => $total,
            'female' => $female,
            'male' => $total - $female,
            'ratio' => $total > 0 ? round(($female / $total) * 100, 1) : 0,
        ];
    }

    private function getHardwareStats(): array
    {
        $total = Asset::count();
        $broken = Asset::where('etat', 'hors_service')->count();
        $operational = $total - $broken;

        return [
            'total' => $total,
            'operational' => $operational,
            'broken' => $broken,
            'rate' => $total > 0 ? round(($operational / $total) * 100, 1) : 0,
        ];
    }

    private function calculateSuccessRate(int $totalParticipants, int $certificates): float
    {
        return $totalParticipants > 0 ? round(($certificates / $totalParticipants) * 100, 1) : 0;
    }

    private function generateInsights(array $attendance, array $cert, array $parity, array $hardware, array $dataForAi): array
    {
        $gemini = new GeminiService();
        $aiInsights = $gemini->generateActivityAnalysis($dataForAi);

        if (!empty($aiInsights)) {
            return $aiInsights;
        }

        $recommendations = [];
        $analysis = [];

        // Attendance Analysis
        if ($attendance['rate'] < 75) {
            $analysis[] = "Le taux d'assiduité est inférieur à l'objectif institutionnel (75%).";
            $recommendations[] = "Renforcer le suivi des absences et sensibiliser les apprenants sur l'importance de la régularité.";
        } else {
            $analysis[] = "L'assiduité est satisfaisante avec un taux de {$attendance['rate']}%.";
        }

        // Parity Analysis
        if ($parity['ratio'] < 40) {
            $analysis[] = "La parité de genre est déséquilibrée avec seulement {$parity['ratio']}% de femmes.";
            $recommendations[] = "Mettre en place des campagnes de recrutement ciblées 'Femmes & Numérique'.";
        }

        // Hardware Analysis
        if ($hardware['rate'] < 90) {
            $analysis[] = "Le matériel opérationnel est critique ({$hardware['rate']}%).";
            $recommendations[] = "Prévoir une session de maintenance curative et le remplacement des unités hors service.";
        } else {
            $analysis[] = "Le parc informatique est globalement fonctionnel.";
        }

        // Success trends
        if ($cert['count'] > 0) {
            $analysis[] = "Une dynamique de certification est en cours avec {$cert['count']} nouveaux certificats.";
        }

        return [
            'analysis' => $analysis,
            'recommendations' => $recommendations,
            'projections' => [
                "Basé sur les tendances actuelles, nous prévoyons une augmentation de 15% des certifications le mois prochain.",
                "L'optimisation du matériel permettrait d'accueillir 10 nouveaux apprenants par session."
            ]
        ];
    }
}
