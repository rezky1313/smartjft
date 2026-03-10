<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USER DATABASE CHECK ===" . PHP_EOL;
echo "Tanggal: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

$users = App\Models\User::with('roles')->orderBy('id')->get();

if ($users->isEmpty()) {
    echo "❌ TIDAK ADA USER DI DATABASE!" . PHP_EOL;
    exit(1);
}

echo "Total User: " . $users->count() . PHP_EOL;
echo PHP_EOL;

foreach ($users as $user) {
    echo "┌─────────────────────────────────────────────────────────────" . PHP_EOL;
    echo "│ ID          : " . $user->id . PHP_EOL;
    echo "│ Name        : " . $user->name . PHP_EOL;
    echo "│ Email       : " . $user->email . PHP_EOL;
    echo "│ Status      : " . $user->status . PHP_EOL;

    $roles = $user->roles->pluck('name');
    if ($roles->isEmpty()) {
        echo "│ Roles       : ❌ TIDAK ADA ROLE!" . PHP_EOL;
    } else {
        echo "│ Roles       : " . $roles->implode(', ') . PHP_EOL;
    }

    echo "│ Created At  : " . $user->created_at . PHP_EOL;
    echo "└─────────────────────────────────────────────────────────────" . PHP_EOL;
    echo PHP_EOL;
}

echo "=== ACCOUNT DEFAULT UNTUK TESTING ===" . PHP_EOL;
echo PHP_EOL;

$defaultAccounts = [
    'superadmin@pusbin.go.id',
    'admin@pusbin.go.id',
    'operator@pusbin.go.id',
    'viewer@pusbin.go.id',
];

foreach ($defaultAccounts as $email) {
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        $roles = $user->roles->pluck('name')->implode(', ') ?: '❌ NO ROLE';
        echo "✅ {$email}" . PHP_EOL;
        echo "   Password: password123" . PHP_EOL;
        echo "   Role: {$roles}" . PHP_EOL;
        echo "   Status: {$user->status}" . PHP_EOL;
        echo PHP_EOL;
    } else {
        echo "❌ {$email} - TIDAK DITEMUKAN!" . PHP_EOL;
        echo PHP_EOL;
    }
}

echo "=== SELESAI ===" . PHP_EOL;
