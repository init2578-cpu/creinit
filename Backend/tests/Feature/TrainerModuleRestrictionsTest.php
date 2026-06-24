<?php

namespace Tests\Feature;

use App\Models\Formation;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class TrainerModuleRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Formateur']);
    }

    public function test_modules_index_returns_predefined_formations()
    {
        Formation::create(['code' => 'TEST-01', 'titre' => 'Formation de Test']);
        
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $response = $this->actingAs($director)->get(route('modules.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('predefined_formations', 1)
            ->where('predefined_formations.0.code', 'TEST-01')
            ->where('predefined_formations.0.titre', 'Formation de Test')
        );
    }

    public function test_trainer_cannot_create_update_or_delete_modules()
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = Module::create([
            'code_module' => 'DEV-99',
            'titre' => 'Module Ancien',
            'quota_heures' => 10,
        ]);

        // 1. Create (store) should fail with 403
        $response = $this->actingAs($trainer)->post(route('modules.store'), [
            'code_module' => 'NEW-01',
            'titre' => 'Nouveau',
            'quota_heures' => 30,
        ]);
        $response->assertStatus(403);

        // 2. Update should fail with 403
        $response = $this->actingAs($trainer)->put(route('modules.update', $module->id), [
            'code_module' => 'DEV-99',
            'titre' => 'Module Modifie',
            'quota_heures' => 12,
        ]);
        $response->assertStatus(403);

        // 3. Delete (destroy) should fail with 403
        $response = $this->actingAs($trainer)->delete(route('modules.destroy', $module->id));
        $response->assertStatus(403);
    }

    public function test_director_can_create_update_and_delete_modules()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $module = Module::create([
            'code_module' => 'DEV-99',
            'titre' => 'Module Ancien',
            'quota_heures' => 10,
        ]);

        // 1. Create (store) should succeed (redirects back)
        $response = $this->actingAs($director)->post(route('modules.store'), [
            'code_module' => 'NEW-01',
            'titre' => 'Nouveau',
            'quota_heures' => 30,
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('modules', ['code_module' => 'NEW-01']);

        // 2. Update should succeed (redirects back)
        $response = $this->actingAs($director)->put(route('modules.update', $module->id), [
            'code_module' => 'DEV-99',
            'titre' => 'Module Modifie',
            'quota_heures' => 12,
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('modules', ['titre' => 'Module Modifie']);

        // 3. Delete (destroy) should succeed (redirects back)
        $response = $this->actingAs($director)->delete(route('modules.destroy', $module->id));
        $response->assertStatus(302);
        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }
}
