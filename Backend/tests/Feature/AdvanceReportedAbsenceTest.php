<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\Module;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdvanceReportedAbsenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Apprenant']);
        Role::firstOrCreate(['name' => 'Formateur']);
        Role::firstOrCreate(['name' => 'Stagiaire']);

        Setting::updateOrCreate(['key' => 'latitude_centre'], ['value' => '14.6937']);
        Setting::updateOrCreate(['key' => 'longitude_centre'], ['value' => '-17.4441']);
        Setting::updateOrCreate(['key' => 'rayon_metre'], ['value' => '1000']);
        Setting::updateOrCreate(['key' => 'attendance_buffer_before'], ['value' => '60']);
        Setting::updateOrCreate(['key' => 'attendance_buffer_after'], ['value' => '120']);
    }

    private function createSetup(): array
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $student1 = User::factory()->create(['name' => 'Jean Dupont']);
        $student1->assignRole('Apprenant');

        $student2 = User::factory()->create(['name' => 'Marie Martin']);
        $student2->assignRole('Apprenant');

        $module = Module::create([
            'code_module' => 'WEB101',
            'titre' => 'Initiation Web',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'Groupe Test',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'active',
            'gps_check_required' => false,
        ]);

        $group->students()->attach([$student1->id, $student2->id]);

        $room = Room::create(['nom' => 'Salle A', 'capacite' => 30, 'type_salle' => 'cours']);

        $schedule = Schedule::create([
            'group_id' => $group->id,
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'room_id' => $room->id,
            'day_of_week' => Carbon::now()->dayOfWeekIso,
            'start_time' => Carbon::now()->subMinutes(10)->format('H:i:s'),
            'end_time' => Carbon::now()->addMinutes(50)->format('H:i:s'),
        ]);

        return compact('director', 'secretary', 'trainer', 'student1', 'student2', 'group', 'schedule');
    }

    public function test_only_director_and_secretary_can_report_advance_absence(): void
    {
        $data = $this->createSetup();
        $today = Carbon::today()->toDateString();

        // Trainer should receive 403 Forbidden
        $responseTrainer = $this->actingAs($data['trainer'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'motif' => 'Rendez-vous médical',
            ]);
        $responseTrainer->assertStatus(403);

        // Student should receive 403 Forbidden
        $responseStudent = $this->actingAs($data['student1'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'motif' => 'Rendez-vous médical',
            ]);
        $responseStudent->assertStatus(403);

        // Secretary should succeed
        $responseSecretary = $this->actingAs($data['secretary'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'motif' => 'Rendez-vous médical prévenu par téléphone',
            ]);
        $responseSecretary->assertRedirect();
        $responseSecretary->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $data['student1']->id,
            'group_id' => $data['group']->id,
            'schedule_id' => $data['schedule']->id,
            'date' => $today,
            'status' => 'justifie',
            'is_advance_reported' => true,
            'motif' => 'Rendez-vous médical prévenu par téléphone',
            'reported_by' => $data['secretary']->id,
        ]);
    }

    public function test_director_can_report_advance_absence_for_all_schedules_of_day(): void
    {
        $data = $this->createSetup();
        $today = Carbon::today()->toDateString();

        $responseDirector = $this->actingAs($data['director'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => '', // all schedules of day
                'user_id' => $data['student2']->id,
                'date' => $today,
                'motif' => 'Urgence familiale',
            ]);

        $responseDirector->assertRedirect();
        $responseDirector->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $data['student2']->id,
            'group_id' => $data['group']->id,
            'schedule_id' => $data['schedule']->id,
            'date' => $today,
            'status' => 'justifie',
            'is_advance_reported' => true,
            'motif' => 'Urgence familiale',
            'reported_by' => $data['director']->id,
        ]);
    }

    public function test_advance_absence_does_not_block_trainer_from_taking_roll_call(): void
    {
        $data = $this->createSetup();
        $today = Carbon::today()->toDateString();

        // 1. Secretary reports advance absence for student 1
        $this->actingAs($data['secretary'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'motif' => 'Arrêt maladie',
            ]);

        // 2. Trainer visits attendance take page (should NOT be readonly)
        $responseTake = $this->actingAs($data['trainer'])
            ->get(route('attendance.take', [
                'schedule' => $data['schedule']->id,
                'date' => $today,
            ]));

        $responseTake->assertStatus(200);

        // 3. Trainer submits attendance for the class: student 1 kept justified, student 2 marked present
        $responseStore = $this->actingAs($data['trainer'])
            ->post(route('attendance.store'), [
                'schedule_id' => $data['schedule']->id,
                'date' => $today,
                'latitude' => 0,
                'longitude' => 0,
                'students' => [
                    [
                        'id' => $data['student1']->id,
                        'status' => 'justifie',
                    ],
                    [
                        'id' => $data['student2']->id,
                        'status' => 'present',
                    ],
                ],
            ]);

        $responseStore->assertRedirect();

        // Check that student 1 preserved is_advance_reported and motif
        $this->assertDatabaseHas('attendances', [
            'user_id' => $data['student1']->id,
            'schedule_id' => $data['schedule']->id,
            'date' => $today,
            'status' => 'justifie',
            'is_advance_reported' => true,
            'motif' => 'Arrêt maladie',
        ]);

        // Check that student 2 is present and not advance reported
        $this->assertDatabaseHas('attendances', [
            'user_id' => $data['student2']->id,
            'schedule_id' => $data['schedule']->id,
            'date' => $today,
            'status' => 'present',
            'is_advance_reported' => false,
        ]);
    }

    public function test_advance_absence_can_be_cancelled(): void
    {
        $data = $this->createSetup();
        $today = Carbon::today()->toDateString();

        // Secretary reports advance absence
        $this->actingAs($data['secretary'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'motif' => 'Empêchement temporaire',
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $data['student1']->id,
            'schedule_id' => $data['schedule']->id,
            'is_advance_reported' => true,
        ]);

        // Secretary cancels the advance absence
        $responseCancel = $this->actingAs($data['secretary'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'action' => 'cancel',
            ]);

        $responseCancel->assertRedirect();
        $responseCancel->assertSessionHas('success');

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $data['student1']->id,
            'schedule_id' => $data['schedule']->id,
            'date' => $today,
        ]);
    }

    public function test_advance_absence_is_reflected_in_group_history_and_director_learner_absences(): void
    {
        $data = $this->createSetup();
        $today = Carbon::today()->toDateString();

        // 1. Report advance absence
        $this->actingAs($data['secretary'])
            ->post(route('attendance.report-absence'), [
                'group_id' => $data['group']->id,
                'schedule_id' => $data['schedule']->id,
                'user_id' => $data['student1']->id,
                'date' => $today,
                'motif' => 'Certificat médical transmis',
            ]);

        // 2. Query director endpoint for learner absences
        $responseLearnerAbsences = $this->actingAs($data['director'])
            ->getJson(route('api.stats.director.learner-absences', [
                'user' => $data['student1']->id,
                'group' => $data['group']->id,
            ]));

        $responseLearnerAbsences->assertStatus(200);
        $responseLearnerAbsences->assertJsonFragment([
            'status' => 'justifie',
            'is_advance_reported' => true,
            'motif' => 'Certificat médical transmis',
        ]);

        // 3. Query group attendance history page
        $responseGroupHistory = $this->actingAs($data['director'])
            ->get(route('groups.attendances.history', $data['group']->id));

        $responseGroupHistory->assertStatus(200);
    }
}
