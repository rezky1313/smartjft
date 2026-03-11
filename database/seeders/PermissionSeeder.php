<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permissions untuk Dashboard
        Permission::firstOrCreate(['name' => 'view dashboard']);

        // Permissions untuk Export
        Permission::firstOrCreate(['name' => 'export data']);

        // Permissions untuk Unit Kerja
        Permission::firstOrCreate(['name' => 'view unit kerja']);
        Permission::firstOrCreate(['name' => 'create unit kerja']);
        Permission::firstOrCreate(['name' => 'edit unit kerja']);
        Permission::firstOrCreate(['name' => 'delete unit kerja']);

        // Permissions untuk Formasi
        Permission::firstOrCreate(['name' => 'view formasi']);
        Permission::firstOrCreate(['name' => 'create formasi']);
        Permission::firstOrCreate(['name' => 'edit formasi']);
        Permission::firstOrCreate(['name' => 'delete formasi']);

        // Permissions untuk Pegawai JFT
        Permission::firstOrCreate(['name' => 'view pegawai']);
        Permission::firstOrCreate(['name' => 'create pegawai']);
        Permission::firstOrCreate(['name' => 'edit pegawai']);
        Permission::firstOrCreate(['name' => 'delete pegawai']);

        // Permissions untuk Uji Kompetensi
        Permission::firstOrCreate(['name' => 'view ujikom']);
        Permission::firstOrCreate(['name' => 'create ujikom']);
        Permission::firstOrCreate(['name' => 'edit ujikom']);
        Permission::firstOrCreate(['name' => 'delete ujikom']);
        Permission::firstOrCreate(['name' => 'verifikasi ujikom']);
        Permission::firstOrCreate(['name' => 'input hasil ujikom']);

        // Permissions untuk Manajemen User
        Permission::firstOrCreate(['name' => 'manage users']);
    }
}
