<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if (!$user) { 
    $user = \App\Models\User::factory()->create();
    $user->assignRole('Directeur');
}

// Generate an exact POST request
$request = \Illuminate\Http\Request::create('/community', 'POST', [
    'title' => 'HTTP Post 1',
    'content' => 'Content 1',
    'category' => 'info',
    'is_pinned' => '0',
    'is_anonymous' => '0',
    'expires_at' => '2027-01-01T10:00',
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Since we are not triggering full middleware, we directly instantiate controller
$controller = new \App\Http\Controllers\AnnouncementController();

// Authorize logic might need auth()->login()
auth()->login($user);

$controller->store($request);
echo "Created 1\n";

$request2 = \Illuminate\Http\Request::create('/community', 'POST', [
    'title' => 'HTTP Post 2',
    'content' => 'Content 2',
    'category' => 'info',
    'is_pinned' => '0',
    'is_anonymous' => '0',
    'expires_at' => '2027-01-01T10:00',
]);
$request2->setUserResolver(function () use ($user) {
    return $user;
});
$controller->store($request2);
echo "Created 2\n";

$announcements = \App\Models\Announcement::all();
echo "Total Announcements: " . $announcements->count() . "\n";
foreach($announcements as $a) {
    echo "ID: {$a->id}, Title: {$a->title}\n";
}
