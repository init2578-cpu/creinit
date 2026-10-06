<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationPendingMotifTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $secretary;
    protected Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Directeur']);
        Role::create(['name' => 'Secrétaire']);
        Role::create(['name' => 'Apprenant']);
        Role::create(['name' => 'Formateur']);

        $this->director = User::factory()->create(['name' => 'Directeur Test']);
        $this->director->assignRole('Directeur');

        $this->secretary = User::factory()->create(['name' => 'Secrétaire Test']);
        $this->secretary->assignRole('Secrétaire');

        $this->module = Module::create([
            'titre' => 'Développement Web',
            'code_module' => 'DEVWEB',
            'quota_heures' => 120,
            'is_active' => true,
        ]);
    }

    public function test_admitted_application_cannot_be_reverted_to_pending_without_motif(): void
    {
        $learner = User::factory()->create(['name' => 'Moussa Diallo']);
        $learner->assignRole('Apprenant');

        $application = Application::create([
            'user_id' => $learner->id,
            'module_id' => $this->module->id,
            'nom_complet' => 'Moussa Diallo',
            'telephone' => '771234567',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->director)
            ->patch(route('applications.status.update', $application->id), [
                'status' => 'pending',
                'motif' => '',
            ]);

        $response->assertSessionHasErrors('motif');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'admitted',
            'motif_remise_en_attente' => null,
        ]);
    }

    public function test_admitted_application_can_be_reverted_to_pending_with_motif(): void
    {
        $learner = User::factory()->create(['name' => 'Fatou Ndiaye']);
        $learner->assignRole('Apprenant');

        $application = Application::create([
            'user_id' => $learner->id,
            'module_id' => $this->module->id,
            'nom_complet' => 'Fatou Ndiaye',
            'telephone' => '772345678',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->director)
            ->patch(route('applications.status.update', $application->id), [
                'status' => 'pending',
                'motif' => 'Dossier incomplet : pièce d’identité illisible',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'pending',
            'motif_remise_en_attente' => 'Dossier incomplet : pièce d’identité illisible',
        ]);

        // Learner role should be removed since no other admitted applications exist
        $this->assertFalse($learner->fresh()->hasRole('Apprenant'));
    }

    public function test_non_director_cannot_revert_application_to_pending(): void
    {
        $learner = User::factory()->create(['name' => 'Ousmane Ba']);
        $learner->assignRole('Apprenant');

        $application = Application::create([
            'user_id' => $learner->id,
            'module_id' => $this->module->id,
            'nom_complet' => 'Ousmane Ba',
            'telephone' => '773456789',
            'status' => 'admitted',
        ]);

        $response = $this->actingAs($this->secretary)
            ->patch(route('applications.status.update', $application->id), [
                'status' => 'pending',
                'motif' => 'Tentative par la secrétaire',
            ]);

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'admitted',
        ]);
    }

    public function test_readmitting_application_clears_pending_motif_and_reassigns_role(): void
    {
        $learner = User::factory()->create(['name' => 'Aïssatou Sow']);

        $application = Application::create([
            'user_id' => $learner->id,
            'module_id' => $this->module->id,
            'nom_complet' => 'Aïssatou Sow',
            'telephone' => '774567890',
            'status' => 'pending',
            'motif_remise_en_attente' => 'Dossier mis en attente pour vérification',
        ]);

        $response = $this->actingAs($this->director)
            ->patch(route('applications.status.update', $application->id), [
                'status' => 'admitted',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'admitted',
            'motif_remise_en_attente' => null,
        ]);

        $this->assertTrue($learner->fresh()->hasRole('Apprenant'));
    }
}
