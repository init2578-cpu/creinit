<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Announcement;
use App\Models\AnnouncementReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AnnouncementAnonymityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // create roles
        Role::firstOrCreate(['name' => 'Directeur']);
        Role::firstOrCreate(['name' => 'Apprenant']);
    }

    public function test_anonymous_announcement_hides_user_id_for_non_directors()
    {
        $director = User::factory()->create();
        $director->assignRole('Directeur');

        $student = User::factory()->create();
        $student->assignRole('Apprenant');

        $author = User::factory()->create();
        $author->assignRole('Apprenant');

        $announcement = Announcement::create([
            'user_id' => $author->id,
            'title' => 'Test Anonymous',
            'content' => 'Content',
            'category' => 'info',
            'is_anonymous' => true,
        ]);

        $reply = AnnouncementReply::create([
            'announcement_id' => $announcement->id,
            'user_id' => $author->id,
            'content' => 'Reply by author',
        ]);

        // Non-director views the announcement
        $response = $this->actingAs($student)->get('/community');
        
        $response->assertStatus(200);
        $page = $response->viewData('page');
        
        $announcements = $page['props']['announcements']['data'];
        $announcementData = collect($announcements)->firstWhere('id', $announcement->id);
        
        $this->assertArrayNotHasKey('user', $announcementData);
        $this->assertArrayNotHasKey('user_id', $announcementData);

        // Check reply
        $replyData = collect($announcementData['replies'])->firstWhere('id', $reply->id);
        $this->assertArrayNotHasKey('user', $replyData);
        $this->assertArrayNotHasKey('user_id', $replyData);

        // Director views the announcement
        $response = $this->actingAs($director)->get('/community');
        $page = $response->viewData('page');
        
        $announcements = $page['props']['announcements']['data'];
        $announcementData = collect($announcements)->firstWhere('id', $announcement->id);
        
        $this->assertNotNull($announcementData['user']);
        $this->assertEquals($author->id, $announcementData['user']['id']);
        $this->assertArrayHasKey('user_id', $announcementData);
    }
}
