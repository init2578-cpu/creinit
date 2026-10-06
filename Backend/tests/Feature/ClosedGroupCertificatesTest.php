<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClosedGroupCertificatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
        Storage::fake('local');
        Notification::fake();

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Apprenant']);
        Role::firstOrCreate(['name' => 'Formateur']);
    }

    public function test_closed_group_students_appear_in_certificates_index_with_grades_and_types(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'BUR101',
            'titre' => 'Bureautique Avancée',
            'quota_heures' => 40,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G1-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $studentPassing = User::factory()->create(['name' => 'Fatou Diop']);
        $studentPassing->assignRole('Apprenant');

        $studentFailing = User::factory()->create(['name' => 'Moussa Ndiaye']);
        $studentFailing->assignRole('Apprenant');

        $group->students()->attach([$studentPassing->id, $studentFailing->id]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Final Bureautique',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
            'are_grades_published' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $studentPassing->id,
            'score' => 15.5,
            'status' => 'completed',
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $studentFailing->id,
            'score' => 7.5,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($director)->get(route('certificates.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Scolarite/CertificatesIndex')
            ->has('closedGroups', 1)
            ->where('closedGroups.0.nom_groupe', 'G1-26')
            ->where('closedGroups.0.students.0.score', 15.5)
            ->where('closedGroups.0.students.0.suggested_type', 'reussite')
            ->where('closedGroups.0.students.1.score', 7.5)
            ->where('closedGroups.0.students.1.suggested_type', 'participation')
        );
    }

    public function test_generate_certificate_records_reussite_for_student_with_passing_score(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'INF101',
            'titre' => 'Initiation Informatique',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G2-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Awa Sow']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Évaluation Finale',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => 14.0,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            ['group_id' => $group->id]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'type' => 'reussite',
            'score' => 14.0,
        ]);
    }

    public function test_generate_certificate_records_participation_for_student_with_failing_score(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'INF102',
            'titre' => 'Algorithmique',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G3-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Ousmane Ba']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Pratique',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => 8.5,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            ['group_id' => $group->id]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'type' => 'participation',
            'score' => 8.5,
        ]);
    }

    public function test_batch_generate_for_closed_group_assigns_appropriate_types(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'WEB201',
            'titre' => 'Développement Web Front-End',
            'quota_heures' => 60,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G4-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student1 = User::factory()->create(['name' => 'Mariama Diallo']);
        $student1->assignRole('Apprenant');

        $student2 = User::factory()->create(['name' => 'Ibrahima Sarr']);
        $student2->assignRole('Apprenant');

        $group->students()->attach([$student1->id, $student2->id]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Projet et Examen Final',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        // Student 1: 16/20 (Passed)
        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student1->id,
            'score' => 16.0,
            'status' => 'completed',
        ]);

        // Student 2: 6.5/20 (Failed)
        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student2->id,
            'score' => 6.5,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($director)->post(
            route('certificates.generate-group', ['group' => $group->id])
        );

        $response->assertSessionHas('success');

        // Check student 1 certificate (Réussite)
        $this->assertDatabaseHas('certificates', [
            'user_id' => $student1->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'type' => 'reussite',
            'score' => 16.0,
        ]);

        // Check student 2 certificate (Participation)
        $this->assertDatabaseHas('certificates', [
            'user_id' => $student2->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'type' => 'participation',
            'score' => 6.5,
        ]);
    }

    public function test_generate_certificate_records_custom_dates(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV101',
            'titre' => 'Programmation Python',
            'quota_heures' => 45,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G5-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Khadija Ba']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            [
                'group_id' => $group->id,
                'score' => 16.5,
                'type' => 'reussite',
                'start_date' => '2026-01-15',
                'end_date' => '2026-05-20',
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'type' => 'reussite',
            'score' => 16.5,
            'start_date' => '2026-01-15 00:00:00',
            'end_date' => '2026-05-20 00:00:00',
        ]);
    }

    public function test_batch_generate_applies_custom_dates(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV102',
            'titre' => 'Bases de Données',
            'quota_heures' => 35,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G6-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student1 = User::factory()->create();
        $student1->assignRole('Apprenant');
        $student2 = User::factory()->create();
        $student2->assignRole('Apprenant');

        $group->students()->attach([$student1->id, $student2->id]);

        $response = $this->actingAs($director)->post(
            route('certificates.generate-group', ['group' => $group->id]),
            [
                'start_date' => '2025-10-01',
                'end_date' => '2026-02-28',
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'user_id' => $student1->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'start_date' => '2025-10-01 00:00:00',
            'end_date' => '2026-02-28 00:00:00',
        ]);

        $this->assertDatabaseHas('certificates', [
            'user_id' => $student2->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'start_date' => '2025-10-01 00:00:00',
            'end_date' => '2026-02-28 00:00:00',
        ]);
    }

    public function test_certificate_verification_returns_type_and_score(): void
    {
        $user = User::factory()->create(['name' => 'Amadou Fall']);
        $user->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'SEC101',
            'titre' => 'Sécurité Informatique',
            'quota_heures' => 25,
        ]);

        $cert = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'type' => 'participation',
            'score' => 9.0,
            'issued_at' => now(),
        ]);

        $response = $this->get(route('certificates.verify', $cert->uuid));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Certificates/VerifyCertificate')
            ->where('valid', true)
            ->where('certificate.type', 'participation')
            ->where('certificate.score', 9)
            ->where('certificate.type_label', 'Attestation de Participation')
        );
    }
}
