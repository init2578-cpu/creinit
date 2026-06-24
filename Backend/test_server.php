<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'youssouphbadji2013@gmail.com')->first();
if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
echo "Roles: " . implode(', ', $user->roles->pluck('name')->toArray()) . "\n";
echo "Permissions: " . implode(', ', $user->getAllPermissions()->pluck('name')->toArray()) . "\n";

// Let's simulate a GET /modules request
$request = Illuminate\Http\Request::create('/modules', 'GET');
$app->instance('request', $request);
$user->must_change_password = false;
Auth::login($user);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() !== 200) {
    echo "Headers:\n" . var_export($response->headers->all(), true) . "\n";
    echo "Content:\n" . substr($response->getContent(), 0, 1000) . "\n";
}
