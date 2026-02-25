<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sdmmodels;

class SdmFromExcelSeeder20250825073421 extends Seeder
{
    public function run(): void
    {
        $rows = array (
  0 => 
  array (
    'nip' => '199901132020011008',
    'nik' => NULL,
    'nama_lengkap' => 'Budianto',
    'jenis_kelamin' => 'L',
    'pendidikan_terakhir' => 'S1',
    'pangkat_golongan' => 'III/a',
    'status_kepegawaian' => 'PNS',
    'formasi_jabatan_id' => 11,
    'unit_kerja_id' => 33,
    'tmt_pengangkatan' => NULL,
    'aktif' => 1,
    'created_at' => '2025-08-25 07:34:21',
    'updated_at' => '2025-08-25 07:34:21',
  ),
  1 => 
  array (
    'nip' => '199801132020021001',
    'nik' => NULL,
    'nama_lengkap' => 'Adrian',
    'jenis_kelamin' => 'L',
    'pendidikan_terakhir' => 'D3',
    'pangkat_golongan' => 'II/c',
    'status_kepegawaian' => 'PNS',
    'formasi_jabatan_id' => 9,
    'unit_kerja_id' => 33,
    'tmt_pengangkatan' => NULL,
    'aktif' => 1,
    'created_at' => '2025-08-25 07:34:21',
    'updated_at' => '2025-08-25 07:34:21',
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
