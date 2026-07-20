<?php

namespace Tests\Feature;

use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationCniUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_apply_with_separate_cni_recto_and_verso_photos(): void
    {
        Storage::fake('private');

        $module = Module::create([
            'titre' => 'Dev Web',
            'code_module' => 'DEVWEB',
            'quota_heures' => 100,
            'is_active' => true,
        ]);

        $response = $this->post(route('applications.store'), [
            'module_id' => $module->id,
            'nom_complet' => 'Awa Sow',
            'telephone' => '771234567',
            'email' => 'awa@example.com',
            'adresse_reelle' => 'Kolda',
            'date_naissance' => '2000-01-01',
            'lieu_naissance' => 'Kolda',
            'niveau_etude' => 'Licence',
            'dernier_diplome_libelle' => 'Licence Info',
            'fonction' => 'Etudiante',
            'sexe' => 'F',
            'cni_recto' => UploadedFile::fake()->image('cni_recto.jpg'),
            'cni_verso' => UploadedFile::fake()->image('cni_verso.jpg'),
            'diploma' => UploadedFile::fake()->create('diploma.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'nom_complet' => 'Awa Sow',
            'telephone' => '771234567',
            'sexe' => 'F',
        ]);

        $application = \App\Models\Application::where('telephone', '771234567')->first();
        $this->assertNotNull($application->cni_recto_path);
        $this->assertNotNull($application->cni_verso_path);

        Storage::disk('private')->assertExists($application->cni_recto_path);
        Storage::disk('private')->assertExists($application->cni_verso_path);
    }

    public function test_candidate_can_apply_without_cni_using_birth_certificate(): void
    {
        Storage::fake('private');

        $module = Module::create([
            'titre' => 'Gestion de Projet',
            'code_module' => 'GESPRO',
            'quota_heures' => 60,
            'is_active' => true,
        ]);

        $response = $this->post(route('applications.store'), [
            'module_id' => $module->id,
            'nom_complet' => 'Mamadou Diallo',
            'telephone' => '789876543',
            'email' => 'mamadou@example.com',
            'adresse_reelle' => 'Kolda Centre',
            'date_naissance' => '2005-05-15',
            'lieu_naissance' => 'Kolda',
            'niveau_etude' => 'Baccalauréat',
            'dernier_diplome_libelle' => 'BAC L2',
            'fonction' => 'Élève',
            'sexe' => 'M',
            'has_cni' => '0',
            'other_identity_doc' => UploadedFile::fake()->image('extrait_naissance.jpg'),
            'diploma' => UploadedFile::fake()->create('diploma.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'nom_complet' => 'Mamadou Diallo',
            'telephone' => '789876543',
            'has_cni' => false,
        ]);

        $application = \App\Models\Application::where('telephone', '789876543')->first();
        $this->assertFalse($application->has_cni);
        $this->assertNotNull($application->other_identity_doc_path);

        Storage::disk('private')->assertExists($application->other_identity_doc_path);
    }
}
