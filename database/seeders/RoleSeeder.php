<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat Role Super Admin
        $superAdmin = Role::create(['name' => 'super_admin']);
        // Super Admin mendapatkan semua permissions
        $superAdmin->givePermissionTo(Permission::all());

        // Buat Role Admin
        $admin = Role::create(['name' => 'admin']);
        // Admin mendapatkan semua permissions kecuali manage users
        $admin->givePermissionTo([
            'view dashboard',
            'export data',
            'view unit kerja',
            'create unit kerja',
            'edit unit kerja',
            'delete unit kerja',
            'view formasi',
            'create formasi',
            'edit formasi',
            'delete formasi',
            'view pegawai',
            'create pegawai',
            'edit pegawai',
            'delete pegawai',
        ]);

        // Buat Role Operator
        $operator = Role::create(['name' => 'operator']);
        // Operator hanya bisa view dan create
        $operator->givePermissionTo([
            'view dashboard',
            'export data',
            'view unit kerja',
            'create unit kerja',
            'view formasi',
            'create formasi',
            'view pegawai',
            'create pegawai',
        ]);

        // Buat Role Viewer
        $viewer = Role::create(['name' => 'viewer']);
        // Viewer hanya bisa view dan export
        $viewer->givePermissionTo([
            'view dashboard',
            'export data',
            'view unit kerja',
            'view formasi',
            'view pegawai',
        ]);
    }
}
