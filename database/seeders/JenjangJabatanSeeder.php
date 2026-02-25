<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// class JenjangJabatanSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         //
//     }
// }

class JenjangJabatanSeeder extends Seeder
{
    // public function run()
    // {
    //     DB::table('jenjang_jabatan')->insert([
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Pemula', 'golongan' => 'II/a', 'kategori' => 'Terampil'],
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Terampil', 'golongan' => 'II/b', 'kategori' => 'Terampil'],
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Mahir', 'golongan' => 'II/c', 'kategori' => 'Terampil'],
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Penyelia', 'golongan' => 'II/d', 'kategori' => 'Terampil'],

    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Ahli Pertama', 'golongan' => 'III/a', 'kategori' => 'Ahli'],
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Ahli Muda', 'golongan' => 'III/b-III/c', 'kategori' => 'Ahli'],
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Ahli Madya', 'golongan' => 'IV/a-IV/b', 'kategori' => 'Ahli'],
    //         ['nama_jenjang' => 'Penguji Kendaraan Bermotor Ahli Utama', 'golongan' => 'IV/c-IV/e', 'kategori' => 'Ahli'],
    //     ]);
    // }

     /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar formasi dasar (selain Penguji Kendaraan Bermotor yang sudah ada)
        $formasiList = [
            'Pengawas Keselamatan Pelayaran',
            'Teknisi Penerbangan',
            'Asisten Inspektur Angkutan Udara',
            'Inspektur Angkutan Udara',
            'Asisten Inspektur Bandar Udara',
            'Inspektur Bandar Udara',
            'Asisten Inspektur Keamanan Penerbangan',
            'Inspektur Keamanan Penerbangan',
            'Asisten Inspektur Navigasi Penerbangan',
            'Inspektur Navigasi Penerbangan',
            'Asisten Inspektur Kelaikudaraan Pesawat Udara',
            'Inspektur Kelaikudaraan Pesawat Udara',
            'Asisten Inspektur Pengoperasian Pesawat Udara',
            'Inspektur Pengoperasian Pesawat Udara',
            'Penguji Sarana Perkeretaapiaan',
            'Penguji Prasarana Perkeretaapiaan',
            'Inspektur Sarana Perkeretaapiaan',
            'Inspektur Prasarana Perkeretaapiaan',
            'Auditor Perkeretaapiaan',
            'Asisten Penguji Sarana Perkeretaapiaan',
            'Asisten Penguji Prasarana Perkeretaapiaan',
        ];

        // Jenjang standar
        $jenjangs = [
            ['suffix' => 'Pemula',       'kategori' => 'Terampil'],
            ['suffix' => 'Terampil',     'kategori' => 'Terampil'],
            ['suffix' => 'Mahir',        'kategori' => 'Terampil'],
            ['suffix' => 'Penyelia',     'kategori' => 'Terampil'],
            ['suffix' => 'Ahli Pertama', 'kategori' => 'Ahli'],
            ['suffix' => 'Ahli Muda',    'kategori' => 'Ahli'],
            ['suffix' => 'Ahli Madya',   'kategori' => 'Ahli'],
            ['suffix' => 'Ahli Utama',   'kategori' => 'Ahli'],
        ];

        $rows = [];
        foreach ($formasiList as $formasi) {
            foreach ($jenjangs as $j) {
                $rows[] = [
                    'nama_jenjang' => $formasi.' '.$j['suffix'],
                    'kategori'     => $j['kategori'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        DB::table('jenjang_jabatan')->insert($rows);
    }
}
