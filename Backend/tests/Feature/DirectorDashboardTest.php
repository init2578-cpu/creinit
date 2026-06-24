<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\Asset;
use App\Models\Module;
use App\Models\Certificate;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DirectorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Apprenant']);
        Role::create(['name' => 'Formateur']);
        Role::create(['name' => 'Directeur']);
        Role::create(['name' => 'Secrétaire']);
        Role::create(['name' => 'Stagiaire']);
    }

    public function test_api_stats_returns_correct_real_data()
    {
        // 1. Create Learners with Applications for Parity
        $maleUser = User::factory()->create();
        $maleUser->assignRole('Apprenant');
        Application::create([
            'user_id' => $maleUser->id,
            'sexe' => 'M',
            'nom_complet' => $maleUser->name,
        ]);

        $femaleUser = User::factory()->create();
        $femaleUser->assignRole('Apprenant');
        Application::create([
            'user_id' => $femaleUser->id,
            'sexe' => 'F',
            'nom_complet' => $femaleUser->name,
        ]);

        // 4. Create Module and Group
        $module = Module::create(['titre' => 'Informatique', 'code_module' => 'INF01', 'quota_heures' => 40]);
        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');
        $group = Group::create([
            'nom_groupe' => 'G1', 
            'annee_academique' => '2026', 
            'module_id' => $module->id,
            'formateur_id' => $trainer->id
        ]);

        // 2. Create Attendance data (50% rate)
        Attendance::create(['user_id' => $maleUser->id, 'group_id' => $group->id, 'status' => 'present', 'date' => now()]);
        Attendance::create(['user_id' => $femaleUser->id, 'group_id' => $group->id, 'status' => 'absent_non_justifie', 'date' => now()]);

        // 3. Create Hardware data (1/3 broken)
        Asset::create(['nom' => 'PC 1', 'etat' => 'neuf']);
        Asset::create(['nom' => 'PC 2', 'etat' => 'bon']);
        Asset::create(['nom' => 'PC 3', 'etat' => 'hors_service']);

        // 5. Create Certificates
        Certificate::create([
            'user_id' => $maleUser->id, 
            'module_id' => $module->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'issued_at' => now(),
        ]);

        $admin = User::find($femaleUser->id);
        $admin->assignRole('Directeur');

        $response = $this->actingAs($admin, 'sanctum')
            ->get(route('api.stats.director'));

        $response->assertStatus(200);
        $response->assertJson([
            'total_learners' => 2,
            'attendance_rate' => 50,
            'operational_hardware' => 66.7,
            'gender_parity' => [
                'male' => 1,
                'female' => 1,
                'total' => 2,
                'ratio' => 50
            ],
            'total_certificates' => 1,
        ]);
    }
}
