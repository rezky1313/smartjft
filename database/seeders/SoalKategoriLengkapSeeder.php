<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoalKategori;

class SoalKategoriLengkapSeeder extends Seeder
{
    public function run()
    {
        // Jenjang keterampilan
        $jenjangKeterampilan = ['pemula', 'terampil', 'mahir', 'penyelia'];
        // Jenjang keahlian
        $jenjangKeahlian = ['ahli_pertama', 'ahli_muda', 'ahli_madya', 'ahli_utama'];

        // Label jenjang untuk nama kategori
        $labelJenjang = [
            'pemula'       => 'Pemula',
            'terampil'     => 'Terampil',
            'mahir'        => 'Mahir',
            'penyelia'     => 'Penyelia',
            'ahli_pertama' => 'Ahli Pertama',
            'ahli_muda'    => 'Ahli Muda',
            'ahli_madya'   => 'Ahli Madya',
            'ahli_utama'   => 'Ahli Utama',
        ];

        // Definisi semua JF
        // Format: [nama_jabatan, matra, [jenjang], klasifikasi, bidang]
        $daftarJF = [
            // ── DARAT ──
            ['Penguji Kendaraan Bermotor', 'darat', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Penguji Kendaraan Bermotor', 'darat', $jenjangKeahlian, 'keahlian', 'Teknis'],

            // ── LAUT ──
            ['Pengawas Keselamatan Pelayaran', 'laut', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Pengawas Keselamatan Pelayaran', 'laut', $jenjangKeahlian, 'keahlian', 'Teknis'],

            // ── UDARA — Keterampilan ──
            ['Teknisi Penerbangan', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Inspektur Angkutan Udara', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Inspektur Bandar Udara', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Inspektur Keamanan Penerbangan', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Inspektur Navigasi Penerbangan', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Inspektur Kelaikan Pesawat Udara', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Inspektur Pengoperasian Pesawat Udara', 'udara', $jenjangKeterampilan, 'keterampilan', 'Teknis'],

            // ── UDARA — Keahlian ──
            ['Inspektur Angkutan Udara', 'udara', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Inspektur Bandar Udara', 'udara', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Inspektur Keamanan Penerbangan', 'udara', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Inspektur Navigasi Penerbangan', 'udara', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Inspektur Kelaikan Pesawat Udara', 'udara', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Inspektur Pengoperasian Pesawat Udara', 'udara', $jenjangKeahlian, 'keahlian', 'Teknis'],

            // ── PERKERETAAPIAN — Keterampilan ──
            ['Asisten Penguji Sarana Perkeretaapian', 'perkeretaapian', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Penguji Sarana Perkeretaapian', 'perkeretaapian', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Asisten Penguji Prasarana Perkeretaapian', 'perkeretaapian', $jenjangKeterampilan, 'keterampilan', 'Teknis'],
            ['Penguji Prasarana Perkeretaapian', 'perkeretaapian', $jenjangKeterampilan, 'keterampilan', 'Teknis'],

            // ── PERKERETAAPIAN — Keahlian ──
            ['Inspektur Sarana Perkeretaapian', 'perkeretaapian', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Inspektur Prasarana Perkeretaapian', 'perkeretaapian', $jenjangKeahlian, 'keahlian', 'Teknis'],
            ['Auditor Perkeretaapian', 'perkeretaapian', $jenjangKeahlian, 'keahlian', 'Teknis'],
        ];

        // Update kategori Umum yang sudah ada
        SoalKategori::updateOrCreate(
            ['nama' => 'Umum'],
            [
                'jabatan'      => null,
                'jenjang'      => 'umum',
                'matra'        => 'umum',
                'klasifikasi'  => 'umum',
                'bidang'       => 'Umum',
                'aktif'        => true,
            ]
        );

        // Generate semua kategori
        foreach ($daftarJF as $jf) {
            [$namaJabatan, $matra, $jenjangList, $klasifikasi, $bidang] = $jf;

            foreach ($jenjangList as $jenjang) {
                $labelMatra = ucfirst($matra);
                $namaKategori = "{$namaJabatan} {$labelJenjang[$jenjang]} - {$labelMatra}";

                SoalKategori::updateOrCreate(
                    ['nama' => $namaKategori],
                    [
                        'jabatan'      => $namaJabatan,
                        'jenjang'      => $jenjang,
                        'matra'        => $matra,
                        'klasifikasi'  => $klasifikasi,
                        'bidang'       => $bidang,
                        'aktif'        => true,
                    ]
                );
            }
        }

        $total = SoalKategori::count();
        $this->command->info("Selesai! Total kategori: {$total}");
    }
}
