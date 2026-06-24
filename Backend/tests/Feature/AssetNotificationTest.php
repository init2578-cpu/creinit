<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Notifications\DefectiveAssetReported;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Roles
        Role::create(['name' => 'Directeur', 'guard_name' => 'web']);
        Role::create(['name' => 'Secrétaire', 'guard_name' => 'web']);
        Role::create(['name' => 'Formateur', 'guard_name' => 'web']); // A role that shouldn't be notified
    }

    public function test_notifications_are_sent_to_director_and_secretary_when_asset_is_reported_defective()
    {
        Notification::fake();

        // Create users
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        // Trigger update to defective
        $asset = Asset::create([
            'nom' => 'PC Test',
            'serie' => 'SN123',
            'etat' => 'bon',
            'status' => 'disponible',
            'emplacement' => 'Salle 1'
        ]);

        $response = $this->actingAs($director)
            ->put(route('assets.update', $asset), [
                'nom' => 'PC Test',
                'serie' => 'SN123',
                'etat' => 'hors_service',
                'status' => 'maintenance',
                'emplacement' => 'Salle 1'
            ]);

        $response->assertStatus(302);

        // Verify notifications
        Notification::assertSentTo(
            [$director, $secretary],
            DefectiveAssetReported::class
        );

        Notification::assertNotSentTo(
            $trainer,
            DefectiveAssetReported::class
        );
    }
}
