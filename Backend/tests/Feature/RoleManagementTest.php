<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $directorRole = Role::create(['name' => 'Directeur']);
        Role::create(['name' => 'Formateur']);
        Permission::create(['name' => 'validate-chapters']);
    }

    public function test_director_can_assign_multiple_roles_and_permissions()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $user = User::factory()->create();

        $response = $this->actingAs($director)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'roles' => ['Formateur'],
            'permissions' => ['validate-chapters'],
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('Formateur'));
        $this->assertTrue($user->fresh()->hasPermissionTo('validate-chapters'));
    }

    public function test_non_director_cannot_manage_users()
    {
        $user = User::factory()->create();
        $formateur = User::factory()->create();
        $formateur->assignRole('Formateur');

        $response = $this->actingAs($formateur)->put(route('users.update', $user), [
            'name' => 'Hacked Name',
            'email' => $user->email,
            'roles' => ['Directeur'],
            'is_active' => true,
        ]);

        $response->assertStatus(403);
    }
}
