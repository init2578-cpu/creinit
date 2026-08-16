<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainerExamRestrictionTest extends TestCase
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

    public function test_trainer_cannot_modify_exam_created_by_another_user(): void
    {
        $trainer1 = User::factory()->create();
        $trainer1->assignRole('Formateur');

        $trainer2 = User::factory()->create();
        $trainer2->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer1->id,
            'titre' => 'Examen Formateur 1',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        // Trainer 2 tries to update
        $response = $this->actingAs($trainer2)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Titre Modifié',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);
        $response->assertStatus(403);

        // Trainer 2 tries to delete
        $response = $this->actingAs($trainer2)->delete(route('exams.destroy', $exam->id));
        $response->assertStatus(403);

        // Trainer 2 tries to duplicate
        $response = $this->actingAs($trainer2)->post(route('exams.duplicate', $exam->id));
        $response->assertStatus(403);

        // Trainer 2 tries to enter grades
        $response = $this->actingAs($trainer2)->post(route('exams.enter-grades', $exam->id), [
            'grades' => [],
        ]);
        $response->assertStatus(403);

        // Trainer 2 tries to get results
        $response = $this->actingAs($trainer2)->get(route('exams.results', $exam->id));
        $response->assertStatus(403);

        // Trainer 2 tries to store question
        $response = $this->actingAs($trainer2)->post(route('exams.questions.store', $exam->id), [
            'enonce' => 'Question test',
            'points' => 5,
            'type' => 'open',
        ]);
        $response->assertStatus(403);

        // Trainer 1 (creator) can update
        $response = $this->actingAs($trainer1)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Titre Modifié Par Créateur',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'titre' => 'Titre Modifié Par Créateur']);
    }

    public function test_trainer_only_sees_their_own_exams_in_index(): void
    {
        $trainer1 = User::factory()->create();
        $trainer1->assignRole('Formateur');

        $trainer2 = User::factory()->create();
        $trainer2->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M102',
            'titre' => 'Module Test 2',
            'quota_heures' => 30,
        ]);

        $exam1 = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer1->id,
            'titre' => 'Examen Formateur 1',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $exam2 = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer2->id,
            'titre' => 'Examen Formateur 2',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $response = $this->actingAs($trainer1)->get(route('exams.index'));
        $response->assertStatus(200);

        $pageExams = $response->inertiaPage()['props']['exams'];
        $examIds = collect($pageExams)->pluck('id')->toArray();

        $this->assertContains($exam1->id, $examIds);
        $this->assertNotContains($exam2->id, $examIds);
    }

    public function test_assistant_and_tutor_can_see_and_manage_each_others_exams(): void
    {
        Role::firstOrCreate(['name' => 'Stagiaire']);

        $tutor = User::factory()->create();
        $tutor->assignRole('Formateur');

        $assistant = User::factory()->create();
        $assistant->assignRole('Stagiaire');

        \App\Models\InternshipRecord::create([
            'user_id' => $assistant->id,
            'internship_type' => 'course_assistant',
            'tuteur_id' => $tutor->id,
        ]);

        $module = Module::create([
            'code_module' => 'M103',
            'titre' => 'Module Test 3',
            'quota_heures' => 30,
        ]);

        $examTutor = Exam::create([
            'module_id' => $module->id,
            'user_id' => $tutor->id,
            'titre' => 'Examen du Tuteur',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $examAssistant = Exam::create([
            'module_id' => $module->id,
            'user_id' => $assistant->id,
            'titre' => 'Examen de l Assistant',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        // Assistant sees both exams in index
        $response = $this->actingAs($assistant)->get(route('exams.index'));
        $response->assertStatus(200);
        $examIds = collect($response->inertiaPage()['props']['exams'])->pluck('id')->toArray();
        $this->assertContains($examTutor->id, $examIds);
        $this->assertContains($examAssistant->id, $examIds);

        // Tutor sees both exams in index
        $response = $this->actingAs($tutor)->get(route('exams.index'));
        $response->assertStatus(200);
        $examIds = collect($response->inertiaPage()['props']['exams'])->pluck('id')->toArray();
        $this->assertContains($examTutor->id, $examIds);
        $this->assertContains($examAssistant->id, $examIds);

        // Assistant can update Tutor's exam
        $response = $this->actingAs($assistant)->put(route('exams.update', $examTutor->id), [
            'module_id' => $module->id,
            'titre' => 'Titre mis a jour par Assistant',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);
        $response->assertStatus(302);
    }
}
