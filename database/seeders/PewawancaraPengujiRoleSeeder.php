<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PewawancaraPengujiRoleSeeder extends Seeder
{
    /**
     * UJ-ROLE: role pewawancara & penguji — permission identik, murni beda label/gelar.
     * Dibatasi HANYA ke fitur nilai manual Wawancara/Presentasi Ujikom Online.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view nilai manual ujikom',
            'input nilai manual ujikom',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        $pewawancara = Role::findOrCreate('pewawancara');
        $pewawancara->syncPermissions($permissions);

        $penguji = Role::findOrCreate('penguji');
        $penguji->syncPermissions($permissions);

        $this->command->info('Role pewawancara & penguji berhasil dibuat/diupdate.');
    }
}
