<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\Module;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class GroupAttendanceHistoryTest extends TestCase
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
    }

    public function test_group_closure_preserves_all_attendance_history_and_student_statistics()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV101',
            'titre' => 'Développement Web',
            'quota_heures' => 50,
        ]);

        $group = Group::create([
            'nom_groupe' => 'Promo 2026 A',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'active',
        ]);

        $student1 = User::factory()->create(['name' => 'Alice Martin']);
        $student1->assignRole('Apprenant');

        $student2 = User::factory()->create(['name' => 'Bob Dupont']);
        $student2->assignRole('Apprenant');

        $group->students()->attach([$student1->id, $student2->id]);

        // Record some attendances across 2 dates
        Attendance::create([
            'group_id' => $group->id,
            'user_id' => $student1->id,
            'date' => '2026-03-01',
            'status' => 'present',
        ]);
        Attendance::create([
            'group_id' => $group->id,
            'user_id' => $student2->id,
            'date' => '2026-03-01',
            'status' => 'absent_non_justifie',
        ]);
        Attendance::create([
            'group_id' => $group->id,
            'user_id' => $student1->id,
            'date' => '2026-03-02',
            'status' => 'late',
        ]);
        Attendance::create([
            'group_id' => $group->id,
            'user_id' => $student2->id,
            'date' => '2026-03-02',
            'status' => 'justifie',
        ]);

        $this->assertDatabaseCount('attendances', 4);

        // Close the group
        $response = $this->actingAs($director)->patch(route('groups.close', $group->id));
        $response->assertSessionHas('success');

        $group->refresh();
        $this->assertEquals('closed', $group->status);

        // Check attendances are STILL intact in DB
        $this->assertDatabaseCount('attendances', 4);

        // Access the attendance history page
        $historyResponse = $this->actingAs($director)->get(route('groups.attendances.history', $group->id));
        $historyResponse->assertOk();
        $historyResponse->assertInertia(fn (Assert $page) => $page
            ->component('Scolarite/GroupAttendanceHistory')
            ->has('group')
            ->where('group.status', 'closed')
            ->has('stats')
            ->where('stats.total_sessions', 2)
            ->where('stats.total_presences', 1)
            ->where('stats.total_absences', 2)
            ->where('stats.total_lates', 1)
            ->has('students', 2)
            ->has('sessions', 2)
        );
    }

    public function test_group_with_attendances_cannot_be_deleted()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $module = Module::create([
            'code_module' => 'DEV102',
            'titre' => 'Algorithmique',
            'quota_heures' => 30,
        ]);

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $group = Group::create([
            'nom_groupe' => 'Promo B',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        Attendance::create([
            'group_id' => $group->id,
            'user_id' => $student->id,
            'date' => '2026-03-01',
            'status' => 'present',
        ]);

        // Attempt deletion
        $response = $this->actingAs($director)->delete(route('groups.destroy', $group->id));
        $response->assertSessionHasErrors('group');

        // Verify group still exists
        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_trainer_is_redirected_to_history_when_trying_to_take_attendance_for_closed_group()
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV103',
            'titre' => 'Base de données',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'Promo C',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $response = $this->actingAs($trainer)->get(route('attendances.take', $group->id));
        $response->assertRedirect(route('groups.attendances.history', $group->id));
        $response->assertSessionHas('info');
    }

    public function test_secretary_can_access_group_attendance_history()
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV104',
            'titre' => 'DevOps',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'Promo D',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $response = $this->actingAs($secretary)->get(route('groups.attendances.history', $group->id));
        $response->assertOk();
    }

    public function test_group_students_index_includes_attendance_stats_even_when_closed()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV105',
            'titre' => 'Sécurité',
            'quota_heures' => 30,
        ]);

        $group = Group::create([
            'nom_groupe' => 'Promo E',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
            'status' => 'closed',
        ]);

        $student = User::factory()->create();
        $student->assignRole('Apprenant');
        $group->students()->attach($student->id);

        Attendance::create([
            'group_id' => $group->id,
            'user_id' => $student->id,
            'date' => '2026-03-01',
            'status' => 'present',
        ]);

        $response = $this->actingAs($director)->get(route('groups.students.index', $group->id));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Scolarite/GroupStudents')
            ->has('currentStudents.0.presences_count')
            ->where('currentStudents.0.presences_count', 1)
            ->where('currentStudents.0.attendance_rate', 100)
            ->has('attendanceStats')
            ->where('attendanceStats.total_sessions', 1)
        );
    }
}

