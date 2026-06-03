<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\User;
use App\Exports\Scolarite\AttendanceExport;
use App\Exports\Scolarite\AcademicExport;
use App\Exports\Scolarite\ModuleEnrollmentExport;
use App\Exports\Scolarite\TrainerReportExport;
use App\Exports\Scolarite\ModuleStudentsExport;
use App\Exports\Scolarite\GlobalKpiExport;
use App\Exports\Scolarite\WordReportGenerator;
use App\Services\Scolarite\SyntheticReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * Display the reports management interface.
     */
    public function index(): InertiaResponse
    {
        $trainers = User::role('Formateur')->select('id', 'name')->get();
        $assistants = User::role('Stagiaire')
            ->whereHas('internshipRecord', function($q) {
                $q->where('internship_type', 'course_assistant');
            })
            ->select('id', 'name')
            ->get()
            ->map(function($user) {
                $user->name = $user->name . " (Assistant)";
                return $user;
            });
        $allTrainers = $trainers->concat($assistants);

        return Inertia::render('Scolarite/Reports', [
            'groups' => Group::select('id', 'nom_groupe')->get(),
            'modules' => \App\Models\Module::select('id', 'titre')->get(),
            'trainers' => $allTrainers,
            'report_types' => [
                ['id' => 'attendance', 'name' => 'Rapport d\'Assiduité'],
                ['id' => 'academic', 'name' => 'Rapport de Performance Académique'],
                ['id' => 'module_students', 'name' => 'Rapport des Formés par Module'],
                ['id' => 'trainer_enrollment', 'name' => 'Rapport Effectifs par Formateur'],
                ['id' => 'module_enrollment', 'name' => 'Rapport Effectifs par Module'],
                ['id' => 'global_kpis', 'name' => 'Rapport de Synthèse (KPIs)'],
                ['id' => 'synthetic', 'name' => 'Rapport d\'Activité Complet (Word)'],
            ]
        ]);
    }

    /**
     * Generate a PDF report based on filters.
     */
    public function generate(Request $request): Response
    {
        $validated = $request->validate([
            'type'        => 'required|string|in:attendance,academic,global_kpis,module_students,trainer_enrollment,module_enrollment,synthetic',
            'format'      => 'nullable|string|in:pdf,excel,word',
            'group_id'    => 'nullable|exists:groups,id',
            'module_id'   => 'nullable|exists:modules,id',
            'trainer_id'  => 'nullable|exists:users,id',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
        ]);

        $type = $validated['type'];
        $format = $validated['format'] ?? 'pdf';

        if ($format === 'word' || $type === 'synthetic') {
            $service = new SyntheticReportService();
            $data = $service->getAggregatedData($validated['start_date'] ?? null, $validated['end_date'] ?? null);
            
            $generator = new WordReportGenerator();
            $path = $generator->generate($data);
            
            return response()->download($path, "rapport-synthetique-" . now()->format('Ymd-His') . ".docx")
                ->deleteFileAfterSend(true);
        }

        $data = $this->getReportData($validated);

        if ($format === 'excel') {
            $export = match($type) {
                'attendance'         => new AttendanceExport($data),
                'academic'           => new AcademicExport($data),
                'module_students'    => new ModuleStudentsExport($data),
                'trainer_enrollment' => new TrainerReportExport($data),
                'module_enrollment'  => new ModuleEnrollmentExport($data),
                'global_kpis'        => new GlobalKpiExport($data),
                default              => abort(400)
            };

            return Excel::download($export, "rapport-{$type}-" . now()->format('Ymd-His') . ".xlsx");
        }

        $view = match($type) {
            'attendance'        => 'pdf.attendance-report',
            'academic'          => 'pdf.academic-report',
            'module_students'   => 'pdf.module-report',
            'trainer_enrollment' => 'pdf.trainer-report',
            'module_enrollment'  => 'pdf.module-enrollment-report',
            'global_kpis'       => 'pdf.monthly-report',
            default             => abort(400)
        };

        // Merge common data with specific report data
        $pdfData = array_merge([
            'generated' => now()->format('d/m/Y H:i'),
            'filters'   => $validated,
        ], ($type === 'global_kpis' ? $data : ['data' => $data]));

        $pdf = Pdf::loadView($view, $pdfData);

        return $pdf->download("rapport-{$type}-" . now()->format('Ymd-His') . ".pdf");
    }

    /**
     * Fetch data for the report based on type and filters.
     */
    private function getReportData(array $filters): array
    {
        $type = $filters['type'];
        $groupId   = $filters['group_id'] ?? null;
        $moduleId  = $filters['module_id'] ?? null;
        $trainerId = $filters['trainer_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;

        return match($type) {
            'attendance'        => $this->getAttendanceData($groupId, $startDate, $endDate),
            'academic'          => $this->getAcademicData($groupId),
            'module_students'   => $this->getModuleStudentsData($moduleId),
            'trainer_enrollment' => $this->getTrainerEnrollmentData($trainerId, $startDate, $endDate),
            'module_enrollment'  => $this->getModuleEnrollmentData($groupId, $startDate, $endDate),
            'global_kpis'       => $this->getGlobalKpiData(),
            default             => []
        };
    }

    private function getAttendanceData($groupId, $startDate, $endDate): array
    {
        $query = Attendance::with(['user', 'schedule.group']);

        if ($groupId) {
            $query->whereHas('schedule', fn($q) => $q->where('group_id', $groupId));
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $attendances = $query->get()->groupBy('user_id');

        $data = [];
        foreach ($attendances as $userId => $userAttendances) {
            $user = $userAttendances->first()->user;
            $data[] = [
                'student_name' => $user->name,
                'total'        => $userAttendances->count(),
                'present'      => $userAttendances->where('status', 'present')->count(),
                'absent'       => $userAttendances->where('status', 'absent_non_justifie')->count(),
                'late'         => $userAttendances->where('status', 'en_retard')->count(),
            ];
        }

        return [
            'records' => $data,
            'group'   => $groupId ? Group::find($groupId)->nom_groupe : 'Global',
            'period'  => ($startDate && $endDate) ? "Du $startDate au $endDate" : 'Toute la période',
        ];
    }

    private function getAcademicData($groupId): array
    {
        $query = User::role('Apprenant')->withCount('certificates');

        if ($groupId) {
            $query->whereHas('groups', fn($q) => $q->where('groups.id', $groupId));
        }

        $students = $query->get();

        return [
            'students' => $students->map(fn($s) => [
                'name'         => $s->name,
                'email'        => $s->email,
                'certificates' => $s->certificates_count,
            ]),
            'group' => $groupId ? Group::find($groupId)->nom_groupe : 'Global',
        ];
    }

    private function getModuleStudentsData($moduleId): array
    {
        $query = Certificate::query()->with('user');

        if ($moduleId) {
            $query->where('module_id', (int) $moduleId);
        }

        $certificates = $query->get();

        return [
            'module' => $moduleId ? \App\Models\Module::find($moduleId)->titre : 'Tous les Modules',
            'students' => $certificates->map(fn($c) => [
                'name' => $c->user->name,
                'date' => $c->created_at->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    private function getTrainerEnrollmentData($trainerId, $startDate, $endDate): array
    {
        $query = Group::query()->with(['formateur', 'students.application'])->withCount('students');

        if ($trainerId) {
            $query->where('formateur_id', (int) $trainerId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $groups = $query->get();
        $totalStudents = $groups->sum('students_count');

        // Comparative data per trainer with gender split
        $trainerStats = $groups->groupBy('formateur_id')->map(function($items) use ($totalStudents) {
            $count = $items->sum('students_count');
            
            $males = 0;
            $females = 0;
            foreach ($items as $group) {
                foreach ($group->students as $student) {
                    $sexe = $student->application->sexe ?? 'M'; // Default to M if unknown
                    if ($sexe === 'F' || $sexe === 'Femme') $females++;
                    else $males++;
                }
            }

            return [
                'name' => $items->first()->formateur->name ?? 'N/A',
                'count' => $count,
                'males' => $males,
                'females' => $females,
                'percentage' => $totalStudents > 0 ? round(($count / $totalStudents) * 100, 1) : 0,
            ];
        })->values();

        return [
            'trainer' => $trainerId ? User::find($trainerId)->name : 'Comparatif Global',
            'period'  => ($startDate && $endDate) ? "Du $startDate au $endDate" : 'Toute la période',
            'groups'  => $groups->map(function($g) use ($totalStudents) {
                $gFemales = 0;
                $gMales = 0;
                foreach ($g->students as $student) {
                    $sexe = $student->application->sexe ?? 'M';
                    if ($sexe === 'F' || $sexe === 'Femme') $gFemales++;
                    else $gMales++;
                }

                return [
                    'name'     => $g->nom_groupe,
                    'trainer'  => $g->formateur->name ?? 'N/A',
                    'count'    => $g->students_count,
                    'males'    => $gMales,
                    'females'  => $gFemales,
                    'percentage' => $totalStudents > 0 ? round(($g->students_count / $totalStudents) * 100, 1) : 0,
                    'created'  => $g->created_at->format('d/m/Y'),
                ];
            })->toArray(),
            'trainer_stats' => $trainerStats->toArray(),
            'total_students' => $totalStudents,
        ];
    }

    private function getGlobalKpiData(): array
    {
        $kpis = (new \App\Http\Controllers\DirectorDashboardController())->getKpis();
        
        return [
            'kpis'      => $kpis,
            'month'     => now()->translatedFormat('F Y'),
            'generated' => now()->format('d/m/Y H:i'),
        ];
    }

    private function getModuleEnrollmentData($groupId, $startDate, $endDate): array
    {
        $period = $startDate && $endDate ? "du {$startDate} au {$endDate}" : "Toutes périodes";

        $modules = \App\Models\Module::with(['groups' => function ($q) use ($groupId) {
            if ($groupId) {
                $q->where('id', '=', $groupId);
            }
            $q->with('students.application');
        }])->get()->map(function($m) use ($startDate, $endDate) {
            $males = 0;
            $females = 0;
            $total = 0;

            foreach ($m->groups as $group) {
                foreach ($group->students as $student) {
                    // Check period if applicable
                    if ($startDate && $endDate) {
                        $pivotDate = DB::table('group_user')
                            ->where('group_id', $group->id)
                            ->where('user_id', $student->id)
                            ->value('created_at');
                            
                        if ($pivotDate < $startDate || $pivotDate > $endDate) continue;
                    }

                    $total++;
                    $sexe = $student->application->sexe ?? 'M';
                    if ($sexe === 'F' || $sexe === 'Femme') $females++;
                    else $males++;
                }
            }

            return [
                'name'    => $m->titre,
                'code'    => $m->code_module,
                'count'   => $total,
                'males'   => $males,
                'females' => $females,
            ];
        })->toArray();

        $totalStudents = array_sum(array_column($modules, 'count'));

        return [
            'period'         => $period,
            'group'          => $groupId ? Group::find($groupId)?->nom_groupe : 'Tous les groupes',
            'total_students' => $totalStudents,
            'modules'        => array_map(function($m) use ($totalStudents) {
                $m['percentage'] = $totalStudents > 0 ? round(($m['count'] / $totalStudents) * 100, 1) : 0;
                return $m;
            }, $modules),
        ];
    }
}
