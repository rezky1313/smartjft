<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionUpdateSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Definisi semua permissions
        $allPermissions = [
            // Dashboard
            'view dashboard',

            // Unit Kerja
            'view unit kerja', 'create unit kerja', 'edit unit kerja', 'delete unit kerja',

            // Formasi
            'view formasi', 'create formasi', 'edit formasi', 'delete formasi',

            // Pegawai
            'view pegawai', 'create pegawai', 'edit pegawai', 'delete pegawai',

            // Ujikom Jadwal
            'view ujikom jadwal', 'create ujikom jadwal', 'edit ujikom jadwal',
            'publish ujikom jadwal',

            // Ujikom Pendaftaran
            'view ujikom permohonan', 'create ujikom permohonan',
            'verifikasi ujikom permohonan admin unit',
            'verifikasi ujikom permohonan pusbin',

            // Laporan
            'view laporan', 'export laporan',

            // Manajemen User
            'manage users',

            // Rekomendasi Formasi (RF-01) -- fondasi permission, belum ada
            // controller/route yang memakainya di fase ini (RF-1A)
            'view rekomendasi formasi',
            'create rekomendasi formasi',
            'edit rekomendasi formasi',
            'verifikasi rekomendasi formasi',
            'ttd rekomendasi formasi',
        ];

        foreach ($allPermissions as $perm) {
            Permission::findOrCreate($perm);
        }

        // ── SUPER ADMIN — semua permission ──
        $superAdmin = Role::findOrCreate('super_admin');
        $superAdmin->syncPermissions($allPermissions);

        // ── ADMIN PUSBIN ──
        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions([
            'view dashboard',
            'view unit kerja',
            'view formasi', 'create formasi', 'edit formasi',
            'view pegawai', 'create pegawai', 'edit pegawai', 'delete pegawai',
            'view ujikom jadwal', 'create ujikom jadwal', 'edit ujikom jadwal', 'publish ujikom jadwal',
            'view ujikom permohonan',
            'verifikasi ujikom permohonan pusbin',
            'view laporan', 'export laporan',
            'view rekomendasi formasi', 'verifikasi rekomendasi formasi',
        ]);

        // ── ADMIN UNIT ──
        $adminUnit = Role::findOrCreate('admin_unit');
        $adminUnit->syncPermissions([
            // Dashboard
            'view dashboard',

            // Data unit kerjanya
            'view formasi',
            'view pegawai', 'create pegawai', 'edit pegawai',

            // Ujikom — akses penuh di lingkup unit kerjanya
            'view ujikom jadwal',
            'view ujikom permohonan',
            'create ujikom permohonan',
            'verifikasi ujikom permohonan admin unit',

            // Rekomendasi Formasi (RF-01) -- unit kerja/dishub adalah pengusul
            'view rekomendasi formasi', 'create rekomendasi formasi', 'edit rekomendasi formasi',
        ]);

        // ── KABID PERENCANAAN & PEMBENTUKAN JFT (Pusbin) ──
        // Penanda tangan digital pihak Pusbin pada Berita Acara Rekomendasi Formasi.
        $kabidPerencanaanJft = Role::findOrCreate('kabid_perencanaan_jft');
        $kabidPerencanaanJft->syncPermissions([
            'view dashboard',
            'view rekomendasi formasi',
            'verifikasi rekomendasi formasi',
            'ttd rekomendasi formasi',
        ]);

        // ── PEMANGKU JFT ──
        $pemangku = Role::findOrCreate('pemangku');
        $pemangku->syncPermissions([
            'view dashboard',
            'view ujikom jadwal',
            'view ujikom permohonan',
            'create ujikom permohonan',
        ]);

        // ── VIEWER ──
        $viewer = Role::findOrCreate('viewer');
        $viewer->syncPermissions([
            'view dashboard',
            'view unit kerja',
            'view formasi',
            'view pegawai',
            'view ujikom jadwal',
            'view rekomendasi formasi',
        ]);

        // ── HAPUS ROLE OPERATOR — konversi ke admin_unit ──
        if (Role::where('name', 'operator')->exists()) {
            $usersOperator = \App\Models\User::role('operator')->get();
            foreach ($usersOperator as $user) {
                $user->removeRole('operator');
                $user->assignRole('admin_unit');
            }
            Role::findByName('operator')->delete();
            $this->command->info('Role operator dikonversi ke admin_unit (' . $usersOperator->count() . ' user).');
        }

        $this->command->info('Role & Permission berhasil diupdate!');
        $this->command->table(
            ['Role', 'Jumlah Permission'],
            Role::with('permissions')->get()->map(fn($r) => [
                $r->name,
                $r->permissions->count(),
            ])->toArray()
        );
    }
}
