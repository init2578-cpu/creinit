<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
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

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen BD',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student1->id,
            'score' => 15.0,
            'status' => 'completed',
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student2->id,
            'score' => 13.0,
            'status' => 'completed',
        ]);

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
            'score' => 15.0,
            'start_date' => '2025-10-01 00:00:00',
            'end_date' => '2026-02-28 00:00:00',
        ]);

        $this->assertDatabaseHas('certificates', [
            'user_id' => $student2->id,
            'module_id' => $module->id,
            'group_id' => $group->id,
            'score' => 13.0,
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

    public function test_cannot_generate_certificate_for_student_with_more_than_3_unjustified_absences_and_no_score(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV201',
            'titre' => 'Développement Web',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-DEV-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Badara Sy']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        // Record 4 unjustified absences
        for ($i = 1; $i <= 4; $i++) {
            Attendance::create([
                'user_id' => $student->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        // Student has NO exam results and NO score
        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            ['group_id' => $group->id]
        );

        $response->assertSessionHasErrors('score');
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_can_generate_certificate_for_student_with_more_than_3_unjustified_absences_if_score_is_provided(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV202',
            'titre' => 'Développement Mobile',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-MOB-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Aissatou Ba']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        // 4 unjustified absences
        for ($i = 1; $i <= 4; $i++) {
            Attendance::create([
                'user_id' => $student->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        // Generating WITH a score and director written justification must work
        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            [
                'group_id' => $group->id,
                'score' => 13.5,
                'justification' => 'Appréciation accordée suite aux efforts exceptionnels lors du projet de synthèse.',
            ]
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
            'score' => 13.5,
            'type' => 'reussite',
            'justification' => 'Appréciation accordée suite aux efforts exceptionnels lors du projet de synthèse.',
        ]);
    }

    public function test_non_director_cannot_generate_certificate_for_student_with_more_than_3_unjustified_absences_even_with_score(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV202B',
            'titre' => 'Développement Mobile B',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-MOB2-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Khadija Fall']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        for ($i = 1; $i <= 4; $i++) {
            Attendance::create([
                'user_id' => $student->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        // Secretary attempts to generate, even with a score and justification
        $response = $this->actingAs($secretary)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            [
                'group_id' => $group->id,
                'score' => 15.0,
                'justification' => 'Tentative par la secrétaire',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_non_director_cannot_generate_certificate_even_for_eligible_student_with_passing_score(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV202X',
            'titre' => 'Développement Mobile X',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-MOBX-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Abdoulaye Wade']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        // Student has perfect score and 0 absences
        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Mobile X',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => 18.0,
            'status' => 'completed',
        ]);

        // Secretary attempts to generate certificate: must be forbidden
        $response = $this->actingAs($secretary)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            ['group_id' => $group->id]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_non_director_cannot_batch_generate_certificates_for_group(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV202Y',
            'titre' => 'Développement Mobile Y',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-MOBY-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Binta Diallo']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Mobile Y',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => 16.0,
            'status' => 'completed',
        ]);

        // Secretary attempts batch generation: must be forbidden
        $response = $this->actingAs($secretary)->post(
            route('certificates.generate-group', ['group' => $group->id])
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_non_director_cannot_delete_certificate(): void
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'TEST_DEL',
            'titre' => 'Module Test Deletion',
            'quota_heures' => 20,
        ]);

        $certificate = Certificate::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'type' => 'reussite',
            'score' => 15.0,
            'issued_at' => now(),
        ]);

        // Secretary attempts to delete: forbidden
        $response = $this->actingAs($secretary)->delete(
            route('certificates.destroy', ['certificate' => $certificate->id])
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
        ]);

        // Director deletes: success
        $responseDirector = $this->actingAs($director)->delete(
            route('certificates.destroy', ['certificate' => $certificate->id])
        );

        $responseDirector->assertSessionHas('success');
        $this->assertDatabaseMissing('certificates', [
            'id' => $certificate->id,
        ]);
    }

    public function test_director_cannot_generate_certificate_for_student_with_more_than_3_unjustified_absences_without_written_justification(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV202C',
            'titre' => 'Développement Mobile C',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-MOB3-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Moussa Diop']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        for ($i = 1; $i <= 4; $i++) {
            Attendance::create([
                'user_id' => $student->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        // Director submits score but leaves justification empty
        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            [
                'group_id' => $group->id,
                'score' => 14.0,
                'justification' => '',
            ]
        );

        $response->assertSessionHasErrors('justification');
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_cannot_generate_certificate_for_student_without_score_even_with_low_absences(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV203',
            'titre' => 'Design Graphique',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-DES-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Cheikh Tidiane']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        // 3 unjustified absences (not > 3), but NO score
        for ($i = 1; $i <= 3; $i++) {
            Attendance::create([
                'user_id' => $student->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        // Must be blocked because student has no score
        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            ['group_id' => $group->id]
        );

        $response->assertSessionHasErrors('score');
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_can_generate_certificate_for_student_with_3_unjustified_absences_with_score(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV203B',
            'titre' => 'Design Graphique B',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-DESB-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create(['name' => 'Cheikh Tidiane 2']);
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        // 3 unjustified absences (<= 3)
        for ($i = 1; $i <= 3; $i++) {
            Attendance::create([
                'user_id' => $student->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        // Generating WITH a score succeeds without director justification since absences <= 3
        $response = $this->actingAs($director)->post(
            route('certificates.generate', ['student' => $student->id, 'module' => $module->id]),
            [
                'group_id' => $group->id,
                'score' => 14.5,
            ]
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'module_id' => $module->id,
            'score' => 14.5,
            'type' => 'reussite',
        ]);
    }

    public function test_batch_generate_skips_students_with_more_than_3_unjustified_absences_and_no_score(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV204',
            'titre' => 'Réseaux',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-RES-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        // Student 1: 4 unjustified absences, no score -> must be skipped
        $studentBlocked = User::factory()->create(['name' => 'Ousmane Mane']);
        $studentBlocked->assignRole('Apprenant');

        // Student 2: 1 unjustified absence, HAS score -> generated
        $studentAllowed = User::factory()->create(['name' => 'Mariama Diallo']);
        $studentAllowed->assignRole('Apprenant');

        $group->students()->attach([$studentBlocked->id, $studentAllowed->id]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Réseaux',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $studentAllowed->id,
            'score' => 15.0,
            'status' => 'completed',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            Attendance::create([
                'user_id' => $studentBlocked->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        Attendance::create([
            'user_id' => $studentAllowed->id,
            'group_id' => $group->id,
            'date' => '2026-03-01',
            'status' => 'absent_non_justifie',
        ]);

        $response = $this->actingAs($director)->post(
            route('certificates.generate-group', ['group' => $group->id])
        );

        $response->assertSessionHas('success');

        // Blocked student must NOT have a certificate
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $studentBlocked->id,
            'module_id' => $module->id,
        ]);

        // Allowed student MUST have a certificate
        $this->assertDatabaseHas('certificates', [
            'user_id' => $studentAllowed->id,
            'module_id' => $module->id,
            'score' => 15.0,
        ]);
    }

    public function test_batch_generate_skips_students_with_more_than_3_unjustified_absences_even_with_score(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV205',
            'titre' => 'Cloud Computing',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-CLD-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $studentWithScoreAndAbsences = User::factory()->create(['name' => 'Babacar Diagne']);
        $studentWithScoreAndAbsences->assignRole('Apprenant');

        $studentNormal = User::factory()->create(['name' => 'Fatou Ndiaye']);
        $studentNormal->assignRole('Apprenant');

        $group->students()->attach([$studentWithScoreAndAbsences->id, $studentNormal->id]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen Cloud',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $studentWithScoreAndAbsences->id,
            'score' => 16.0,
            'status' => 'completed',
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $studentNormal->id,
            'score' => 14.0,
            'status' => 'completed',
        ]);

        // Student 1 has 5 unjustified absences
        for ($i = 1; $i <= 5; $i++) {
            Attendance::create([
                'user_id' => $studentWithScoreAndAbsences->id,
                'group_id' => $group->id,
                'date' => "2026-03-0{$i}",
                'status' => 'absent_non_justifie',
            ]);
        }

        $response = $this->actingAs($director)->post(
            route('certificates.generate-group', ['group' => $group->id])
        );

        $response->assertSessionHas('success');

        // Student with 5 absences and score must NOT be generated via batch (requires Director appraisal + justification)
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $studentWithScoreAndAbsences->id,
            'module_id' => $module->id,
        ]);

        // Normal student certificate must be generated
        $this->assertDatabaseHas('certificates', [
            'user_id' => $studentNormal->id,
            'module_id' => $module->id,
            'score' => 14.0,
        ]);
    }

    public function test_batch_generate_skips_student_without_score_even_with_zero_absences(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV206',
            'titre' => 'Intelligence Artificielle',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'G-IA-26',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        // Student with 0 absences, but NO score -> must be skipped
        $studentNoScore = User::factory()->create(['name' => 'Mouhamed Lo']);
        $studentNoScore->assignRole('Apprenant');

        // Student with score -> generated
        $studentWithScore = User::factory()->create(['name' => 'Sokhna Diop']);
        $studentWithScore->assignRole('Apprenant');

        $group->students()->attach([$studentNoScore->id, $studentWithScore->id]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'user_id' => $trainer->id,
            'titre' => 'Examen IA',
            'type' => 'paper',
            'total_points' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $studentWithScore->id,
            'score' => 17.5,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($director)->post(
            route('certificates.generate-group', ['group' => $group->id])
        );

        $response->assertSessionHas('success');

        // Student without score must NOT have a certificate
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $studentNoScore->id,
            'module_id' => $module->id,
        ]);

        // Student with score must have certificate
        $this->assertDatabaseHas('certificates', [
            'user_id' => $studentWithScore->id,
            'module_id' => $module->id,
            'score' => 17.5,
        ]);
    }
}

