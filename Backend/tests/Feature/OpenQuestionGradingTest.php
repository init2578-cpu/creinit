<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use App\Notifications\ExamResultGradedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpenQuestionGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Formateur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Apprenant']);
    }

    public function test_authorized_trainer_can_grade_open_questions_and_score_recalculates(): void
    {
        Notification::fake();

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'M102',
            'titre' => 'Module Open Test',
            'quota_heures' => 30,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen avec Question Ouverte',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $q1 = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'QCM Question',
            'type' => 'qcm',
            'points' => 10,
        ]);

        $opt1 = $q1->options()->create(['texte' => 'Option A', 'is_correct' => true]);
        $q1->options()->create(['texte' => 'Option B', 'is_correct' => false]);

        $q2 = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Expliquer le concept X',
            'type' => 'open',
            'points' => 10,
            'expected_answer' => 'Explication de X',
        ]);

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'answers' => [
                $q1->id => $opt1->id,
                $q2->id => 'Ma réponse rédigée',
            ],
            'score' => 10, // QCM points initial
            'status' => 'completed',
            'is_graded' => true,
        ]);

        $response = $this->actingAs($trainer)->post(route('exams.grade-open-questions', [
            'exam' => $exam->id,
            'user' => $student->id,
        ]), [
            'open_question_scores' => [
                $q2->id => 8,
            ],
        ]);

        $response->assertStatus(302);

        $result->refresh();
        // Total points earned = 10 (QCM) + 8 (Open) = 18 out of 20 total_points. (18/20)*20 = 18.0
        $this->assertEquals(18.0, (float) $result->score);
        $this->assertEquals(8, $result->answers['_question_scores'][$q2->id]);

        Notification::assertSentTo($student, ExamResultGradedNotification::class);
    }

    public function test_unauthorized_trainer_cannot_grade_open_questions(): void
    {
        $trainer1 = User::factory()->create();
        $trainer1->assignRole('Formateur');

        $trainer2 = User::factory()->create();
        $trainer2->assignRole('Formateur');

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'M103',
            'titre' => 'Module Test 2',
            'quota_heures' => 30,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer1->id,
            'titre' => 'Examen Formateur 1',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $q2 = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Question ouverte',
            'type' => 'open',
            'points' => 20,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'answers' => [$q2->id => 'Réponse'],
            'score' => 0,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($trainer2)->post(route('exams.grade-open-questions', [
            'exam' => $exam->id,
            'user' => $student->id,
        ]), [
            'open_question_scores' => [
                $q2->id => 15,
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_director_can_grade_open_questions(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'M104',
            'titre' => 'Module Test 3',
            'quota_heures' => 30,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Formateur',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $q = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Question ouverte',
            'type' => 'open',
            'points' => 20,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'answers' => [$q->id => 'Réponse apprenant'],
            'score' => 0,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($director)->post(route('exams.grade-open-questions', [
            'exam' => $exam->id,
            'user' => $student->id,
        ]), [
            'open_question_scores' => [
                $q->id => 17.5,
            ],
        ]);

        $response->assertStatus(302);
    }

    public function test_secretary_cannot_grade_open_questions(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'M105',
            'titre' => 'Module Test 4',
            'quota_heures' => 30,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Formateur',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $q = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Question ouverte',
            'type' => 'open',
            'points' => 20,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'answers' => [$q->id => 'Réponse'],
            'score' => 0,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($secretary)->post(route('exams.grade-open-questions', [
            'exam' => $exam->id,
            'user' => $student->id,
        ]), [
            'open_question_scores' => [
                $q->id => 15,
            ],
        ]);

        $response->assertStatus(403);
    }
}
