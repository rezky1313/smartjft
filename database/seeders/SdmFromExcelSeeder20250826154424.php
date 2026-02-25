<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sdmmodels;

class SdmFromExcelSeeder20250826154424 extends Seeder
{
    public function run(): void
    {
        $rows = array (
  0 => 
  array (
    'nip' => '198703232009041001',
    'nik' => NULL,
    'nama_lengkap' => 'Ahmad Syekhu, A.Ma.',
    'jenis_kelamin' => 'L',
    'pendidikan_terakhir' => NULL,
    'pangkat_golongan' => 'III/a',
    'status_kepegawaian' => 'PNS',
    'formasi_jabatan_id' => NULL,
    'unit_kerja_id' => NULL,
    'tmt_pengangkatan' => NULL,
    'aktif' => 1,
    'created_at' => '2025-08-26 15:44:24',
    'updated_at' => '2025-08-26 15:44:24',
  ),
  1 => 
  array (
    'nip' => '198801302009041001',
    'nik' => NULL,
    'nama_lengkap' => 'Slamet Andhi Soemarsono, A.Ma.',
    'jenis_kelamin' => 'L',
    'pendidikan_terakhir' => NULL,
    'pangkat_golongan' => 'III/a',
    'status_kepegawaian' => 'PNS',
    'formasi_jabatan_id' => NULL,
    'unit_kerja_id' => NULL,
    'tmt_pengangkatan' => NULL,
    'aktif' => 1,
    'created_at' => '2025-08-26 15:44:24',
    'updated_at' => '2025-08-26 15:44:24',
  ),
  2 => 
  array (
    'nip' => '199309052019021002',
    'nik' => NULL,
    'nama_lengkap' => 'Andika Suseno, A.Md PКВ.',
    'jenis_kelamin' => 'L',
    'pendidikan_terakhir' => NULL,
    'pangkat_golongan' => 'II/d',
    'status_kepegawaian' => 'PNS',
    'formasi_jabatan_id' => 41,
    'unit_kerja_id' => 184,
    'tmt_pengangkatan' => NULL,
    'aktif' => 1,
    'created_at' => '2025-08-26 15:44:24',
    'updated_at' => '2025-08-26 15:44:24',
  ),
);

        // Idempotent by NIP (updateOrCreate), agar aman dijalankan berulang
        foreach (array_chunk($rows, 500) as $chunk) {
            foreach ($chunk as $row) {
                // Jika NIP kosong, pakai create biasa
                if (!empty($row['nip'])) {
                    Sdmmodels::withTrashed()->updateOrCreate(
                        ['nip' => $row['nip']],
                        $row
                    );
                } else {
                    Sdmmodels::create($row);
                }
            }
        }
    }
}
