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
        $kpis = \Illuminate\Support\Facades\Cache::remember('director_dashboard_kpis_v3', 600, function () {
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
                'trainers_performance'    => $this->getTrainersPerformance(),
                'top_learners'            => $this->getTopLearners(),
                'attendance_stats'         => $this->getAttendanceStats(),
                'alerts'                   => $this->getAlerts(),
                'daily_trends'             => $this->getDailyTrends(),
                'module_distribution'      => $this->getModuleDistribution(),
            ];
        });

        $kpis['online_users_count'] = $this->getOnlineUsersCount();
        $kpis['trainers_availability'] = $this->getTrainersAvailability();

        return $kpis;
    }

    /**
     * Get count of users currently online (active within the last 5 minutes).
     */
    private function getOnlineUsersCount(): int
    {
        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * KPI: Detailed attendance statistics by role.
     *
     * @return array{learners: float, trainers: float}
     */
    private function getAttendanceStats(): array
    {
        // Learners (Apprenant / Élèves)
        $learnerTotal = Attendance::whereHas('user', function($q) { $q->role('Apprenant'); })->count();
        $learnerAbsent = Attendance::whereHas('user', function($q) { $q->role('Apprenant'); })
            ->where('status', '!=', 'present')->count();
        
        $learnerHours = Attendance::whereHas('user', function($q) { $q->role('Apprenant'); })
            ->where('attendances.status', '!=', 'present')
            ->join('schedules', 'attendances.schedule_id', '=', 'schedules.id')
            ->select(DB::raw('SUM(EXTRACT(EPOCH FROM (schedules.end_time - schedules.start_time)) / 3600) as total_hours'))
            ->value('total_hours') ?? 0;

        // Trainees (Stagiaire)
        $traineeTotal = Attendance::whereHas('user', function($q) { $q->role('Stagiaire'); })->count();
        $traineeAbsent = Attendance::whereHas('user', function($q) { $q->role('Stagiaire'); })
            ->where('status', '!=', 'present')->count();
        
        $traineeHours = Attendance::whereHas('user', function($q) { $q->role('Stagiaire'); })
            ->where('attendances.status', '!=', 'present')
            ->join('schedules', 'attendances.schedule_id', '=', 'schedules.id')
            ->select(DB::raw('SUM(EXTRACT(EPOCH FROM (schedules.end_time - schedules.start_time)) / 3600) as total_hours'))
            ->value('total_hours') ?? 0;

        // Trainers
        $trainerTotal = Attendance::whereHas('user', function($q) { $q->role('Formateur'); })->count();
        $trainerAbsent = Attendance::whereHas('user', function($q) { $q->role('Formateur'); })
            ->where('status', '!=', 'present')->count();

        $trainerHours = Attendance::whereHas('user', function($q) { $q->role('Formateur'); })
            ->where('attendances.status', '!=', 'present')
            ->join('schedules', 'attendances.schedule_id', '=', 'schedules.id')
            ->select(DB::raw('SUM(EXTRACT(EPOCH FROM (schedules.end_time - schedules.start_time)) / 3600) as total_hours'))
            ->value('total_hours') ?? 0;

        return [
            'learners_absence_rate'  => $learnerTotal > 0 ? round(($learnerAbsent / $learnerTotal) * 100, 1) : 0.0,
            'learners_absence_hours' => round((float)$learnerHours, 1),
            'trainees_absence_rate'  => $traineeTotal > 0 ? round(($traineeAbsent / $traineeTotal) * 100, 1) : 0.0,
            'trainees_absence_hours' => round((float)$traineeHours, 1),
            'trainers_absence_rate'  => $trainerTotal > 0 ? round(($trainerAbsent / $trainerTotal) * 100, 1) : 0.0,
            'trainers_absence_hours' => round((float)$trainerHours, 1),
        ];
    }

    /**
     * KPI: Trainers productivity based on chapter validations.
     */
    private function getTrainersPerformance(): array
    {
        return User::role('Formateur')
            ->withCount(['submittedChapters as chapters_validated_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->get()
            ->map(function ($trainer) {
                return [
                    'id'    => $trainer->id,
                    'name'  => $trainer->name,
                    'count' => $trainer->chapters_validated_count,
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    /**
     * KPI: Top 5 learners based on exam scores.
     */
    private function getTopLearners(): array
    {
        return ExamResult::with('user:id,name,email')
            ->select('user_id', DB::raw('AVG(score) as avg_score'))
            ->groupBy('user_id')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get()
            ->map(function ($result) {
                return [
                    'name'  => $result->user->name ?? 'Inconnu',
                    'email' => $result->user->email ?? '-',
                    'score' => round((float)$result->avg_score, 1),
                ];
            })
            ->toArray();
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
        $counts = User::role(['Apprenant', 'Stagiaire'])
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
    /**
     * KPI: Distribution of active learners per module.
     */
    private function getModuleDistribution(): array
    {
        $total = User::role(['Apprenant', 'Stagiaire'])->where('is_active', true)->count();

        $modules = Module::with('groups')->get();

        return $modules->map(function ($module) use ($total) {
            $count = DB::table('group_user')
                ->join('groups', 'group_user.group_id', '=', 'groups.id')
                ->join('users', 'group_user.user_id', '=', 'users.id')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('groups.module_id', $module->id)
                ->where('users.is_active', true)
                ->whereIn('roles.name', ['Apprenant', 'Stagiaire'])
                ->distinct('users.id')
                ->count('users.id');

            return [
                'id'        => $module->id,
                'titre'     => $module->titre,
                'code'      => $module->code_module,
                'count'     => $count,
                'percent'   => $total > 0 ? round($count / $total * 100, 1) : 0,
                'is_active' => (bool) $module->is_active,
            ];
        })
        ->sortByDesc('count')
        ->values()
        ->toArray();
    }

    private function getModuleValidationRates(): array
    {
        return Module::withCount(['certificates'])
            ->get()
            ->mapWithKeys(fn($module) => [
                $module->nom_module => $module->certificates_count
            ])->toArray();
    }

    private function getAlerts(): array
    {
        $learnersAtRisk = Attendance::where('status', 'absent_non_justifie')
            ->select('user_id', 'group_id', DB::raw('COUNT(*) as total_absences'))
            ->groupBy('user_id', 'group_id')
            ->havingRaw('COUNT(*) >= 2')
            ->with([
                'user:id,name,email',
                'group:id,nom_groupe,formateur_id',
                'group.formateur:id,name'
            ])
            ->get();

        $brokenAssets = Asset::where('etat', 'hors_service')->get();

        return [
            'learners_at_risk' => $learnersAtRisk,
            'broken_assets'    => $brokenAssets,
        ];
    }

    private function getTotalLearners(): int
    {
        return User::role(['Apprenant', 'Stagiaire'])->where('users.is_active', true)->count();
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
     * Get daily attendance trends for the last 10 active days.
     */
    private function getDailyTrends(): array
    {
        $dailyRaw = DB::select("
            SELECT
                date,
                COUNT(*) AS total,
                SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS present_count
            FROM attendances
            GROUP BY date
            ORDER BY date DESC
            LIMIT 100
        ");

        return collect($dailyRaw)->reverse()->map(function ($row) {
            $rate = $row->total > 0 ? round(($row->present_count / $row->total) * 100, 1) : 0;
            $date = \Carbon\Carbon::parse($row->date);
            return [
                'label'          => $date->translatedFormat('d M'),
                'rate'           => $rate,
                'absences_count' => (int)($row->total - $row->present_count),
                'total_count'    => (int)$row->total,
            ];
        })->values()->toArray();
    }

    /**
     * Get statistics for the dashboard via API.
     */
    public function apiStats()
    {
        return response()->json($this->getKpis());
    }

    /**
     * KPI: Trainers availability and schedules.
     */
    private function getTrainersAvailability(): array
    {
        return User::where('is_active', true)->where(function($query) {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'Formateur');
            })->orWhereHas('groupsAsFormateur')
              ->orWhere(function($q) {
                  $q->whereHas('roles', function($r) {
                      $r->where('name', 'Stagiaire');
                  })->whereHas('internshipRecord', function($i) {
                      $i->whereIn('internship_type', ['course_assistant', 'course_substitute']);
                  });
              });
        })->with(['schedules' => function($q) {
            $q->whereHas('group', function($groupQ) {
                $groupQ->where('status', 'active');
            })->with('group:id,nom_groupe');
        }])->get(['id', 'name'])->map(function($trainer) {
            $totalMinutes = 0;
            foreach ($trainer->schedules as $schedule) {
                if ($schedule->start_time && $schedule->end_time) {
                    $start = \Carbon\Carbon::parse($schedule->start_time);
                    $end = \Carbon\Carbon::parse($schedule->end_time);
                    if ($end->gt($start)) {
                        $totalMinutes += $end->diffInMinutes($start);
                    }
                }
            }
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $totalHoursFormatted = $minutes > 0 ? "{$hours}h" . str_pad((string)$minutes, 2, '0', STR_PAD_LEFT) : "{$hours}h";
            
            return [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'total_hours' => $totalHoursFormatted,
                'total_minutes' => $totalMinutes,
                'active_groups' => $trainer->schedules->pluck('group.nom_groupe')->filter()->unique()->values()
            ];
        })->sortByDesc('total_minutes')->values()->toArray();
    }

    /**
     * Get detailed absences for a specific learner in a specific group.
     */
    public function getLearnerAbsences(User $user, Group $group): \Illuminate\Http\JsonResponse
    {
        $absences = Attendance::where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->whereIn('status', ['absent_non_justifie', 'absent_justifie'])
            ->with(['schedule'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'group' => [
                'id' => $group->id,
                'nom_groupe' => $group->nom_groupe,
            ],
            'absences' => $absences->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->date ? $attendance->date->format('Y-m-d') : null,
                    'status' => $attendance->status,
                    'start_time' => $attendance->schedule->start_time ?? null,
                    'end_time' => $attendance->schedule->end_time ?? null,
                    'day_of_week' => $attendance->schedule->day_of_week ?? null,
                ];
            }),
        ]);
    }
}
