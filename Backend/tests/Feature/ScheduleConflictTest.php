<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Formateur']);
    }

    public function test_active_group_schedule_blocks_room(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $formateur = User::factory()->create();
        $formateur->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40,
        ]);

        $activeGroup = Group::create([
            'nom_groupe' => 'G1-26',
            'module_id' => $module->id,
            'formateur_id' => $formateur->id,
            'annee_academique' => '2025-2026',
            'status' => 'active',
        ]);

        $room = Room::create(['nom' => 'Salle C1', 'capacite' => 30, 'type_salle' => 'cours']);

        Schedule::create([
            'group_id' => $activeGroup->id,
            'room_id' => $room->id,
            'formateur_id' => $formateur->id,
            'day_of_week' => 1, // Lundi
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $newGroup = Group::create([
            'nom_groupe' => 'G2-26',
            'module_id' => $module->id,
            'formateur_id' => $formateur->id,
            'annee_academique' => '2025-2026',
            'status' => 'active',
        ]);

        $response = $this->actingAs($director)->post(route('schedules.store'), [
            'group_id' => $newGroup->id,
            'room_id' => $room->id,
            'formateur_id' => $formateur->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertSessionHasErrors(['room_id']);
    }

    public function test_closed_group_schedule_does_not_block_room(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $formateur = User::factory()->create();
        $formateur->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40,
        ]);

        $closedGroup = Group::create([
            'nom_groupe' => 'G1-26',
            'module_id' => $module->id,
            'formateur_id' => $formateur->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $room = Room::create(['nom' => 'Salle C1', 'capacite' => 30, 'type_salle' => 'cours']);

        Schedule::create([
            'group_id' => $closedGroup->id,
            'room_id' => $room->id,
            'formateur_id' => $formateur->id,
            'day_of_week' => 1, // Lundi
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $newGroup = Group::create([
            'nom_groupe' => 'G2-26',
            'module_id' => $module->id,
            'formateur_id' => $formateur->id,
            'annee_academique' => '2025-2026',
            'status' => 'active',
        ]);

        $response = $this->actingAs($director)->post(route('schedules.store'), [
            'group_id' => $newGroup->id,
            'room_id' => $room->id,
            'formateur_id' => $formateur->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', [
            'group_id' => $newGroup->id,
            'room_id' => $room->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_pending_alerts_endpoint_executes_without_error(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $response = $this->actingAs($director)->getJson(route('api.attendance.pending-alerts'));

        $response->assertOk()
            ->assertJsonStructure(['alerts', 'count']);
    }

    public function test_pending_alerts_triggers_after_5_minutes(): void
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $formateur = User::factory()->create();
        $formateur->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'M102',
            'titre' => 'Module Test Alert',
            'quota_heures' => 40,
        ]);

        $activeGroup = Group::create([
            'nom_groupe' => 'G3-26',
            'module_id' => $module->id,
            'formateur_id' => $formateur->id,
            'annee_academique' => '2025-2026',
            'status' => 'active',
        ]);

        $room = Room::create(['nom' => 'Salle C3', 'capacite' => 30, 'type_salle' => 'cours']);

        $now = \Carbon\Carbon::now();
        $currentDay = (int) $now->dayOfWeekIso;

        // Course started 6 minutes ago and ends in 1 hour
        $startTime = $now->copy()->subMinutes(6)->format('H:i:s');
        $endTime = $now->copy()->addHour()->format('H:i:s');

        Schedule::create([
            'group_id' => $activeGroup->id,
            'room_id' => $room->id,
            'formateur_id' => $formateur->id,
            'day_of_week' => $currentDay,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $response = $this->actingAs($director)->getJson(route('api.attendance.pending-alerts'));

        $response->assertOk()
            ->assertJsonCount(1, 'alerts')
            ->assertJsonPath('alerts.0.group_name', 'G3-26');
    }
}
