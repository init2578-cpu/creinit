<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticularStudentTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $trainer;
    protected User $secretary;
    protected User $particularStudent;
    protected User $cohortStudent;
    protected Module $module;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Formateur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Apprenant']);

        $this->director = User::factory()->create();
        $this->director->assignRole('Directeur');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('Formateur');

        $this->secretary = User::factory()->create();
        $this->secretary->assignRole('Secrétaire');

        $this->module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Gestion de Projet',
            'quota_heures' => 30,
        ]);

        $this->group = Group::create([
            'nom_groupe' => 'Cohorte Standard 2026',
            'module_id' => $this->module->id,
            'formateur_id' => $this->trainer->id,
            'annee_academique' => '2026',
            'status' => 'active',
        ]);

        $this->cohortStudent = User::factory()->create(['name' => 'Moussa Cohorte', 'is_particulier' => false]);
        $this->cohortStudent->assignRole('Apprenant');
        $this->group->students()->attach($this->cohortStudent->id);

        $this->particularStudent = User::factory()->create(['name' => 'Aissatou Particulier', 'is_particulier' => true]);
        $this->particularStudent->assignRole('Apprenant');
        $this->particularStudent->particularModules()->attach($this->module->id);
    }

    public function test_trainer_and_secretary_cannot_see_particular_students_in_students_list(): void
    {
        // Director sees both
        $response = $this->actingAs($this->director)->get(route('students.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('students', 2)
            ->where('is_directeur', true)
        );

        // Trainer sees only cohort student
        $response = $this->actingAs($this->trainer)->get(route('students.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('students', 1)
            ->where('students.0.name', 'Moussa Cohorte')
            ->where('is_directeur', false)
        );

        // Secretary sees only cohort student
        $response = $this->actingAs($this->secretary)->get(route('students.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('students', 1)
            ->where('students.0.name', 'Moussa Cohorte')
            ->where('is_directeur', false)
        );
    }

    public function test_only_director_can_create_particular_student_with_modules(): void
    {
        // Director creates particular student
        $response = $this->actingAs($this->director)->post(route('students.store'), [
            'name' => 'Ibrahima Particulier',
            'email' => 'ibrahima@example.com',
            'password' => 'password123',
            'telephone' => '771234567',
            'is_particulier' => true,
            'particular_module_ids' => [$this->module->id],
        ]);
        $response->assertRedirect();

        $ibrahima = User::where('email', 'ibrahima@example.com')->first();
        $this->assertNotNull($ibrahima);
        $this->assertTrue((bool) $ibrahima->is_particulier);
        $this->assertTrue($ibrahima->particularModules->contains($this->module->id));

        // Non-director cannot set is_particulier = true
        $response = $this->actingAs($this->secretary)->post(route('students.store'), [
            'name' => 'Test Regular',
            'email' => 'regular@example.com',
            'password' => 'password123',
            'telephone' => '770000000',
            'is_particulier' => true, // Attempt to set
            'particular_module_ids' => [$this->module->id],
        ]);
        $response->assertRedirect();

        $regular = User::where('email', 'regular@example.com')->first();
        $this->assertNotNull($regular);
        $this->assertFalse((bool) $regular->is_particulier);
        $this->assertCount(0, $regular->particularModules);
    }

    public function test_non_director_cannot_modify_or_delete_particular_student(): void
    {
        // Trainer cannot modify particular student
        $response = $this->actingAs($this->trainer)->put(route('students.update', $this->particularStudent->id), [
            'name' => 'Hacked Name',
            'email' => $this->particularStudent->email,
            'is_active' => true,
        ]);
        $response->assertForbidden();

        // Trainer cannot delete particular student
        $response = $this->actingAs($this->trainer)->delete(route('students.destroy', $this->particularStudent->id));
        $response->assertForbidden();

        // Director can update particular student
        $response = $this->actingAs($this->director)->put(route('students.update', $this->particularStudent->id), [
            'name' => 'Aissatou Particulier Updated',
            'email' => $this->particularStudent->email,
            'is_active' => true,
            'is_particulier' => true,
            'particular_module_ids' => [$this->module->id],
        ]);
        $response->assertRedirect();
        $this->assertEquals('Aissatou Particulier Updated', $this->particularStudent->fresh()->name);
    }

    public function test_only_director_can_create_and_view_exclusive_particular_exam(): void
    {
        // Director creates exam exclusively for particular student without cohort groups
        $response = $this->actingAs($this->director)->post(route('exams.store'), [
            'module_id' => $this->module->id,
            'titre' => 'Examen Spécial Direction',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'group_ids' => [],
            'particular_student_ids' => [$this->particularStudent->id],
        ]);
        $response->assertRedirect();

        $exam = Exam::where('titre', 'Examen Spécial Direction')->first();
        $this->assertNotNull($exam);
        $this->assertTrue((bool) $exam->is_exclusive_directeur);
        $this->assertTrue($exam->particularStudents->contains($this->particularStudent->id));

        // Director sees exam
        $response = $this->actingAs($this->director)->get(route('exams.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('exams', 1)
            ->where('exams.0.titre', 'Examen Spécial Direction')
        );

        // Trainer cannot see exam
        $response = $this->actingAs($this->trainer)->get(route('exams.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('exams', 0)
        );

        // Secretary cannot see exam
        $response = $this->actingAs($this->secretary)->get(route('exams.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('exams', 0)
        );
    }

    public function test_particular_student_can_take_exam_while_cohort_student_cannot(): void
    {
        $exam = Exam::create([
            'user_id' => $this->director->id,
            'module_id' => $this->module->id,
            'titre' => 'Examen Particulier En Ligne',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_exclusive_directeur' => true,
            'is_approved' => true,
        ]);
        $exam->particularStudents()->attach($this->particularStudent->id);

        // Particular student can access exam in index
        $response = $this->actingAs($this->particularStudent)->get(route('student.exams.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('exams', 1)
            ->where('exams.0.titre', 'Examen Particulier En Ligne')
        );

        // Cohort student cannot see exam in index
        $response = $this->actingAs($this->cohortStudent)->get(route('student.exams.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('exams', 0)
        );

        // Cohort student directly accessing show endpoint is redirected with error
        $response = $this->actingAs($this->cohortStudent)->get(route('student.exams.show', $exam->id));
        $response->assertRedirect(route('student.exams.index'));

        // Particular student can access show endpoint
        $response = $this->actingAs($this->particularStudent)->get(route('student.exams.show', $exam->id));
        $response->assertOk();
    }

    public function test_only_director_can_grade_particular_student(): void
    {
        $exam = Exam::create([
            'user_id' => $this->director->id,
            'module_id' => $this->module->id,
            'titre' => 'Examen Particulier Notation',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'is_exclusive_directeur' => true,
            'is_approved' => true,
        ]);
        $exam->particularStudents()->attach($this->particularStudent->id);

        // Trainer cannot enter grades on exclusive exam
        $response = $this->actingAs($this->trainer)->post(route('exams.enter-grades', $exam->id), [
            'grades' => [
                ['user_id' => $this->particularStudent->id, 'score' => 15],
            ],
        ]);
        $response->assertForbidden();

        // Director can enter grades
        $response = $this->actingAs($this->director)->post(route('exams.enter-grades', $exam->id), [
            'grades' => [
                ['user_id' => $this->particularStudent->id, 'score' => 16.5],
            ],
        ]);
        $response->assertRedirect();

        $result = ExamResult::where('exam_id', $exam->id)->where('user_id', $this->particularStudent->id)->first();
        $this->assertNotNull($result);
        $this->assertEquals(16.5, (float) $result->score);
    }

    public function test_only_director_can_generate_certificate_for_particular_student(): void
    {
        // Non-director cannot generate certificate for particular student
        $response = $this->actingAs($this->secretary)->post(route('certificates.generate', [
            'student' => $this->particularStudent->id,
            'module' => $this->module->id,
        ]), [
            'score' => 17,
            'type' => 'reussite',
        ]);
        $response->assertForbidden();

        // Director can generate certificate
        $response = $this->actingAs($this->director)->post(route('certificates.generate', [
            'student' => $this->particularStudent->id,
            'module' => $this->module->id,
        ]), [
            'score' => 17,
            'type' => 'reussite',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('certificates', [
            'user_id' => $this->particularStudent->id,
            'module_id' => $this->module->id,
            'type' => 'reussite',
            'score' => 17,
        ]);
    }
}
