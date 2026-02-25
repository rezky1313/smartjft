<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceRegencySeederEast extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Ambil mapping nama provinsi -> id
        $provIds = DB::table('provinces')->pluck('id', 'name');

        // =======================
        // KALIMANTAN
        // =======================
        $kalimantan = [
            'Kalimantan Barat' => [
                ['type'=>'KAB','name'=>'Bengkayang'],
                ['type'=>'KAB','name'=>'Kapuas Hulu'],
                ['type'=>'KAB','name'=>'Kayong Utara'],
                ['type'=>'KAB','name'=>'Ketapang'],
                ['type'=>'KAB','name'=>'Kubu Raya'],
                ['type'=>'KAB','name'=>'Landak'],
                ['type'=>'KAB','name'=>'Melawi'],
                ['type'=>'KAB','name'=>'Mempawah'],
                ['type'=>'KAB','name'=>'Sambas'],
                ['type'=>'KAB','name'=>'Sanggau'],
                ['type'=>'KAB','name'=>'Sekadau'],
                ['type'=>'KAB','name'=>'Sintang'],
                ['type'=>'KOTA','name'=>'Pontianak'],
                ['type'=>'KOTA','name'=>'Singkawang'],
            ],
            'Kalimantan Tengah' => [
                ['type'=>'KAB','name'=>'Barito Selatan'],
                ['type'=>'KAB','name'=>'Barito Timur'],
                ['type'=>'KAB','name'=>'Barito Utara'],
                ['type'=>'KAB','name'=>'Gunung Mas'],
                ['type'=>'KAB','name'=>'Kapuas'],
                ['type'=>'KAB','name'=>'Katingan'],
                ['type'=>'KAB','name'=>'Kotawaringin Barat'],
                ['type'=>'KAB','name'=>'Kotawaringin Timur'],
                ['type'=>'KAB','name'=>'Lamandau'],
                ['type'=>'KAB','name'=>'Murung Raya'],
                ['type'=>'KAB','name'=>'Pulang Pisau'],
                ['type'=>'KAB','name'=>'Seruyan'],
                ['type'=>'KAB','name'=>'Sukamara'],
                ['type'=>'KOTA','name'=>'Palangka Raya'],
            ],
            'Kalimantan Selatan' => [
                ['type'=>'KAB','name'=>'Balangan'],
                ['type'=>'KAB','name'=>'Banjar'],
                ['type'=>'KAB','name'=>'Barito Kuala'],
                ['type'=>'KAB','name'=>'Hulu Sungai Selatan'],
                ['type'=>'KAB','name'=>'Hulu Sungai Tengah'],
                ['type'=>'KAB','name'=>'Hulu Sungai Utara'],
                ['type'=>'KAB','name'=>'Kotabaru'],
                ['type'=>'KAB','name'=>'Tabalong'],
                ['type'=>'KAB','name'=>'Tanah Bumbu'],
                ['type'=>'KAB','name'=>'Tanah Laut'],
                ['type'=>'KAB','name'=>'Tapin'],
                ['type'=>'KOTA','name'=>'Banjarbaru'],
                ['type'=>'KOTA','name'=>'Banjarmasin'],
            ],
            'Kalimantan Timur' => [
                ['type'=>'KAB','name'=>'Berau'],
                ['type'=>'KAB','name'=>'Kutai Barat'],
                ['type'=>'KAB','name'=>'Kutai Kartanegara'],
                ['type'=>'KAB','name'=>'Kutai Timur'],
                ['type'=>'KAB','name'=>'Mahakam Ulu'],
                ['type'=>'KAB','name'=>'Paser'],
                ['type'=>'KAB','name'=>'Penajam Paser Utara'],
                ['type'=>'KOTA','name'=>'Balikpapan'],
                ['type'=>'KOTA','name'=>'Bontang'],
                ['type'=>'KOTA','name'=>'Samarinda'],
            ],
            'Kalimantan Utara' => [
                ['type'=>'KAB','name'=>'Bulungan'],
                ['type'=>'KAB','name'=>'Malinau'],
                ['type'=>'KAB','name'=>'Nunukan'],
                ['type'=>'KAB','name'=>'Tana Tidung'],
                ['type'=>'KOTA','name'=>'Tarakan'],
            ],
        ];

        // =======================
        // SULAWESI (+ Gorontalo, Sulbar)
        // =======================
        $sulawesi = [
            'Sulawesi Utara' => [
                ['type'=>'KAB','name'=>'Bolaang Mongondow'],
                ['type'=>'KAB','name'=>'Bolaang Mongondow Selatan'],
                ['type'=>'KAB','name'=>'Bolaang Mongondow Timur'],
                ['type'=>'KAB','name'=>'Bolaang Mongondow Utara'],
                ['type'=>'KAB','name'=>'Kepulauan Sangihe'],
                ['type'=>'KAB','name'=>'Kepulauan Siau Tagulandang Biaro'],
                ['type'=>'KAB','name'=>'Kepulauan Talaud'],
                ['type'=>'KAB','name'=>'Minahasa'],
                ['type'=>'KAB','name'=>'Minahasa Selatan'],
                ['type'=>'KAB','name'=>'Minahasa Tenggara'],
                ['type'=>'KAB','name'=>'Minahasa Utara'],
                ['type'=>'KOTA','name'=>'Bitung'],
                ['type'=>'KOTA','name'=>'Kotamobagu'],
                ['type'=>'KOTA','name'=>'Manado'],
                ['type'=>'KOTA','name'=>'Tomohon'],
            ],
            'Gorontalo' => [
                ['type'=>'KAB','name'=>'Boalemo'],
                ['type'=>'KAB','name'=>'Bone Bolango'],
                ['type'=>'KAB','name'=>'Gorontalo'],
                ['type'=>'KAB','name'=>'Gorontalo Utara'],
                ['type'=>'KAB','name'=>'Pohuwato'],
                ['type'=>'KOTA','name'=>'Gorontalo'],
            ],
            'Sulawesi Tengah' => [
                ['type'=>'KAB','name'=>'Banggai'],
                ['type'=>'KAB','name'=>'Banggai Kepulauan'],
                ['type'=>'KAB','name'=>'Banggai Laut'],
                ['type'=>'KAB','name'=>'Buol'],
                ['type'=>'KAB','name'=>'Donggala'],
                ['type'=>'KAB','name'=>'Morowali'],
                ['type'=>'KAB','name'=>'Morowali Utara'],
                ['type'=>'KAB','name'=>'Parigi Moutong'],
                ['type'=>'KAB','name'=>'Poso'],
                ['type'=>'KAB','name'=>'Sigi'],
                ['type'=>'KAB','name'=>'Tojo Una-Una'],
                ['type'=>'KAB','name'=>'Tolitoli'],
                ['type'=>'KOTA','name'=>'Palu'],
            ],
            'Sulawesi Barat' => [
                ['type'=>'KAB','name'=>'Majene'],
                ['type'=>'KAB','name'=>'Mamasa'],
                ['type'=>'KAB','name'=>'Mamuju'],
                ['type'=>'KAB','name'=>'Mamuju Tengah'],
                ['type'=>'KAB','name'=>'Pasangkayu'],
                ['type'=>'KAB','name'=>'Polewali Mandar'],
            ],
            'Sulawesi Selatan' => [
                ['type'=>'KAB','name'=>'Bantaeng'],
                ['type'=>'KAB','name'=>'Barru'],
                ['type'=>'KAB','name'=>'Bone'],
                ['type'=>'KAB','name'=>'Bulukumba'],
                ['type'=>'KAB','name'=>'Enrekang'],
                ['type'=>'KAB','name'=>'Gowa'],
                ['type'=>'KAB','name'=>'Jeneponto'],
                ['type'=>'KAB','name'=>'Kepulauan Selayar'],
                ['type'=>'KAB','name'=>'Luwu'],
                ['type'=>'KAB','name'=>'Luwu Timur'],
                ['type'=>'KAB','name'=>'Luwu Utara'],
                ['type'=>'KAB','name'=>'Maros'],
                ['type'=>'KAB','name'=>'Pangkajene dan Kepulauan'],
                ['type'=>'KAB','name'=>'Pinrang'],
                ['type'=>'KAB','name'=>'Sidenreng Rappang'],
                ['type'=>'KAB','name'=>'Sinjai'],
                ['type'=>'KAB','name'=>'Soppeng'],
                ['type'=>'KAB','name'=>'Takalar'],
                ['type'=>'KAB','name'=>'Tana Toraja'],
                ['type'=>'KAB','name'=>'Toraja Utara'],
                ['type'=>'KAB','name'=>'Wajo'],
                ['type'=>'KOTA','name'=>'Makassar'],
                ['type'=>'KOTA','name'=>'Palopo'],
                ['type'=>'KOTA','name'=>'Parepare'],
            ],
            'Sulawesi Tenggara' => [
                ['type'=>'KAB','name'=>'Bombana'],
                ['type'=>'KAB','name'=>'Buton'],
                ['type'=>'KAB','name'=>'Buton Selatan'],
                ['type'=>'KAB','name'=>'Buton Tengah'],
                ['type'=>'KAB','name'=>'Buton Utara'],
                ['type'=>'KAB','name'=>'Kolaka'],
                ['type'=>'KAB','name'=>'Kolaka Timur'],
                ['type'=>'KAB','name'=>'Kolaka Utara'],
                ['type'=>'KAB','name'=>'Konawe'],
                ['type'=>'KAB','name'=>'Konawe Kepulauan'],
                ['type'=>'KAB','name'=>'Konawe Selatan'],
                ['type'=>'KAB','name'=>'Konawe Utara'],
                ['type'=>'KAB','name'=>'Muna'],
                ['type'=>'KAB','name'=>'Muna Barat'],
                ['type'=>'KAB','name'=>'Wakatobi'],
                ['type'=>'KOTA','name'=>'Baubau'],
                ['type'=>'KOTA','name'=>'Kendari'],
            ],
        ];

        // =======================
        // BALI – NUSA TENGGARA
        // =======================
        $baliNusa = [
            'Bali' => [
                ['type'=>'KAB','name'=>'Badung'],
                ['type'=>'KAB','name'=>'Bangli'],
                ['type'=>'KAB','name'=>'Buleleng'],
                ['type'=>'KAB','name'=>'Gianyar'],
                ['type'=>'KAB','name'=>'Jembrana'],
                ['type'=>'KAB','name'=>'Karangasem'],
                ['type'=>'KAB','name'=>'Klungkung'],
                ['type'=>'KAB','name'=>'Tabanan'],
                ['type'=>'KOTA','name'=>'Denpasar'],
            ],
            'Nusa Tenggara Barat' => [
                ['type'=>'KAB','name'=>'Bima'],
                ['type'=>'KAB','name'=>'Dompu'],
                ['type'=>'KAB','name'=>'Lombok Barat'],
                ['type'=>'KAB','name'=>'Lombok Tengah'],
                ['type'=>'KAB','name'=>'Lombok Timur'],
                ['type'=>'KAB','name'=>'Lombok Utara'],
                ['type'=>'KAB','name'=>'Sumbawa'],
                ['type'=>'KAB','name'=>'Sumbawa Barat'],
                ['type'=>'KOTA','name'=>'Bima'],
                ['type'=>'KOTA','name'=>'Mataram'],
            ],
            'Nusa Tenggara Timur' => [
                ['type'=>'KAB','name'=>'Alor'],
                ['type'=>'KAB','name'=>'Belu'],
                ['type'=>'KAB','name'=>'Ende'],
                ['type'=>'KAB','name'=>'Flores Timur'],
                ['type'=>'KAB','name'=>'Kupang'],
                ['type'=>'KAB','name'=>'Lembata'],
                ['type'=>'KAB','name'=>'Malaka'],
                ['type'=>'KAB','name'=>'Manggarai'],
                ['type'=>'KAB','name'=>'Manggarai Barat'],
                ['type'=>'KAB','name'=>'Manggarai Timur'],
                ['type'=>'KAB','name'=>'Nagekeo'],
                ['type'=>'KAB','name'=>'Ngada'],
                ['type'=>'KAB','name'=>'Rote Ndao'],
                ['type'=>'KAB','name'=>'Sabu Raijua'],
                ['type'=>'KAB','name'=>'Sikka'],
                ['type'=>'KAB','name'=>'Sumba Barat'],
                ['type'=>'KAB','name'=>'Sumba Barat Daya'],
                ['type'=>'KAB','name'=>'Sumba Tengah'],
                ['type'=>'KAB','name'=>'Sumba Timur'],
                ['type'=>'KAB','name'=>'Timor Tengah Selatan'],
                ['type'=>'KAB','name'=>'Timor Tengah Utara'],
                ['type'=>'KOTA','name'=>'Kupang'],
            ],
        ];

        // =======================
        // MALUKU – PAPUA (provinsi pasca-pemekaran 2022)
        // =======================
        $malukuPapua = [
            'Maluku' => [
                ['type'=>'KAB','name'=>'Buru'],
                ['type'=>'KAB','name'=>'Buru Selatan'],
                ['type'=>'KAB','name'=>'Kepulauan Aru'],
                ['type'=>'KAB','name'=>'Maluku Barat Daya'],
                ['type'=>'KAB','name'=>'Maluku Tengah'],
                ['type'=>'KAB','name'=>'Maluku Tenggara'],
                ['type'=>'KAB','name'=>'Seram Bagian Barat'],
                ['type'=>'KAB','name'=>'Seram Bagian Timur'],
                ['type'=>'KAB','name'=>'Kepulauan Tanimbar'],
                ['type'=>'KOTA','name'=>'Ambon'],
                ['type'=>'KOTA','name'=>'Tual'],
            ],
            'Maluku Utara' => [
                ['type'=>'KAB','name'=>'Halmahera Barat'],
                ['type'=>'KAB','name'=>'Halmahera Tengah'],
                ['type'=>'KAB','name'=>'Halmahera Timur'],
                ['type'=>'KAB','name'=>'Halmahera Selatan'],
                ['type'=>'KAB','name'=>'Halmahera Utara'],
                ['type'=>'KAB','name'=>'Kepulauan Sula'],
                ['type'=>'KAB','name'=>'Pulau Morotai'],
                ['type'=>'KAB','name'=>'Pulau Taliabu'],
                ['type'=>'KOTA','name'=>'Ternate'],
                ['type'=>'KOTA','name'=>'Tidore Kepulauan'],
            ],
            // Papua Barat (pasca pemekaran; tanpa Sorong cs.)
            'Papua Barat' => [
                ['type'=>'KAB','name'=>'Fakfak'],
                ['type'=>'KAB','name'=>'Kaimana'],
                ['type'=>'KAB','name'=>'Manokwari'],
                ['type'=>'KAB','name'=>'Manokwari Selatan'],
                ['type'=>'KAB','name'=>'Pegunungan Arfak'],
                ['type'=>'KAB','name'=>'Teluk Bintuni'],
                ['type'=>'KAB','name'=>'Teluk Wondama'],
            ],
            // Papua Barat Daya (baru)
            'Papua Barat Daya' => [
                ['type'=>'KAB','name'=>'Maybrat'],
                ['type'=>'KAB','name'=>'Raja Ampat'],
                ['type'=>'KAB','name'=>'Sorong'],
                ['type'=>'KAB','name'=>'Sorong Selatan'],
                ['type'=>'KAB','name'=>'Tambrauw'],
                ['type'=>'KOTA','name'=>'Sorong'],
            ],
            'Papua Selatan' => [
                ['type'=>'KAB','name'=>'Asmat'],
                ['type'=>'KAB','name'=>'Boven Digoel'],
                ['type'=>'KAB','name'=>'Mappi'],
                ['type'=>'KAB','name'=>'Merauke'],
            ],
            'Papua Tengah' => [
                ['type'=>'KAB','name'=>'Deiyai'],
                ['type'=>'KAB','name'=>'Dogiyai'],
                ['type'=>'KAB','name'=>'Intan Jaya'],
                ['type'=>'KAB','name'=>'Mimika'],
                ['type'=>'KAB','name'=>'Nabire'],
                ['type'=>'KAB','name'=>'Paniai'],
                ['type'=>'KAB','name'=>'Puncak'],
                ['type'=>'KAB','name'=>'Puncak Jaya'],
            ],
            'Papua Pegunungan' => [
                ['type'=>'KAB','name'=>'Jayawijaya'],
                ['type'=>'KAB','name'=>'Lanny Jaya'],
                ['type'=>'KAB','name'=>'Mamberamo Tengah'],
                ['type'=>'KAB','name'=>'Nduga'],
                ['type'=>'KAB','name'=>'Pegunungan Bintang'],
                ['type'=>'KAB','name'=>'Tolikara'],
                ['type'=>'KAB','name'=>'Yahukimo'],
                ['type'=>'KAB','name'=>'Yalimo'],
            ],
            // Papua (induk) pasca pemekaran
            'Papua' => [
                ['type'=>'KAB','name'=>'Biak Numfor'],
                ['type'=>'KAB','name'=>'Jayapura'],
                ['type'=>'KAB','name'=>'Keerom'],
                ['type'=>'KAB','name'=>'Kepulauan Yapen'],
                ['type'=>'KAB','name'=>'Mamberamo Raya'],
                ['type'=>'KAB','name'=>'Sarmi'],
                ['type'=>'KAB','name'=>'Supiori'],
                ['type'=>'KAB','name'=>'Waropen'],
                ['type'=>'KOTA','name'=>'Jayapura'],
            ],
        ];

        // Gabungkan semua region
        $regions = $kalimantan + $sulawesi + $baliNusa + $malukuPapua;

        // Build rows dari mapping di atas
        $rows = [];
        foreach ($regions as $provName => $items) {
            $provId = $provIds[$provName] ?? null;
            if (!$provId) { continue; } // lewati jika provinsi tidak ada
            foreach ($items as $it) {
                $rows[] = [
                    'province_id' => $provId,
                    'type'        => $it['type'],
                    'name'        => $it['name'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        // Upsert per 500 rows
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('regencies')->upsert(
                $chunk,
                ['province_id', 'type', 'name'],
                ['updated_at']
            );
        }
    }
}
