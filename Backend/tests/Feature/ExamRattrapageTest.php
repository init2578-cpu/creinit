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
}
