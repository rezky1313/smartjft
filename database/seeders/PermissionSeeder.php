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
        Permission::create(['name' => 'view dashboard']);

        // Permissions untuk Export
        Permission::create(['name' => 'export data']);

        // Permissions untuk Unit Kerja
        Permission::create(['name' => 'view unit kerja']);
        Permission::create(['name' => 'create unit kerja']);
        Permission::create(['name' => 'edit unit kerja']);
        Permission::create(['name' => 'delete unit kerja']);

        // Permissions untuk Formasi
        Permission::create(['name' => 'view formasi']);
        Permission::create(['name' => 'create formasi']);
        Permission::create(['name' => 'edit formasi']);
        Permission::create(['name' => 'delete formasi']);

        // Permissions untuk Pegawai JFT
        Permission::create(['name' => 'view pegawai']);
        Permission::create(['name' => 'create pegawai']);
        Permission::create(['name' => 'edit pegawai']);
        Permission::create(['name' => 'delete pegawai']);

        // Permissions untuk Manajemen User
        Permission::create(['name' => 'manage users']);
    }
}
