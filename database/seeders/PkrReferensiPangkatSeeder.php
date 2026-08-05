<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PkrReferensiPangkat;

class PkrReferensiPangkatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Kategori (keterampilan/keahlian) sengaja dibiarkan null: golongan/ruang PNS
     * bersifat umum lintas jalur JF (keterampilan maupun keahlian bisa memakai
     * golongan yang sama), jadi tidak ada pemetaan 1:1 yang valid di level tabel ini.
     */
    public function run(): void
    {
        $pangkat = [
            [1, 'I/a', 'Juru Muda'],
            [2, 'I/b', 'Juru Muda Tingkat I'],
            [3, 'I/c', 'Juru'],
            [4, 'I/d', 'Juru Tingkat I'],
            [5, 'II/a', 'Pengatur Muda'],
            [6, 'II/b', 'Pengatur Muda Tingkat I'],
            [7, 'II/c', 'Pengatur'],
            [8, 'II/d', 'Pengatur Tingkat I'],
            [9, 'III/a', 'Penata Muda'],
            [10, 'III/b', 'Penata Muda Tingkat I'],
            [11, 'III/c', 'Penata'],
            [12, 'III/d', 'Penata Tingkat I'],
            [13, 'IV/a', 'Pembina'],
            [14, 'IV/b', 'Pembina Tingkat I'],
            [15, 'IV/c', 'Pembina Utama Muda'],
            [16, 'IV/d', 'Pembina Utama Madya'],
            [17, 'IV/e', 'Pembina Utama'],
        ];

        foreach ($pangkat as [$urutan, $golongan, $nama]) {
            PkrReferensiPangkat::updateOrCreate(
                ['urutan' => $urutan],
                ['golongan_ruang' => $golongan, 'nama_pangkat' => $nama, 'kategori' => null]
            );
        }
    }
}
