<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExamGroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup default roles if they do not exist
        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Apprenant']);
        Role::firstOrCreate(['name' => 'Formateur']);
    }

    public function test_director_can_create_exam_with_groups()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40
        ]);

        $group1 = Group::create([
            'nom_groupe' => 'Groupe A',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026'
        ]);

        $group2 = Group::create([
            'nom_groupe' => 'Groupe B',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026'
        ]);

        $response = $this->actingAs($director)->post(route('exams.store'), [
            'module_id' => $module->id,
            'titre' => 'Examen Test Groupes',
            'type' => 'online',
            'description' => 'Test description',
            'duree_minutes' => 60,
            'total_points' => 20,
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'group_ids' => [$group1->id, $group2->id],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exams', [
            'titre' => 'Examen Test Groupes',
            'is_approved' => true,
        ]);

        $exam = Exam::where('titre', 'Examen Test Groupes')->first();
        $this->assertCount(2, $exam->groups);
        $this->assertTrue($exam->groups->contains($group1));
        $this->assertTrue($exam->groups->contains($group2));
    }

    public function test_director_can_update_exam_groups()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M103',
            'titre' => 'Module Test Update',
            'quota_heures' => 40
        ]);

        $group1 = Group::create([
            'nom_groupe' => 'Groupe E',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026'
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen Original',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($director)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Examen Modifie',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'group_ids' => [$group1->id],
        ]);

        $response->assertSessionHasNoErrors();
        $exam->refresh();
        $this->assertEquals('Examen Modifie', $exam->titre);
        $this->assertCount(1, $exam->groups);
        $this->assertTrue($exam->groups->contains($group1));
    }

    public function test_update_exam_with_full_frontend_payload()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M104',
            'titre' => 'Module Test Full',
            'quota_heures' => 40
        ]);

        $group1 = Group::create([
            'nom_groupe' => 'Groupe F',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026'
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen Original',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($director)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Examen Modifie Full',
            'type' => 'online',
            'description' => 'some description',
            'duree_minutes' => 120,
            'total_points' => 40,
            'scheduled_at' => '2026-06-15T10:00',
            'scheduled_end' => '2026-06-15T12:00',
            'document' => null,
            'group_ids' => [$group1->id],
        ]);

        $response->assertSessionHasNoErrors();
        $exam->refresh();
        $this->assertEquals('Examen Modifie Full', $exam->titre);
        $this->assertEquals(120, $exam->duree_minutes);
        $this->assertCount(1, $exam->groups);
    }

    public function test_student_only_sees_exams_assigned_to_their_group()
    {
        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M102',
            'titre' => 'Module Test 2',
            'quota_heures' => 45
        ]);

        $group1 = Group::create([
            'nom_groupe' => 'Groupe C',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026'
        ]);

        $group2 = Group::create([
            'nom_groupe' => 'Groupe D',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026'
        ]);

        // Enroll student in group 1
        $group1->students()->attach($student->id);

        // Create exam assigned to group 1
        $exam1 = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen 1',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
        ]);
        $exam1->groups()->attach($group1->id);

        // Create exam assigned only to group 2
        $exam2 = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen 2',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => true,
            'is_active' => true,
        ]);
        $exam2->groups()->attach($group2->id);

        // Access student exams index via Inertia
        $response = $this->actingAs($student)->get(route('student.exams.index'));
        $response->assertOk();

        // Retrieve exams from Inertia page prop
        $exams = $response->original->getData()['page']['props']['exams'];
        
        $examIds = collect($exams)->pluck('id');
        $this->assertTrue($examIds->contains($exam1->id));
        $this->assertFalse($examIds->contains($exam2->id));
    }

    public function test_trainer_can_assign_own_groups_but_not_others()
    {
        $trainerA = User::factory()->create();
        $trainerA->assignRole('Formateur');

        $trainerB = User::factory()->create();
        $trainerB->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M105',
            'titre' => 'Module Test Group Permissions',
            'quota_heures' => 40
        ]);

        $groupA = Group::create([
            'nom_groupe' => 'Groupe Trainer A',
            'module_id' => $module->id,
            'formateur_id' => $trainerA->id,
            'annee_academique' => '2025-2026'
        ]);

        $groupB = Group::create([
            'nom_groupe' => 'Groupe Trainer B',
            'module_id' => $module->id,
            'formateur_id' => $trainerB->id,
            'annee_academique' => '2025-2026'
        ]);

        // 1. Test creation by Trainer A
        $response = $this->actingAs($trainerA)->post(route('exams.store'), [
            'module_id' => $module->id,
            'titre' => 'Examen Test Trainer',
            'type' => 'online',
            'description' => 'Test description',
            'duree_minutes' => 60,
            'total_points' => 20,
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'group_ids' => [$groupA->id, $groupB->id],
        ]);

        $response->assertSessionHasNoErrors();
        $exam = Exam::where('titre', 'Examen Test Trainer')->first();
        $this->assertNotNull($exam);
        
        // Assert only Group A was assigned, Group B was ignored because it belongs to Trainer B
        $this->assertTrue($exam->groups->contains($groupA));
        $this->assertFalse($exam->groups->contains($groupB));

        // 2. Test update by Trainer A
        // Suppose a director assigned both groups to the exam
        $exam->groups()->sync([$groupA->id, $groupB->id]);

        $response = $this->actingAs($trainerA)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Examen Test Trainer Modifie',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'group_ids' => [], // Trainer A tries to clear all their groups
        ]);

        $response->assertSessionHasNoErrors();
        $exam->refresh();

        // Assert Group A is detached (because it belonged to Trainer A and they unchecked it)
        // Assert Group B remains attached (because it belongs to Trainer B and Trainer A cannot modify it)
        $this->assertFalse($exam->groups->contains($groupA));
        $this->assertTrue($exam->groups->contains($groupB));
    }

    public function test_staff_can_download_proposed_exam_document()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $module = Module::create([
            'code_module' => 'M106',
            'titre' => 'Module Test Download',
            'quota_heures' => 40
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen Test Download',
            'type' => 'paper',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => false, // Not approved yet!
            'document_path' => 'exams/test-doc.pdf',
        ]);

        // Create a fake file to ensure file_exists passes
        \Illuminate\Support\Facades\Storage::disk('public')->put('exams/test-doc.pdf', 'fake pdf content');

        $response = $this->actingAs($director)->get(route('exams.download-file', $exam->id));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline; filename="test-doc.pdf"');

        // Cleanup
        \Illuminate\Support\Facades\Storage::disk('public')->delete('exams/test-doc.pdf');
    }

    public function test_student_cannot_download_unapproved_exam_document_via_admin_route()
    {
        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'M107',
            'titre' => 'Module Test Download Denied',
            'quota_heures' => 40
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen Test Download Denied',
            'type' => 'paper',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_approved' => false,
            'document_path' => 'exams/test-doc-denied.pdf',
        ]);

        // Access via admin route should fail due to middleware (403 or redirect based on middleware setup)
        // Usually, role middleware returns 403 Forbidden.
        $response = $this->actingAs($student)->get(route('exams.download-file', $exam->id));
        $response->assertStatus(403);
    }
}
