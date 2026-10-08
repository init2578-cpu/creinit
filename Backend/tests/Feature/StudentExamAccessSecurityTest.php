<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Group;
use App\Models\Module;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $otherStudent;
    private Group $group;
    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Directeur']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secrétaire']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Apprenant']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Formateur']);

        $this->withoutMiddleware(\App\Http\Middleware\EnsureWithinPremises::class);

        $this->module = Module::create([
            'code_module' => 'SEC101',
            'titre' => 'Sécurité Informatique',
            'quota_heures' => 30,
        ]);

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $this->group = Group::create([
            'nom_groupe' => 'Groupe Sécurité',
            'module_id' => $this->module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
        ]);

        $this->student = User::factory()->create();
        $this->student->assignRole('Apprenant');
        $this->group->students()->attach($this->student->id);

        $this->otherStudent = User::factory()->create();
        $this->otherStudent->assignRole('Apprenant');
    }

    public function test_student_not_enrolled_cannot_view_exam_in_index(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Final Réseau',
            'type' => 'online',
            'scheduled_at' => now()->subHours(5),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
            'are_grades_published' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        // otherStudent is not in the group
        $response = $this->actingAs($this->otherStudent)->get(route('student.exams.index'));
        $response->assertOk();

        $exams = $response->original->getData()['page']['props']['exams'];
        $this->assertEmpty($exams);
    }

    public function test_student_not_enrolled_cannot_access_exam_questions_via_show(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Final Réseau',
            'type' => 'online',
            'scheduled_at' => now()->subMinutes(10),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        $response = $this->actingAs($this->otherStudent)->get(route('student.exams.show', $exam->id));
        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error');
    }

    public function test_student_not_enrolled_cannot_access_correction_even_if_grades_published(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Passé',
            'type' => 'online',
            'scheduled_at' => now()->subDays(2),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
            'are_grades_published' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        $response = $this->actingAs($this->otherStudent)->get(route('student.exams.result', $exam->id));
        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', "Cet examen n'est pas accessible.");
    }

    public function test_index_page_does_not_leak_questions_and_correct_answers(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Avec Questions',
            'type' => 'online',
            'scheduled_at' => now()->addDay(),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        $question = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Quelle est la commande pour lister les fichiers ?',
            'points' => 5,
            'type' => 'qcm',
            'ordre' => 1,
        ]);

        Option::create([
            'question_id' => $question->id,
            'texte' => 'ls',
            'is_correct' => true,
        ]);

        Option::create([
            'question_id' => $question->id,
            'texte' => 'cd',
            'is_correct' => false,
        ]);

        $response = $this->actingAs($this->student)->get(route('student.exams.index'));
        $response->assertOk();

        $exams = $response->original->getData()['page']['props']['exams'];
        $this->assertCount(1, $exams);
        $firstExam = $exams[0];

        // Questions relation must NOT be loaded on the index
        $this->assertFalse(isset($firstExam->questions) && !empty($firstExam->questions));
    }

    public function test_student_who_did_not_take_exam_cannot_see_correction_even_if_exam_has_ended_and_grades_published(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Passé Non Composé',
            'type' => 'online',
            'scheduled_at' => now()->subDays(1),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
            'are_grades_published' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        // Student was enrolled in group, but has NO ExamResult (absent / did not compose)
        $response = $this->actingAs($this->student)->get(route('student.exams.result', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', "Vous n'avez pas passé cet examen. Vous ne pouvez pas accéder aux questions ni à la correction.");
    }

    public function test_student_with_incomplete_started_attempt_cannot_see_correction_when_exam_ended_and_grades_published(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Passé Non Terminé',
            'type' => 'online',
            'scheduled_at' => now()->subDays(1),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
            'are_grades_published' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        // Attempt was started but never submitted/completed
        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $this->student->id,
            'status' => 'started',
            'score' => null,
            'started_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.exams.result', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', "Vous n'avez pas passé cet examen. Vous ne pouvez pas accéder aux questions ni à la correction.");
    }

    public function test_student_who_completed_exam_can_see_correction_when_grades_published(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Complété avec Succès',
            'type' => 'online',
            'scheduled_at' => now()->subDays(1),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
            'are_grades_published' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Quelle commande affiche la date ?',
            'points' => 20,
            'type' => 'qcm',
            'ordre' => 1,
        ]);

        // Student took and completed the exam
        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $this->student->id,
            'status' => 'completed',
            'score' => 18.00,
            'started_at' => now()->subDays(1),
            'finished_at' => now()->subDays(1)->addMinutes(45),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.exams.result', $exam->id));

        $response->assertOk();
        $this->assertEquals('Student/ExamResult', $response->original->getData()['page']['component']);
    }

    public function test_student_cannot_view_exam_questions_after_exam_has_ended(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen Expiré',
            'type' => 'online',
            'scheduled_at' => now()->subHours(3),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
        ]);
        $exam->groups()->attach($this->group->id);

        // Student did not participate and exam is past
        $response = $this->actingAs($this->student)->get(route('student.exams.show', $exam->id));

        $response->assertRedirect(route('student.exams.index'));
        $response->assertSessionHas('error', "Cet examen n'est pas accessible actuellement.");
    }

    public function test_taking_official_exam_hides_correct_options_and_expected_answers(): void
    {
        $exam = Exam::create([
            'module_id' => $this->module->id,
            'titre' => 'Examen En Cours',
            'type' => 'online',
            'scheduled_at' => now()->subMinutes(5),
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
            'is_practice' => false,
        ]);
        $exam->groups()->attach($this->group->id);

        $qcm = Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Protocole sécurisé web ?',
            'points' => 10,
            'type' => 'qcm',
            'ordre' => 1,
        ]);

        Option::create([
            'question_id' => $qcm->id,
            'texte' => 'HTTPS',
            'is_correct' => true,
        ]);

        Option::create([
            'question_id' => $qcm->id,
            'texte' => 'HTTP',
            'is_correct' => false,
        ]);

        Question::create([
            'exam_id' => $exam->id,
            'enonce' => 'Expliquer le chiffrement symétrique.',
            'expected_answer' => 'Même clé pour chiffrer et déchiffrer',
            'points' => 10,
            'type' => 'open',
            'ordre' => 2,
        ]);

        // Student is within time window and GPS check bypassed (or provide coords)
        $response = $this->actingAs($this->student)->get(route('student.exams.show', [
            'exam' => $exam->id,
            'latitude' => 14.6937,
            'longitude' => -17.4441,
        ]));

        $response->assertOk();
        $examData = $response->original->getData()['page']['props']['exam'];
        $questions = is_array($examData) ? $examData['questions'] : $examData->questions;

        // Verify that expected_answer is hidden
        $openQ = collect($questions)->firstWhere('type', 'open');
        $openQArray = is_array($openQ) ? $openQ : $openQ->toArray();
        $this->assertArrayNotHasKey('expected_answer', $openQArray);

        // Verify that is_correct is hidden on options
        $qcmQ = collect($questions)->firstWhere('type', 'qcm');
        $qcmOptions = is_array($qcmQ) ? $qcmQ['options'] : $qcmQ->options;
        foreach ($qcmOptions as $opt) {
            $optArray = is_array($opt) ? $opt : $opt->toArray();
            $this->assertArrayNotHasKey('is_correct', $optArray);
        }
    }
}
