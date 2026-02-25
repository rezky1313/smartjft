<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Formasijabatan;

class FormasiFromExcelSeeder20250826144436 extends Seeder
{
    public function run(): void
    {
        $rows = array (
  0 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Pemula',
    'jenjang_id' => 9,
    'unit_kerja_id' => 34,
    'kuota' => 2,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  1 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Terampil',
    'jenjang_id' => 10,
    'unit_kerja_id' => 34,
    'kuota' => 4,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  2 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Mahir',
    'jenjang_id' => 11,
    'unit_kerja_id' => 34,
    'kuota' => 9,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  3 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Penyelia',
    'jenjang_id' => 12,
    'unit_kerja_id' => 34,
    'kuota' => 4,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  4 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Pemula',
    'jenjang_id' => 9,
    'unit_kerja_id' => 35,
    'kuota' => 1,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  5 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Terampil',
    'jenjang_id' => 10,
    'unit_kerja_id' => 35,
    'kuota' => 3,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  6 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Mahir',
    'jenjang_id' => 11,
    'unit_kerja_id' => 35,
    'kuota' => 8,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  7 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Penyelia',
    'jenjang_id' => 12,
    'unit_kerja_id' => 35,
    'kuota' => 5,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  8 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Pemula',
    'jenjang_id' => 9,
    'unit_kerja_id' => 37,
    'kuota' => 1,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  9 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Terampil',
    'jenjang_id' => 10,
    'unit_kerja_id' => 37,
    'kuota' => 6,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  10 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Mahir',
    'jenjang_id' => 11,
    'unit_kerja_id' => 37,
    'kuota' => 3,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
  11 => 
  array (
    'nama_formasi' => 'Penguji Kendaraan Bermotor Penyelia',
    'jenjang_id' => 12,
    'unit_kerja_id' => 37,
    'kuota' => 5,
    'tahun_formasi' => 0,
    'created_at' => '2025-08-26 14:44:36',
    'updated_at' => '2025-08-26 14:44:36',
  ),
);

        foreach (array_chunk($rows, 500) as $chunk) {
            foreach ($chunk as $row) {
                // Jika tahun_formasi ada -> jadikan bagian kunci unik
                $keys = [
                    'unit_kerja_id' => $row['unit_kerja_id'],
                    'jenjang_id'    => $row['jenjang_id'],
                ];
                if (!is_null($row['tahun_formasi'])) {
                    $keys['tahun_formasi'] = $row['tahun_formasi'];
                }

                Formasijabatan::updateOrCreate($keys, $row);
            }
        }
    }
}
