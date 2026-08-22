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

    public function test_assistant_trainer_cannot_create_update_or_delete_modules()
    {
        $assistant = User::factory()->create();

        $module = Module::create([
            'code_module' => 'DEV-99',
            'titre' => 'Module Ancien',
            'quota_heures' => 10,
        ]);

        // Set the user as a trainer by linking them as a formateur of a group
        \App\Models\Group::create([
            'nom_groupe' => 'Groupe Test Assistant',
            'formateur_id' => $assistant->id,
            'module_id' => $module->id,
            'annee_academique' => '2025-2026',
        ]);

        $this->assertTrue($assistant->isTrainer());

        // 1. Create (store) should fail with 403
        $response = $this->actingAs($assistant)->post(route('modules.store'), [
            'code_module' => 'NEW-01',
            'titre' => 'Nouveau',
            'quota_heures' => 30,
        ]);
        $response->assertStatus(403);

        // 2. Update should fail with 403
        $response = $this->actingAs($assistant)->put(route('modules.update', $module->id), [
            'code_module' => 'DEV-99',
            'titre' => 'Module Modifie',
            'quota_heures' => 12,
        ]);
        $response->assertStatus(403);

        // 3. Delete (destroy) should fail with 403
        $response = $this->actingAs($assistant)->delete(route('modules.destroy', $module->id));
        $response->assertStatus(403);
    }

    public function test_trainer_proposing_chapter_sends_notification_to_director()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = \App\Models\Module::create([
            'code_module' => 'DEV-99',
            'titre' => 'Module Ancien',
            'quota_heures' => 10,
        ]);

        // 1. Trainer stores a chapter -> should notify Director
        $response = $this->actingAs($trainer)->post(route('modules.chapters.store', $module->id), [
            'titre' => 'Chapitre Propose 1',
            'content' => 'Description du cours 1',
        ]);
        $response->assertStatus(302);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $director,
            \App\Notifications\ChapterProposedNotification::class,
            function ($notification) use ($module) {
                return $notification->chapter->titre === 'Chapitre Propose 1' && $notification->chapter->module_id === $module->id;
            }
        );

        // 2. Trainer updates a chapter -> should notify Director
        $chapter = \App\Models\Chapter::where('titre', 'Chapitre Propose 1')->first();
        $response = $this->actingAs($trainer)->post(route('modules.chapters.update', $chapter->id), [
            'titre' => 'Chapitre Modifie',
            'content' => 'Description modifiee',
        ]);
        $response->assertStatus(302);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $director,
            \App\Notifications\ChapterProposedNotification::class,
            function ($notification) use ($module) {
                return $notification->chapter->titre === 'Chapitre Modifie' && $notification->chapter->module_id === $module->id;
            }
        );
    }

    public function test_unapproved_chapter_cannot_be_published_to_public()
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = \App\Models\Module::create([
            'code_module' => 'DEV-100',
            'titre' => 'Module Web',
            'quota_heures' => 20,
        ]);

        // Trainer creates chapter with is_published = true
        $this->actingAs($trainer)->post(route('modules.chapters.store', $module->id), [
            'titre' => 'Chapitre Non Valide',
            'content' => 'Contenu du cours',
            'is_published' => true,
        ]);

        $chapter = \App\Models\Chapter::where('titre', 'Chapitre Non Valide')->first();

        // Must be unapproved and unpublished
        $this->assertFalse((bool)$chapter->is_approved);
        $this->assertFalse((bool)$chapter->is_published);

        // Attempting to force update is_published to true on unapproved chapter returns error
        $response = $this->actingAs($trainer)->post(route('modules.chapters.update', $chapter->id), [
            'titre' => 'Chapitre Non Valide',
            'is_published' => true,
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse((bool)$chapter->fresh()->is_published);
    }

    public function test_trainer_modifying_phase_resets_chapter_approval()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = \App\Models\Module::create([
            'code_module' => 'DEV-101',
            'titre' => 'Module Phase Test',
            'quota_heures' => 15,
        ]);

        $chapter = \App\Models\Chapter::create([
            'module_id' => $module->id,
            'titre' => 'Chapitre Initial Approuvé',
            'is_approved' => true,
            'is_published' => true,
            'ordre' => 1,
        ]);

        // Trainer stores a phase on an approved chapter
        $this->actingAs($trainer)->post(route('chapters.phases.store', $chapter->id), [
            'titre' => 'Nouvelle Phase',
            'description' => 'Description de la phase',
        ]);

        $this->assertFalse((bool)$chapter->fresh()->is_approved);
        $this->assertFalse((bool)$chapter->fresh()->is_published);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $director,
            \App\Notifications\ChapterProposedNotification::class
        );
    }

    public function test_trainer_cannot_modify_schedules()
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('Formateur');

        $module = \App\Models\Module::create([
            'code_module' => 'DEV-102',
            'titre' => 'Module Test Group',
            'quota_heures' => 10,
        ]);

        $group = \App\Models\Group::create([
            'nom_groupe' => 'Groupe Schedule Test',
            'module_id' => $module->id,
            'formateur_id' => $trainer->id,
            'annee_academique' => '2025-2026',
        ]);

        $room = \App\Models\Room::create([
            'nom' => 'Salle S1',
            'capacite' => 20,
            'type_salle' => 'cours',
        ]);

        $schedule = \App\Models\Schedule::create([
            'group_id' => $group->id,
            'room_id' => $room->id,
            'formateur_id' => $trainer->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        // 1. Store schedule -> 403
        $response = $this->actingAs($trainer)->post(route('schedules.store'), [
            'group_id' => $group->id,
            'room_id' => $room->id,
            'formateur_id' => $trainer->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);
        $response->assertStatus(403);

        // 2. Update schedule -> 403
        $response = $this->actingAs($trainer)->put(route('schedules.update', $schedule->id), [
            'group_id' => $group->id,
            'room_id' => $room->id,
            'formateur_id' => $trainer->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);
        $response->assertStatus(403);

        // 3. Destroy schedule -> 403
        $response = $this->actingAs($trainer)->delete(route('schedules.destroy', $schedule->id));
        $response->assertStatus(403);
    }
}
