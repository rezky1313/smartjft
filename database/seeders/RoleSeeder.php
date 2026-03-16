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
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        // Super Admin mendapatkan semua permissions
        $superAdmin->syncPermissions(Permission::all());

        // Buat Role Admin
        $admin = Role::firstOrCreate(['name' => 'admin']);
        // Admin mendapatkan semua permissions kecuali manage users
        $admin->syncPermissions([
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
            'view ujikom',
            'create ujikom',
            'edit ujikom',
            'delete ujikom',
            'verifikasi ujikom',
            'input hasil ujikom',
            'view pengangkatan',
            'create pengangkatan',
            'edit pengangkatan',
            'delete pengangkatan',
            'verifikasi pengangkatan',
        ]);

        // Buat Role Operator
        $operator = Role::firstOrCreate(['name' => 'operator']);
        // Operator hanya bisa view dan create
        $operator->syncPermissions([
            'view dashboard',
            'export data',
            'view unit kerja',
            'create unit kerja',
            'view formasi',
            'create formasi',
            'view pegawai',
            'create pegawai',
            'view ujikom',
            'create ujikom',
            'view pengangkatan',
            'create pengangkatan',
            'edit pengangkatan',
            'delete pengangkatan',
        ]);

        // Buat Role Viewer
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        // Viewer hanya bisa view dan export
        $viewer->syncPermissions([
            'view dashboard',
            'export data',
            'view unit kerja',
            'view formasi',
            'view pegawai',
            'view ujikom',
            'view pengangkatan',
        ]);
    }
}
