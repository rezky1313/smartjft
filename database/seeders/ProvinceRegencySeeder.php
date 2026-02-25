<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceRegencySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ===== PROVINSI (38) =====
        $provinces = [
            'Aceh',
            'Sumatera Utara',
            'Sumatera Barat',
            'Riau',
            'Jambi',
            'Sumatera Selatan',
            'Bengkulu',
            'Lampung',
            'Kepulauan Bangka Belitung',
            'Kepulauan Riau',
            'DKI Jakarta',
            'Jawa Barat',
            'Jawa Tengah',
            'DI Yogyakarta',
            'Jawa Timur',
            'Banten',
            'Bali',
            'Nusa Tenggara Barat',
            'Nusa Tenggara Timur',
            'Kalimantan Barat',
            'Kalimantan Tengah',
            'Kalimantan Selatan',
            'Kalimantan Timur',
            'Kalimantan Utara',
            'Sulawesi Utara',
            'Sulawesi Tengah',
            'Sulawesi Selatan',
            'Sulawesi Tenggara',
            'Gorontalo',
            'Sulawesi Barat',
            'Maluku',
            'Maluku Utara',
            'Papua',
            'Papua Barat',
            'Papua Barat Daya',
            'Papua Selatan',
            'Papua Tengah',
            'Papua Pegunungan',
        ];

        // upsert provinces by name
        $provRows = array_map(fn($name) => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $provinces);

        DB::table('provinces')->upsert($provRows, ['name'], ['updated_at']);

        // map: name => id
        $provIds = DB::table('provinces')->pluck('id', 'name');

        // ===== REGENCIES (Batch 1: SUMATERA) =====
        // Format: 'Provinsi' => [ ['type'=>'KAB|KOTA','name'=>'...'], ... ]
        $sumatra = [

            // ACEH
            'Aceh' => [
                ['type'=>'KAB','name'=>'Aceh Barat'],
                ['type'=>'KAB','name'=>'Aceh Barat Daya'],
                ['type'=>'KAB','name'=>'Aceh Besar'],
                ['type'=>'KAB','name'=>'Aceh Jaya'],
                ['type'=>'KAB','name'=>'Aceh Selatan'],
                ['type'=>'KAB','name'=>'Aceh Singkil'],
                ['type'=>'KAB','name'=>'Aceh Tamiang'],
                ['type'=>'KAB','name'=>'Aceh Tengah'],
                ['type'=>'KAB','name'=>'Aceh Tenggara'],
                ['type'=>'KAB','name'=>'Aceh Timur'],
                ['type'=>'KAB','name'=>'Aceh Utara'],
                ['type'=>'KAB','name'=>'Bener Meriah'],
                ['type'=>'KAB','name'=>'Bireuen'],
                ['type'=>'KAB','name'=>'Gayo Lues'],
                ['type'=>'KAB','name'=>'Nagan Raya'],
                ['type'=>'KAB','name'=>'Pidie'],
                ['type'=>'KAB','name'=>'Pidie Jaya'],
                ['type'=>'KAB','name'=>'Simeulue'],
                ['type'=>'KOTA','name'=>'Banda Aceh'],
                ['type'=>'KOTA','name'=>'Langsa'],
                ['type'=>'KOTA','name'=>'Lhokseumawe'],
                ['type'=>'KOTA','name'=>'Sabang'],
                ['type'=>'KOTA','name'=>'Subulussalam'],
            ],

            // SUMATERA UTARA
            'Sumatera Utara' => [
                ['type'=>'KAB','name'=>'Asahan'],
                ['type'=>'KAB','name'=>'Batu Bara'],
                ['type'=>'KAB','name'=>'Dairi'],
                ['type'=>'KAB','name'=>'Deli Serdang'],
                ['type'=>'KAB','name'=>'Humbang Hasundutan'],
                ['type'=>'KAB','name'=>'Karo'],
                ['type'=>'KAB','name'=>'Labuhanbatu'],
                ['type'=>'KAB','name'=>'Labuhanbatu Selatan'],
                ['type'=>'KAB','name'=>'Labuhanbatu Utara'],
                ['type'=>'KAB','name'=>'Langkat'],
                ['type'=>'KAB','name'=>'Mandailing Natal'],
                ['type'=>'KAB','name'=>'Nias'],
                ['type'=>'KAB','name'=>'Nias Barat'],
                ['type'=>'KAB','name'=>'Nias Selatan'],
                ['type'=>'KAB','name'=>'Nias Utara'],
                ['type'=>'KAB','name'=>'Padang Lawas'],
                ['type'=>'KAB','name'=>'Padang Lawas Utara'],
                ['type'=>'KAB','name'=>'Pakpak Bharat'],
                ['type'=>'KAB','name'=>'Samosir'],
                ['type'=>'KAB','name'=>'Serdang Bedagai'],
                ['type'=>'KAB','name'=>'Simalungun'],
                ['type'=>'KAB','name'=>'Tapanuli Selatan'],
                ['type'=>'KAB','name'=>'Tapanuli Tengah'],
                ['type'=>'KAB','name'=>'Tapanuli Utara'],
                ['type'=>'KAB','name'=>'Toba'],
                ['type'=>'KOTA','name'=>'Binjai'],
                ['type'=>'KOTA','name'=>'Gunungsitoli'],
                ['type'=>'KOTA','name'=>'Medan'],
                ['type'=>'KOTA','name'=>'Padangsidimpuan'],
                ['type'=>'KOTA','name'=>'Pematangsiantar'],
                ['type'=>'KOTA','name'=>'Sibolga'],
                ['type'=>'KOTA','name'=>'Tanjungbalai'],
                ['type'=>'KOTA','name'=>'Tebing Tinggi'],
            ],

            // SUMATERA BARAT
            'Sumatera Barat' => [
                ['type'=>'KAB','name'=>'Agam'],
                ['type'=>'KAB','name'=>'Dharmasraya'],
                ['type'=>'KAB','name'=>'Kepulauan Mentawai'],
                ['type'=>'KAB','name'=>'Lima Puluh Kota'],
                ['type'=>'KAB','name'=>'Padang Pariaman'],
                ['type'=>'KAB','name'=>'Pasaman'],
                ['type'=>'KAB','name'=>'Pasaman Barat'],
                ['type'=>'KAB','name'=>'Pesisir Selatan'],
                ['type'=>'KAB','name'=>'Sijunjung'],
                ['type'=>'KAB','name'=>'Solok'],
                ['type'=>'KAB','name'=>'Solok Selatan'],
                ['type'=>'KAB','name'=>'Tanah Datar'],
                ['type'=>'KOTA','name'=>'Bukittinggi'],
                ['type'=>'KOTA','name'=>'Padang'],
                ['type'=>'KOTA','name'=>'Padang Panjang'],
                ['type'=>'KOTA','name'=>'Pariaman'],
                ['type'=>'KOTA','name'=>'Payakumbuh'],
                ['type'=>'KOTA','name'=>'Sawahlunto'],
                ['type'=>'KOTA','name'=>'Solok'],
            ],

            // RIAU
            'Riau' => [
                ['type'=>'KAB','name'=>'Bengkalis'],
                ['type'=>'KAB','name'=>'Indragiri Hilir'],
                ['type'=>'KAB','name'=>'Indragiri Hulu'],
                ['type'=>'KAB','name'=>'Kampar'],
                ['type'=>'KAB','name'=>'Kepulauan Meranti'],
                ['type'=>'KAB','name'=>'Kuantan Singingi'],
                ['type'=>'KAB','name'=>'Pelalawan'],
                ['type'=>'KAB','name'=>'Rokan Hilir'],
                ['type'=>'KAB','name'=>'Rokan Hulu'],
                ['type'=>'KAB','name'=>'Siak'],
                ['type'=>'KOTA','name'=>'Dumai'],
                ['type'=>'KOTA','name'=>'Pekanbaru'],
            ],

            // JAMBI
            'Jambi' => [
                ['type'=>'KAB','name'=>'Batanghari'],
                ['type'=>'KAB','name'=>'Bungo'],
                ['type'=>'KAB','name'=>'Kerinci'],
                ['type'=>'KAB','name'=>'Merangin'],
                ['type'=>'KAB','name'=>'Muaro Jambi'],
                ['type'=>'KAB','name'=>'Sarolangun'],
                ['type'=>'KAB','name'=>'Tanjung Jabung Barat'],
                ['type'=>'KAB','name'=>'Tanjung Jabung Timur'],
                ['type'=>'KAB','name'=>'Tebo'],
                ['type'=>'KOTA','name'=>'Jambi'],
                ['type'=>'KOTA','name'=>'Sungai Penuh'],
            ],

            // SUMATERA SELATAN
            'Sumatera Selatan' => [
                ['type'=>'KAB','name'=>'Banyuasin'],
                ['type'=>'KAB','name'=>'Empat Lawang'],
                ['type'=>'KAB','name'=>'Lahat'],
                ['type'=>'KAB','name'=>'Muara Enim'],
                ['type'=>'KAB','name'=>'Musi Banyuasin'],
                ['type'=>'KAB','name'=>'Musi Rawas'],
                ['type'=>'KAB','name'=>'Musi Rawas Utara'],
                ['type'=>'KAB','name'=>'Ogan Ilir'],
                ['type'=>'KAB','name'=>'Ogan Komering Ilir'],
                ['type'=>'KAB','name'=>'Ogan Komering Ulu'],
                ['type'=>'KAB','name'=>'Ogan Komering Ulu Selatan'],
                ['type'=>'KAB','name'=>'Ogan Komering Ulu Timur'],
                ['type'=>'KAB','name'=>'Penukal Abab Lematang Ilir'],
                ['type'=>'KOTA','name'=>'Lubuklinggau'],
                ['type'=>'KOTA','name'=>'Pagar Alam'],
                ['type'=>'KOTA','name'=>'Palembang'],
                ['type'=>'KOTA','name'=>'Prabumulih'],
            ],

            // BENGKULU
            'Bengkulu' => [
                ['type'=>'KAB','name'=>'Bengkulu Selatan'],
                ['type'=>'KAB','name'=>'Bengkulu Tengah'],
                ['type'=>'KAB','name'=>'Bengkulu Utara'],
                ['type'=>'KAB','name'=>'Kaur'],
                ['type'=>'KAB','name'=>'Kepahiang'],
                ['type'=>'KAB','name'=>'Lebong'],
                ['type'=>'KAB','name'=>'Muko-Muko'],
                ['type'=>'KAB','name'=>'Rejang Lebong'],
                ['type'=>'KAB','name'=>'Seluma'],
                ['type'=>'KOTA','name'=>'Bengkulu'],
            ],

            // LAMPUNG
            'Lampung' => [
                ['type'=>'KAB','name'=>'Lampung Barat'],
                ['type'=>'KAB','name'=>'Lampung Selatan'],
                ['type'=>'KAB','name'=>'Lampung Tengah'],
                ['type'=>'KAB','name'=>'Lampung Timur'],
                ['type'=>'KAB','name'=>'Lampung Utara'],
                ['type'=>'KAB','name'=>'Mesuji'],
                ['type'=>'KAB','name'=>'Pesawaran'],
                ['type'=>'KAB','name'=>'Pesisir Barat'],
                ['type'=>'KAB','name'=>'Pringsewu'],
                ['type'=>'KAB','name'=>'Tanggamus'],
                ['type'=>'KAB','name'=>'Tulang Bawang'],
                ['type'=>'KAB','name'=>'Tulang Bawang Barat'],
                ['type'=>'KAB','name'=>'Way Kanan'],
                ['type'=>'KOTA','name'=>'Bandar Lampung'],
                ['type'=>'KOTA','name'=>'Metro'],
            ],

            // KEP. BANGKA BELITUNG
            'Kepulauan Bangka Belitung' => [
                ['type'=>'KAB','name'=>'Bangka'],
                ['type'=>'KAB','name'=>'Bangka Barat'],
                ['type'=>'KAB','name'=>'Bangka Selatan'],
                ['type'=>'KAB','name'=>'Bangka Tengah'],
                ['type'=>'KAB','name'=>'Belitung'],
                ['type'=>'KAB','name'=>'Belitung Timur'],
                ['type'=>'KOTA','name'=>'Pangkalpinang'],
            ],

            // KEPULAUAN RIAU
            'Kepulauan Riau' => [
                ['type'=>'KAB','name'=>'Bintan'],
                ['type'=>'KAB','name'=>'Karimun'],
                ['type'=>'KAB','name'=>'Kepulauan Anambas'],
                ['type'=>'KAB','name'=>'Lingga'],
                ['type'=>'KAB','name'=>'Natuna'],
                ['type'=>'KOTA','name'=>'Batam'],
                ['type'=>'KOTA','name'=>'Tanjungpinang'],
            ],
        ];

        // build regency rows
        $rows = [];
        foreach ($sumatra as $provName => $items) {
            $provId = $provIds[$provName] ?? null;
            if (!$provId) continue;
            foreach ($items as $it) {
                $rows[] = [
                    'province_id' => $provId,
                    'type'       => $it['type'],
                    'name'       => $it['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // upsert by (province_id, type, name)
        // (jaga-jaga kalau seed berulang)
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('regencies')->upsert(
                $chunk,
                ['province_id','type','name'],
                ['updated_at']
            );
        }
    }
}
