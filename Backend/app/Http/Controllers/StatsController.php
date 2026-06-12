<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\User;
use App\Models\Module;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Stats/Index', [
            'growth_data'        => $this->getGrowthData(),
            'module_performance' => $this->getModulePerformance(),
            'attendance_stats'   => $this->getAttendanceStats(),
        ]);
    }

    private function getGrowthData(): array
    {
        $monthly = User::select(DB::raw("to_char(created_at, 'Mon YYYY') as month"), DB::raw("count(*) as count"))
            ->groupBy(DB::raw("to_char(created_at, 'Mon YYYY'), date_trunc('month', created_at)"))
            ->orderBy(DB::raw("date_trunc('month', min(created_at))"))
            ->take(6)
            ->get();

        // Month-over-month growth %
        $thisMonth = User::whereRaw("date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE)")->count();
        $lastMonth = User::whereRaw("date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE - INTERVAL '1 month')")->count();
        $growthPct = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null;

        return [
            'months'     => $monthly->toArray(),
            'growth_pct' => $growthPct,
        ];
    }

    private function getModulePerformance(): array
    {
        return Module::withCount('certificates')
            ->get()
            ->map(fn($m) => [
                'name'         => $m->titre,
                'certificates' => $m->certificates_count,
            ])->toArray();
    }

    private function getAttendanceStats(): array
    {
        $totalRecords = Attendance::count();

        if ($totalRecords === 0) {
            return [
                'weekly_trends'   => [],
                'group_breakdown' => [],
                'status_summary'  => ['present' => 0, 'absent' => 0, 'late' => 0, 'justified' => 0],
                'overall_rate'    => 0,
                'target_rate'     => 90,
                'trend_direction' => 'stable',
                'total_sessions'  => 0,
                'total_students'  => 0,
            ];
        }

        // Weekly trends (last 8 weeks)
        $weeklyRaw = DB::select("
            SELECT
                to_char(date, 'IYYY-IW') AS iso_week,
                MIN(date) AS week_start,
                COUNT(*) AS total,
                SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'justified' THEN 1 ELSE 0 END) AS justified_count
            FROM attendances
            WHERE date >= CURRENT_DATE - INTERVAL '8 weeks'
            GROUP BY iso_week
            ORDER BY iso_week
        ");

        $weeklyTrends = collect($weeklyRaw)->map(function ($row, $i) {
            $rate = $row->total > 0 ? round(($row->present_count / $row->total) * 100, 1) : 0;
            $weekStart = Carbon::parse($row->week_start);
            return [
                'week'      => 'S' . ($i + 1),
                'label'     => $weekStart->format('d M'),
                'rate'      => $rate,
                'total'     => (int) $row->total,
                'present'   => (int) $row->present_count,
                'absent'    => (int) $row->absent_count,
                'late'      => (int) $row->late_count,
                'justified' => (int) $row->justified_count,
            ];
        })->values()->toArray();

        // Group breakdown
        $groups = Group::with(['module', 'formateur'])->get();
        $groupBreakdown = $groups->map(function ($group) {
            $stats = DB::table('attendances')
                ->where('group_id', $group->id)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                    DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count"),
                    DB::raw("SUM(CASE WHEN status = 'justified' THEN 1 ELSE 0 END) as justified_count")
                )->first();

            $rate = ($stats && $stats->total > 0)
                ? round(($stats->present_count / $stats->total) * 100, 1)
                : 0;

            return [
                'group_id'   => $group->id,
                'group_name' => $group->nom_groupe,
                'module'     => $group->module?->titre ?? 'N/A',
                'formateur'  => $group->formateur?->name ?? 'N/A',
                'rate'       => $rate,
                'total'      => (int) ($stats?->total ?? 0),
                'present'    => (int) ($stats?->present_count ?? 0),
                'absent'     => (int) ($stats?->absent_count ?? 0),
                'late'       => (int) ($stats?->late_count ?? 0),
                'justified'  => (int) ($stats?->justified_count ?? 0),
                'students'   => $group->students()->count(),
            ];
        })->values()->toArray();

        // Overall status summary
        $statusSummary = DB::table('attendances')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAll   = array_sum($statusSummary);
        $presentAll = ($statusSummary['present'] ?? 0) + ($statusSummary['late'] ?? 0);
        $overallRate = $totalAll > 0 ? round(($presentAll / $totalAll) * 100, 1) : 0;

        // Trend direction
        $trendDirection = 'stable';
        if (count($weeklyTrends) >= 2) {
            $last = $weeklyTrends[count($weeklyTrends) - 1]['rate'];
            $prev = $weeklyTrends[count($weeklyTrends) - 2]['rate'];
            $trendDirection = $last > $prev ? 'up' : ($last < $prev ? 'down' : 'stable');
        }

        return [
            'weekly_trends'   => $weeklyTrends,
            'group_breakdown' => $groupBreakdown,
            'status_summary'  => [
                'present'   => (int) ($statusSummary['present'] ?? 0),
                'absent'    => (int) ($statusSummary['absent'] ?? 0),
                'late'      => (int) ($statusSummary['late'] ?? 0),
                'justified' => (int) ($statusSummary['justified'] ?? 0),
            ],
            'overall_rate'    => $overallRate,
            'target_rate'     => 90,
            'trend_direction' => $trendDirection,
            'total_sessions'  => (int) DB::table('attendances')
                ->distinct()
                ->count(DB::raw('date || \'_\' || group_id')),
            'total_students'  => (int) DB::table('group_user')->distinct()->count('user_id'),
        ];
    }
}
