<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DuplicateRegistrationPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_register_online_with_existing_user_phone_or_email(): void
    {
        Storage::fake('private');

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'telephone' => '77 123 45 67',
        ]);

        $module = Module::create([
            'titre' => 'Module Test',
            'code_module' => 'MOD-TEST-1',
            'quota_heures' => 50,
            'is_active' => true,
        ]);

        $response = $this->post(route('applications.store'), [
            'module_id' => $module->id,
            'nom_complet' => 'Nouveau Candidat',
            'email' => 'nouveau@example.com',
            'telephone' => '771234567', // Same phone without spaces
            'adresse_reelle' => 'Dakar',
            'date_naissance' => '2000-01-01',
            'lieu_naissance' => 'Dakar',
            'niveau_etude' => 'Bac',
            'dernier_diplome_libelle' => 'Baccalauréat',
            'fonction' => 'Étudiant',
            'sexe' => 'M',
            'has_cni' => true,
            'cni_recto' => UploadedFile::fake()->create('recto.jpg', 100),
            'cni_verso' => UploadedFile::fake()->create('verso.jpg', 100),
            'diploma' => UploadedFile::fake()->create('diploma.pdf', 100),
        ]);

        $response->assertSessionHasErrors(['telephone']);
    }

    public function test_cannot_register_online_with_existing_application_email(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['email' => 'candidat1@example.com', 'telephone' => '780000001']);
        $module = Module::create([
            'titre' => 'Module Test 2',
            'code_module' => 'MOD-TEST-2',
            'quota_heures' => 50,
            'is_active' => true,
        ]);

        Application::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'nom_complet' => 'Candidat 1',
            'telephone' => '780000001',
            'adresse_reelle' => 'Dakar',
            'date_naissance' => '2000-01-01',
            'lieu_naissance' => 'Dakar',
            'niveau_etude' => 'Bac',
            'dernier_diplome_libelle' => 'Baccalauréat',
            'fonction' => 'Étudiant',
            'sexe' => 'M',
            'cni_path' => 'cni.pdf',
            'diploma_path' => 'diploma.pdf',
            'status' => 'pending',
        ]);

        $response = $this->post(route('applications.store'), [
            'module_id' => $module->id,
            'nom_complet' => 'Candidat 2',
            'email' => 'Candidat1@Example.com', // Duplicate email (case-insensitive)
            'telephone' => '789999999',
            'adresse_reelle' => 'Dakar',
            'date_naissance' => '2000-01-01',
            'lieu_naissance' => 'Dakar',
            'niveau_etude' => 'Bac',
            'dernier_diplome_libelle' => 'Baccalauréat',
            'fonction' => 'Étudiant',
            'sexe' => 'M',
            'has_cni' => true,
            'cni_recto' => UploadedFile::fake()->create('recto.jpg', 100),
            'cni_verso' => UploadedFile::fake()->create('verso.jpg', 100),
            'diploma' => UploadedFile::fake()->create('diploma.pdf', 100),
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
