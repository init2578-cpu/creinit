<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/modules', 'GET');
$app->instance('request', $request);

$kernel->bootstrap();

$users = App\Models\User::role('Formateur')->get();
if ($users->isEmpty()) {
    echo "No users with Formateur role found!\n";
    exit;
}

foreach ($users as $user) {
    echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
    $user->must_change_password = false;
    Auth::login($user);
    $response = $kernel->handle($request);
    echo "  Status: " . $response->getStatusCode() . "\n";
}
