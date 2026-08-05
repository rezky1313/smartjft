<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PkrReferensiKoefisien;
use App\Models\PkrReferensiPredikat;
use App\Models\PkrAmbangBatasJenjang;

class PkrReferensiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $koefisien = [
            ['jenjang' => 'pemula', 'koefisien_tahunan' => 3.75],
            ['jenjang' => 'terampil', 'koefisien_tahunan' => 5],
            ['jenjang' => 'mahir', 'koefisien_tahunan' => 12.5],
            ['jenjang' => 'penyelia', 'koefisien_tahunan' => 25],
            ['jenjang' => 'ahli_pertama', 'koefisien_tahunan' => 12.5],
            ['jenjang' => 'ahli_muda', 'koefisien_tahunan' => 25],
            ['jenjang' => 'ahli_madya', 'koefisien_tahunan' => 37.5],
            ['jenjang' => 'ahli_utama', 'koefisien_tahunan' => 50],
        ];
        foreach ($koefisien as $row) {
            PkrReferensiKoefisien::updateOrCreate(['jenjang' => $row['jenjang']], $row);
        }

        $predikat = [
            ['predikat' => 'sangat_baik', 'persentase' => 150],
            ['predikat' => 'baik', 'persentase' => 100],
            ['predikat' => 'cukup', 'persentase' => 75],
            ['predikat' => 'kurang', 'persentase' => 50],
            ['predikat' => 'sangat_kurang', 'persentase' => 25],
        ];
        foreach ($predikat as $row) {
            PkrReferensiPredikat::updateOrCreate(['predikat' => $row['predikat']], $row);
        }

        $ambangBatas = [
            ['kategori' => 'keterampilan', 'dari_jenjang' => 'pemula', 'ke_jenjang' => 'terampil', 'ak_kumulatif_minimal' => 15],
            ['kategori' => 'keterampilan', 'dari_jenjang' => 'terampil', 'ke_jenjang' => 'mahir', 'ak_kumulatif_minimal' => 60],
            ['kategori' => 'keterampilan', 'dari_jenjang' => 'mahir', 'ke_jenjang' => 'penyelia', 'ak_kumulatif_minimal' => 100],
            ['kategori' => 'keahlian', 'dari_jenjang' => 'ahli_pertama', 'ke_jenjang' => 'ahli_muda', 'ak_kumulatif_minimal' => 100],
            ['kategori' => 'keahlian', 'dari_jenjang' => 'ahli_muda', 'ke_jenjang' => 'ahli_madya', 'ak_kumulatif_minimal' => 200],
            ['kategori' => 'keahlian', 'dari_jenjang' => 'ahli_madya', 'ke_jenjang' => 'ahli_utama', 'ak_kumulatif_minimal' => 450],
        ];
        foreach ($ambangBatas as $row) {
            PkrAmbangBatasJenjang::updateOrCreate(
                ['kategori' => $row['kategori'], 'dari_jenjang' => $row['dari_jenjang']],
                $row
            );
        }
    }
}
