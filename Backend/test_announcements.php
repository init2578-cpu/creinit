<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if (!$user) { 
    $user = \App\Models\User::factory()->create(); 
}

echo "Before count: " . \App\Models\Announcement::count() . "\n";

$user->announcements()->create([
    'title' => 'Test 1', 
    'content' => 'c1', 
    'category' => 'info', 
    'visibility_roles' => null
]);
echo "After first count: " . \App\Models\Announcement::count() . "\n";

sleep(1);

$user->announcements()->create([
    'title' => 'Test 2', 
    'content' => 'c2', 
    'category' => 'info', 
    'visibility_roles' => null
]);
echo "After second count: " . \App\Models\Announcement::count() . "\n";

$all = \App\Models\Announcement::all();
foreach ($all as $m) {
    echo "ID: {$m->id}, Title: {$m->title}, User: {$m->user_id}\n";
}
