<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Group;
use App\Models\Module;
use App\Models\Chapter;
use App\Models\Question;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class SecretaryAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup default roles if they do not exist
        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Secrétaire']);
        Role::firstOrCreate(['name' => 'Apprenant']);
        Role::firstOrCreate(['name' => 'Formateur']);
    }

    public function test_secretary_can_view_exams_list_without_results()
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen Test',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'user_id' => $secretary->id
        ]);

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $result = ExamResult::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'score' => 15,
            'finished_at' => now()
        ]);

        $response = $this->actingAs($secretary)->get(route('exams.index'));

        $response->assertStatus(200);
        
        // Assert exams are returned but examResults are not loaded for secretary
        $response->assertInertia(fn (Assert $page) => $page->has('exams', 1));
        
        $exams = $response->viewData('page')['props']['exams'];
        $examArray = is_array($exams) ? $exams[0] : $exams[0]->toArray();
        $this->assertArrayNotHasKey('exam_results', $examArray);
        $this->assertArrayNotHasKey('examResults', $examArray);
    }

    public function test_secretary_cannot_perform_exam_modification_actions()
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40
        ]);

        $exam = Exam::create([
            'module_id' => $module->id,
            'titre' => 'Examen Test',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
            'user_id' => $secretary->id
        ]);

        // 1. Store
        $response = $this->actingAs($secretary)->post(route('exams.store'), [
            'module_id' => $module->id,
            'titre' => 'Nouveau',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);
        $response->assertStatus(403);

        // 2. Update
        $response = $this->actingAs($secretary)->put(route('exams.update', $exam->id), [
            'module_id' => $module->id,
            'titre' => 'Modifié',
            'type' => 'online',
            'duree_minutes' => 60,
            'total_points' => 20,
        ]);
        $response->assertStatus(403);

        // 3. Destroy
        $response = $this->actingAs($secretary)->delete(route('exams.destroy', $exam->id));
        $response->assertStatus(403);

        // 4. enterGrades
        $response = $this->actingAs($secretary)->post(route('exams.enter-grades', $exam->id), [
            'grades' => []
        ]);
        $response->assertStatus(403);

        // 5. getResults
        $response = $this->actingAs($secretary)->get(route('exams.results', $exam->id));
        $response->assertStatus(403);

        // 6. storeQuestion
        $response = $this->actingAs($secretary)->post(route('exams.questions.store', $exam->id), [
            'enonce' => 'Question?',
            'points' => 5,
            'type' => 'open'
        ]);
        $response->assertStatus(403);
    }

    public function test_secretary_cannot_perform_exercise_modification_actions()
    {
        $secretary = User::factory()->create();
        $secretary->assignRole('Secrétaire');

        $module = Module::create([
            'code_module' => 'M101',
            'titre' => 'Module Test',
            'quota_heures' => 40
        ]);

        $chapter = Chapter::create([
            'module_id' => $module->id,
            'titre' => 'Chapitre Test',
            'ordre' => 1,
            'exercise_type' => 'online',
            'exercise_points' => 20
        ]);

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $submission = ExerciseSubmission::create([
            'chapter_id' => $chapter->id,
            'user_id' => $student->id,
            'status' => 'pending'
        ]);

        $question = Question::create([
            'chapter_id' => $chapter->id,
            'enonce' => 'Q1',
            'points' => 5,
            'type' => 'open',
            'ordre' => 1
        ]);

        // 1. Update Chapter Exercise settings
        $response = $this->actingAs($secretary)->put(route('exercises.update', $chapter->id), [
            'exercise_title' => 'Nouveau titre',
            'exercise_type' => 'file',
            'exercise_points' => 20
        ]);
        $response->assertStatus(403);

        // 2. Grade submission
        $response = $this->actingAs($secretary)->post(route('exercises.grade-submission', $submission->id), [
            'grade' => 18,
            'status' => 'graded'
        ]);
        $response->assertStatus(403);

        // 3. Store question
        $response = $this->actingAs($secretary)->post(route('exercises.questions.store', $chapter->id), [
            'enonce' => 'Nouvelle Q',
            'points' => 5,
            'type' => 'open'
        ]);
        $response->assertStatus(403);

        // 4. Update question
        $response = $this->actingAs($secretary)->patch(route('questions.update', $question->id), [
            'points' => 10,
            'enonce' => 'Mise à jour Q'
        ]);
        $response->assertStatus(403);

        // 5. Destroy question
        $response = $this->actingAs($secretary)->delete(route('questions.destroy', $question->id));
        $response->assertStatus(403);
    }
}
