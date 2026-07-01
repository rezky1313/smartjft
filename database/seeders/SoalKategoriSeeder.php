<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoalKategori;

class SoalKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            // Kategori Umum
            ['nama' => 'Umum', 'jabatan' => null, 'jenjang' => 'umum', 'matra' => 'umum', 'bidang' => 'Umum'],

            // PKB (Penguji Kendaraan Bermotor) — Darat
            ['nama' => 'PKB Terampil - Darat',     'jabatan' => 'Penguji Kendaraan Bermotor', 'jenjang' => 'terampil',     'matra' => 'darat', 'bidang' => 'Teknis'],
            ['nama' => 'PKB Mahir - Darat',         'jabatan' => 'Penguji Kendaraan Bermotor', 'jenjang' => 'mahir',        'matra' => 'darat', 'bidang' => 'Teknis'],
            ['nama' => 'PKB Penyelia - Darat',      'jabatan' => 'Penguji Kendaraan Bermotor', 'jenjang' => 'penyelia',     'matra' => 'darat', 'bidang' => 'Teknis'],
            ['nama' => 'PKB Ahli Pertama - Darat',  'jabatan' => 'Penguji Kendaraan Bermotor', 'jenjang' => 'ahli_pertama', 'matra' => 'darat', 'bidang' => 'Teknis'],
            ['nama' => 'PKB Ahli Muda - Darat',     'jabatan' => 'Penguji Kendaraan Bermotor', 'jenjang' => 'ahli_muda',    'matra' => 'darat', 'bidang' => 'Teknis'],

            // PPNS (Penyidik Pegawai Negeri Sipil) — Darat
            ['nama' => 'PPNS Ahli Pertama - Darat', 'jabatan' => 'Penyidik Pegawai Negeri Sipil', 'jenjang' => 'ahli_pertama', 'matra' => 'darat', 'bidang' => 'Hukum'],
            ['nama' => 'PPNS Ahli Muda - Darat',    'jabatan' => 'Penyidik Pegawai Negeri Sipil', 'jenjang' => 'ahli_muda',    'matra' => 'darat', 'bidang' => 'Hukum'],

            // Pengawas Keselamatan Pelayaran — Laut
            ['nama' => 'Pengawas Keselamatan Terampil - Laut',     'jabatan' => 'Pengawas Keselamatan Pelayaran', 'jenjang' => 'terampil',     'matra' => 'laut', 'bidang' => 'Teknis'],
            ['nama' => 'Pengawas Keselamatan Ahli Pertama - Laut', 'jabatan' => 'Pengawas Keselamatan Pelayaran', 'jenjang' => 'ahli_pertama', 'matra' => 'laut', 'bidang' => 'Teknis'],
        ];

        foreach ($kategoriList as $data) {
            SoalKategori::firstOrCreate(
                ['nama' => $data['nama']],
                array_merge($data, ['aktif' => true])
            );
        }
    }
}
