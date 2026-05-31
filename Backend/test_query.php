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

$userRoles = $user->getRoleNames()->toArray();

$announcements = \App\Models\Announcement::with('user:id,name,email,profile_photo_path')
    ->where(function ($query) use ($userRoles) {
        $query->whereNull('visibility_roles')
              ->orWhereJsonContains('visibility_roles', $userRoles);
    })
    ->where(function ($query) {
        $query->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
    })
    ->orderByDesc('is_pinned')
    ->orderByDesc('created_at')
    ->paginate(15);

echo "Retrieved Count: " . $announcements->count() . "\n";
foreach($announcements as $a) {
    echo "ID: {$a->id}, Title: {$a->title}, VisRoles: " . json_encode($a->visibility_roles) . "\n";
}
