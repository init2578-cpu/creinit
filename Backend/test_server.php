<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/modules', 'GET');
$app->instance('request', $request);

$kernel->bootstrap();

$user = App\Models\User::role('Formateur')->first();
if (!$user) {
    echo "No Formateur user found!\n";
    exit;
}

echo "Testing as user: " . $user->name . " (ID: " . $user->id . ", Role: Formateur)\n";
$user->must_change_password = false;
Auth::login($user);

// 1. GET /modules
$response = $kernel->handle(Illuminate\Http\Request::create('/modules', 'GET'));
echo "GET /modules Status: " . $response->getStatusCode() . "\n";

// 2. POST /modules (Store module)
$randomCode = 'TEST-' . rand(1000, 9999);
$storeReq = Illuminate\Http\Request::create('/modules', 'POST', [
    'titre' => 'Test Module',
    'code_module' => $randomCode,
    'quota_heures' => 10,
    'is_active' => true,
]);
$response = $kernel->handle($storeReq);
echo "POST /modules Status: " . $response->getStatusCode() . "\n";

// Find the created module
$module = App\Models\Module::where('code_module', $randomCode)->first();
if ($module) {
    echo "Module created successfully! ID: " . $module->id . "\n";
    
    // 3. POST /modules/{module}/chapters (Store chapter)
    $chapterReq = Illuminate\Http\Request::create("/modules/{$module->id}/chapters", 'POST', [
        'titre' => 'Test Chapter 1',
        'is_published' => true,
    ]);
    $response = $kernel->handle($chapterReq);
    echo "POST /modules/{module}/chapters Status: " . $response->getStatusCode() . "\n";
    
    // Find the chapter
    $chapter = $module->chapters()->first();
    if ($chapter) {
        echo "Chapter created successfully! ID: " . $chapter->id . "\n";
        
        // Cleanup chapter
        $chapter->delete();
        echo "Cleaned up chapter.\n";
    }
    
    // Cleanup module
    $module->delete();
    echo "Cleaned up module.\n";
} else {
    echo "Failed to find created module in DB!\n";
}
