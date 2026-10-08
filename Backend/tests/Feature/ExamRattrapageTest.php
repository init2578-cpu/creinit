<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamRattrapage;
use App\Models\ExamResult;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;
use App\Notifications\NewExamRattrapageNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExamRattrapageTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $trainer;
    protected User $studentJustified;
    protected User $studentRegular;
    protected Module $module;
    protected Group $group;
    protected Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Formateur']);
        Role::firstOrCreate(['name' => 'Apprenant']);
        Role::firstOrCreate(['name' => 'Secrétaire']);

        $this->director = User::factory()->create();
        $this->director->assignRole('Directeur');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('Formateur');

        $this->studentJustified = User::factory()->create(['name' => 'Fatou Diop']);
        $this->studentJustified->assignRole('Apprenant');

        $this->studentRegular = User::factory()->create(['name' => 'Amadou Sall']);
        $this->studentRegular->assignRole('Apprenant');

        $this->module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Développement Web',
            'quota_heures' => 40,
        ]);

        $this->group = Group::create([
            'nom_groupe' => 'Groupe Dév A',
            'module_id' => $this->module->id,
            'formateur_id' => $this->trainer->id,
            'annee_academique' => '2025-2026',
        ]);

        $this->group->students()->attach([
            $this->studentJustified->id,
            $this->studentRegular->id,
        ]);

        $examDate = Carbon::now()->subDays(2);

        $this->exam = Exam::create([
            'module_id' => $this->module->id,
            'user_id' => $this->trainer->id,
            'titre' => 'Évaluation Finale PHP',
            'type' => 'online',
            'scheduled_at' => $examDate,
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
        ]);

        $this->exam->groups()->attach($this->group->id);

        Question::create([
            'exam_id' => $this->exam->id,
            'enonce' => 'Quelle fonction retourne la longueur d\'une chaîne ?',
            'points' => 20,
            'type' => 'open',
            'expected_answer' => 'strlen',
        ]);

        // Justified attendance for studentJustified on the exam date
        Attendance::create([
            'user_id' => $this->studentJustified->id,
            'group_id' => $this->group->id,
            'date' => $examDate->toDateString(),
            'status' => 'justifie',
            'marked_by' => $this->trainer->id,
        ]);
    }

    public function test_eligible_students_identifies_justified_absences(): void
    {
        $response = $this->actingAs($this->director)
            ->getJson(route('exams.rattrapages.eligible-students', $this->exam->id));

        $response->assertOk();
        $data = $response->json();

        $justifiedEntry = collect($data)->firstWhere('id', $this->studentJustified->id);
        $this->assertNotNull($justifiedEntry);
        $this->assertTrue($justifiedEntry['has_justified_absence']);
        $this->assertTrue($justifiedEntry['is_recommended']);
        $this->assertStringContainsString('Absence justifiée', $justifiedEntry['justification_motif']);

        $regularEntry = collect($data)->firstWhere('id', $this->studentRegular->id);
        $this->assertNotNull($regularEntry);
        $this->assertFalse($regularEntry['has_justified_absence']);
    }

    public function test_can_schedule_rattrapage_and_sends_notification(): void
    {
        Notification::fake();

        $scheduledAt = Carbon::now()->addDays(2)->setTime(10, 0);

        $response = $this->actingAs($this->trainer)
            ->postJson(route('exams.rattrapages.store', $this->exam->id), [
                'scheduled_at' => $scheduledAt->toISOString(),
                'duree_minutes' => 60,
                'titre' => 'Rattrapage Spécial Évaluation PHP',
                'instructions' => 'Veuillez vous connecter à l\'heure.',
                'student_ids' => [$this->studentJustified->id],
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('exam_rattrapages', [
            'exam_id' => $this->exam->id,
            'titre' => 'Rattrapage Spécial Évaluation PHP',
            'duree_minutes' => 60,
            'created_by' => $this->trainer->id,
        ]);

        $rattrapage = ExamRattrapage::where('exam_id', $this->exam->id)->first();
        $this->assertNotNull($rattrapage);

        $this->assertDatabaseHas('exam_rattrapage_user', [
            'exam_rattrapage_id' => $rattrapage->id,
            'user_id' => $this->studentJustified->id,
            'status' => 'scheduled',
        ]);

        Notification::assertSentTo(
            $this->studentJustified,
            NewExamRattrapageNotification::class
        );
    }

    public function test_expired_exam_does_not_auto_zero_justified_absent_student(): void
    {
        $response = $this->actingAs($this->director)
            ->getJson(route('exams.results', $this->exam->id));

        $response->assertOk();
        $results = $response->json();

        // The regular student (unexcused absent) is auto-completed with score 0
        $regularRes = collect($results)->firstWhere('user_id', $this->studentRegular->id);
        $this->assertNotNull($regularRes);
        $this->assertEquals(0, $regularRes['score']);
        $this->assertEquals('completed', $regularRes['status']);

        // The justified absent student is NOT auto-failed
        $justifiedRes = collect($results)->firstWhere('user_id', $this->studentJustified->id);
        $this->assertNotNull($justifiedRes);
        $this->assertNull($justifiedRes['score']);
        $this->assertNull($justifiedRes['status']);
        $this->assertTrue($justifiedRes['has_justified_absence']);
    }

    public function test_student_can_start_and_submit_exam_during_active_rattrapage(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureWithinPremises::class);

        // Schedule an active rattrapage (started 10 minutes ago, lasts 60 min)
        $rattrapage = ExamRattrapage::create([
            'exam_id' => $this->exam->id,
            'titre' => 'Rattrapage Actif',
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'duree_minutes' => 60,
            'created_by' => $this->trainer->id,
        ]);

        $rattrapage->users()->attach($this->studentJustified->id, [
            'status' => 'scheduled',
            'motif_justification' => 'Absence justifiée',
        ]);

        // Student exams list indicates active rattrapage
        $indexResponse = $this->actingAs($this->studentJustified)
            ->get(route('student.exams.index'));
        $indexResponse->assertOk();

        // Student can access exam show
        $showResponse = $this->actingAs($this->studentJustified)
            ->get(route('student.exams.show', $this->exam->id));
        $showResponse->assertOk();

        // Student starts exam
        $startResponse = $this->actingAs($this->studentJustified)
            ->postJson(route('student.exams.start', $this->exam->id));
        $startResponse->assertOk();

        $this->assertDatabaseHas('exam_results', [
            'exam_id' => $this->exam->id,
            'user_id' => $this->studentJustified->id,
            'is_rattrapage' => true,
            'exam_rattrapage_id' => $rattrapage->id,
            'status' => 'started',
        ]);

        // Student submits exam
        $submitResponse = $this->actingAs($this->studentJustified)
            ->post(route('student.exams.submit', $this->exam->id), [
                'answers' => [
                    $this->exam->questions->first()->id => 'strlen',
                ],
            ]);

        $submitResponse->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('exam_results', [
            'exam_id' => $this->exam->id,
            'user_id' => $this->studentJustified->id,
            'is_rattrapage' => true,
            'exam_rattrapage_id' => $rattrapage->id,
            'status' => 'completed',
        ]);

        // Pivot status updated to completed
        $this->assertDatabaseHas('exam_rattrapage_user', [
            'exam_rattrapage_id' => $rattrapage->id,
            'user_id' => $this->studentJustified->id,
            'status' => 'completed',
        ]);
    }

    public function test_can_delete_rattrapage(): void
    {
        $rattrapage = ExamRattrapage::create([
            'exam_id' => $this->exam->id,
            'titre' => 'Rattrapage à annuler',
            'scheduled_at' => Carbon::now()->addDay(),
            'duree_minutes' => 60,
            'created_by' => $this->trainer->id,
        ]);

        $response = $this->actingAs($this->trainer)
            ->deleteJson(route('exams.rattrapages.destroy', [
                'exam' => $this->exam->id,
                'rattrapage' => $rattrapage->id,
            ]));

        $response->assertOk();
        $this->assertDatabaseMissing('exam_rattrapages', ['id' => $rattrapage->id]);
    }

    public function test_student_with_prior_completed_result_can_open_and_pass_rattrapage_when_time_arrives(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureWithinPremises::class);

        // Student took regular exam, scored 6/20 (completed)
        $initialResult = ExamResult::create([
            'exam_id' => $this->exam->id,
            'user_id' => $this->studentRegular->id,
            'score' => 6,
            'status' => 'completed',
            'finished_at' => Carbon::now()->subDays(2),
            'answers' => [$this->exam->questions->first()->id => 'wrong_answer'],
        ]);

        // Trainer schedules rattrapage for this student, scheduled time has arrived (5 min ago)
        $rattrapage = ExamRattrapage::create([
            'exam_id' => $this->exam->id,
            'titre' => 'Rattrapage Note Insuffisante',
            'scheduled_at' => Carbon::now()->subMinutes(5),
            'duree_minutes' => 45,
            'created_by' => $this->trainer->id,
        ]);

        $rattrapage->users()->attach($this->studentRegular->id, [
            'status' => 'scheduled',
            'motif_justification' => 'Note inférieure à la moyenne',
        ]);

        // 1. Student checks /student/exams
        $indexResponse = $this->actingAs($this->studentRegular)
            ->get(route('student.exams.index'));
        $indexResponse->assertOk();
        $exams = $indexResponse->viewData('page')['props']['exams'];
        $examData = collect($exams)->firstWhere('id', $this->exam->id);
        $this->assertNotNull($examData);
        $this->assertTrue($examData['can_start']);
        $this->assertNotNull($examData['rattrapage_session']);
        $this->assertTrue($examData['rattrapage_session']['can_start']);

        // 2. Student opens exam /student/exams/{id} (must not be redirected with "Vous avez déjà passé cet examen")
        $showResponse = $this->actingAs($this->studentRegular)
            ->get(route('student.exams.show', $this->exam->id));
        $showResponse->assertOk();
        $showExam = $showResponse->viewData('page')['props']['exam'];
        $this->assertEquals(45, $showExam['duree_minutes']);
        // Saved answers for new rattrapage attempt should be empty
        $this->assertEmpty($showResponse->viewData('page')['props']['savedAnswers']);

        // 3. Student starts rattrapage exam
        $startResponse = $this->actingAs($this->studentRegular)
            ->postJson(route('student.exams.start', $this->exam->id));
        $startResponse->assertOk();

        $initialResult->refresh();
        $this->assertEquals('started', $initialResult->status);
        $this->assertTrue((bool)$initialResult->is_rattrapage);
        $this->assertEquals($rattrapage->id, $initialResult->exam_rattrapage_id);

        // 4. Student submits rattrapage exam
        $submitResponse = $this->actingAs($this->studentRegular)
            ->post(route('student.exams.submit', $this->exam->id), [
                'answers' => [
                    $this->exam->questions->first()->id => 'strlen',
                ],
            ]);
        $submitResponse->assertRedirect(route('student.dashboard'));

        $initialResult->refresh();
        $this->assertEquals('completed', $initialResult->status);
        $this->assertNotNull($initialResult->finished_at);

        // Pivot status updated to completed
        $this->assertDatabaseHas('exam_rattrapage_user', [
            'exam_rattrapage_id' => $rattrapage->id,
            'user_id' => $this->studentRegular->id,
            'status' => 'completed',
        ]);
    }

    public function test_student_dashboard_displays_active_rattrapage_even_if_regular_exam_ended(): void
    {
        // Student had regular exam result
        ExamResult::create([
            'exam_id' => $this->exam->id,
            'user_id' => $this->studentRegular->id,
            'score' => 5,
            'status' => 'completed',
            'finished_at' => Carbon::now()->subDays(2),
        ]);

        // Rattrapage scheduled for now
        $rattrapage = ExamRattrapage::create([
            'exam_id' => $this->exam->id,
            'titre' => 'Rattrapage Actif',
            'scheduled_at' => Carbon::now()->subMinutes(2),
            'duree_minutes' => 60,
            'created_by' => $this->trainer->id,
        ]);

        $rattrapage->users()->attach($this->studentRegular->id, [
            'status' => 'scheduled',
            'motif_justification' => 'Session de rattrapage',
        ]);

        $dashboardResponse = $this->actingAs($this->studentRegular)
            ->get(route('student.dashboard'));
        $dashboardResponse->assertOk();

        $upcomingExams = $dashboardResponse->viewData('page')['props']['upcomingExams'];
        $found = collect($upcomingExams)->firstWhere('id', $this->exam->id);
        $this->assertNotNull($found);
        $this->assertNotNull($found['rattrapage_session']);
        $this->assertTrue($found['rattrapage_session']['can_start']);
    }

    public function test_student_cannot_retake_rattrapage_after_completing_it(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureWithinPremises::class);

        $rattrapage = ExamRattrapage::create([
            'exam_id' => $this->exam->id,
            'titre' => 'Rattrapage Terminé',
            'scheduled_at' => Carbon::now()->subMinutes(10),
            'duree_minutes' => 60,
            'created_by' => $this->trainer->id,
        ]);

        // Pivot status is completed
        $rattrapage->users()->attach($this->studentJustified->id, [
            'status' => 'completed',
            'motif_justification' => 'Absence justifiée',
        ]);

        // Result marked completed for rattrapage
        ExamResult::create([
            'exam_id' => $this->exam->id,
            'user_id' => $this->studentJustified->id,
            'score' => 15,
            'status' => 'completed',
            'is_rattrapage' => true,
            'exam_rattrapage_id' => $rattrapage->id,
        ]);

        // Accessing show should redirect back
        $showResponse = $this->actingAs($this->studentJustified)
            ->get(route('student.exams.show', $this->exam->id));
        $showResponse->assertRedirect(route('student.exams.index'));

        // Calling start should return 403
        $startResponse = $this->actingAs($this->studentJustified)
            ->postJson(route('student.exams.start', $this->exam->id));
        $startResponse->assertStatus(403);
    }
}
