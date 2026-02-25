<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rumahsakit;

class UnitKerjaFromExcelSeeder20251105040554 extends Seeder
{
    public function run(): void
    {
        $rows = array (
  0 => 
  array (
    'nama_rumahsakit' => 'Balai Pengelola Transportasi Darat Kelas II Sulawesi Utara',
    'alamat' => NULL,
    'no_telp' => NULL,
    'regency_id' => 343,
    'latitude' => 1.4809596686917,
    'longitude' => 124.88718521534,
    'matra' => 'Darat',
    'instansi' => 'Pusat',
    'created_at' => '2025-11-05 04:05:54',
    'updated_at' => '2025-11-05 04:05:54',
  ),
);

        // Upsert idempotent berdasarkan kombinasi (nama_rumahsakit, regency_id)
        foreach (array_chunk($rows, 500) as $chunk) {
            foreach ($chunk as $row) {
                Rumahsakit::updateOrCreate(
                    [
                        'nama_rumahsakit' => $row['nama_rumahsakit'],
                        'regency_id'      => $row['regency_id'],
                    ],
                    $row
                );
            }
        }
    }
}
