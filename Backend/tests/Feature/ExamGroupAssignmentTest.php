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
}
