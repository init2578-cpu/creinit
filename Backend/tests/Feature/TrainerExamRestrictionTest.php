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

    public function test_trainer_only_sees_their_own_groups_and_modules_in_index(): void
    {
        $trainer1 = User::factory()->create();
        $trainer1->assignRole('Formateur');

        $trainer2 = User::factory()->create();
        $trainer2->assignRole('Formateur');

        $module1 = Module::create([
            'code_module' => 'M105',
            'titre' => 'Module Test 5',
            'quota_heures' => 30,
        ]);

        $module2 = Module::create([
            'code_module' => 'M106',
            'titre' => 'Module Test 6',
            'quota_heures' => 30,
        ]);

        $group1 = \App\Models\Group::create([
            'nom_groupe' => 'Groupe T1',
            'module_id' => $module1->id,
            'formateur_id' => $trainer1->id,
            'annee_academique' => '2025-2026',
        ]);

        $group2 = \App\Models\Group::create([
            'nom_groupe' => 'Groupe T2',
            'module_id' => $module2->id,
            'formateur_id' => $trainer2->id,
            'annee_academique' => '2025-2026',
        ]);

        $response = $this->actingAs($trainer1)->get(route('exams.index'));
        $response->assertStatus(200);

        $pageGroups = $response->inertiaPage()['props']['groups'];
        $groupIds = collect($pageGroups)->pluck('id')->toArray();

        $this->assertContains($group1->id, $groupIds);
        $this->assertNotContains($group2->id, $groupIds);

        $pageModules = $response->inertiaPage()['props']['modules'];
        $moduleIds = collect($pageModules)->pluck('id')->toArray();

        $this->assertContains($module1->id, $moduleIds);
        $this->assertNotContains($module2->id, $moduleIds);
    }

    public function test_trainer_with_no_groups_sees_no_groups_and_no_modules_in_index(): void
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $otherTrainer = User::factory()->create();
        $otherTrainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M107',
            'titre' => 'Module Test 7',
            'quota_heures' => 30,
        ]);

        $group = \App\Models\Group::create([
            'nom_groupe' => 'Groupe Other',
            'module_id' => $module->id,
            'formateur_id' => $otherTrainer->id,
            'annee_academique' => '2025-2026',
        ]);

        $response = $this->actingAs($trainer)->get(route('exams.index'));
        $response->assertStatus(200);

        $pageGroups = $response->inertiaPage()['props']['groups'];
        $this->assertCount(0, $pageGroups);

        $pageModules = $response->inertiaPage()['props']['modules'];
        $this->assertCount(0, $pageModules);
    }

    public function test_director_can_duplicate_exam_and_assign_to_another_trainer(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer1 = User::factory()->create();
        $trainer1->assignRole('Formateur');

        $trainer2 = User::factory()->create();
        $trainer2->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M108',
            'titre' => 'Module Test 8',
            'quota_heures' => 30,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer1->id,
            'titre' => 'Examen Formateur 1 Original',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        // Duplicate with no user_id (retains original owner)
        $response = $this->actingAs($director)->post(route('exams.duplicate', $exam->id));
        $response->assertStatus(302);
        $duplicatedExam = Exam::where('titre', 'Examen Formateur 1 Original - Copie')->first();
        $this->assertNotNull($duplicatedExam);
        $this->assertEquals($trainer1->id, $duplicatedExam->user_id);

        // Duplicate and explicitly assign to trainer2
        $response = $this->actingAs($director)->post(route('exams.duplicate', $exam->id), [
            'user_id' => $trainer2->id,
        ]);
        $response->assertStatus(302);
        $duplicatedExam2 = Exam::where('titre', 'Examen Formateur 1 Original - Copie')
            ->where('user_id', $trainer2->id)
            ->first();
        $this->assertNotNull($duplicatedExam2);
    }

    public function test_director_can_update_exam_user_id_to_another_trainer(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer1 = User::factory()->create();
        $trainer1->assignRole('Formateur');

        $trainer2 = User::factory()->create();
        $trainer2->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M109',
            'titre' => 'Module Test 9',
            'quota_heures' => 30,
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer1->id,
            'titre' => 'Examen A Modifier Owner',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);

        $response = $this->actingAs($director)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Examen A Modifier Owner Modifie',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'user_id' => $trainer2->id,
        ]);

        $response->assertStatus(302);
        $exam->refresh();
        $this->assertEquals($trainer2->id, $exam->user_id);
    }

    public function test_trainer_receives_notification_when_exam_is_assigned_by_director(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M110',
            'titre' => 'Module Test 10',
            'quota_heures' => 30,
        ]);

        $response = $this->actingAs($director)->post(route('exams.store'), [
            'module_id' => $module->id,
            'titre' => 'Examen Directement Attribué',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'user_id' => $trainer->id,
        ]);

        $response->assertStatus(302);
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $trainer,
            \App\Notifications\ExamAssignedNotification::class
        );
    }
}

