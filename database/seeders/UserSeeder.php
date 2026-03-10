<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus semua user yang ada (optional - hapus baris ini jika tidak ingin menghapus data user yang sudah ada)
        // User::query()->delete();

        $userData = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pusbin.go.id',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@pusbin.go.id',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'role' => 'admin',
            ],
            [
                'name' => 'Operator',
                'email' => 'operator@pusbin.go.id',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'role' => 'operator',
            ],
            [
                'name' => 'Viewer',
                'email' => 'viewer@pusbin.go.id',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'role' => 'viewer',
            ],
        ];

        foreach($userData as $data){
            // Buat user tanpa role
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'status' => $data['status'],
                ]
            );

            // Assign role menggunakan Spatie
            $user->assignRole($data['role']);
        }

        $this->command->info('Akun default berhasil dibuat:');
        $this->command->info('1. superadmin@pusbin.go.id / password123 (Super Admin)');
        $this->command->info('2. admin@pusbin.go.id / password123 (Admin)');
        $this->command->info('3. operator@pusbin.go.id / password123 (Operator)');
        $this->command->info('4. viewer@pusbin.go.id / password123 (Viewer)');
    }
}
