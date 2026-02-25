<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceRegencySeederJava extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Ambil ID provinsi yang sudah ada
        $provIds = DB::table('provinces')->pluck('id', 'name');

        // ===== REGIONS: PULAU JAWA =====
        $java = [
            // DKI JAKARTA
            'DKI Jakarta' => [
                ['type'=>'KAB','name'=>'Kepulauan Seribu'],
                ['type'=>'KOTA','name'=>'Jakarta Pusat'],
                ['type'=>'KOTA','name'=>'Jakarta Utara'],
                ['type'=>'KOTA','name'=>'Jakarta Barat'],
                ['type'=>'KOTA','name'=>'Jakarta Timur'],
                ['type'=>'KOTA','name'=>'Jakarta Selatan'],
            ],

            // JAWA BARAT
            'Jawa Barat' => [
                ['type'=>'KAB','name'=>'Bandung'],
                ['type'=>'KAB','name'=>'Bandung Barat'],
                ['type'=>'KAB','name'=>'Bekasi'],
                ['type'=>'KAB','name'=>'Bogor'],
                ['type'=>'KAB','name'=>'Ciamis'],
                ['type'=>'KAB','name'=>'Cianjur'],
                ['type'=>'KAB','name'=>'Cirebon'],
                ['type'=>'KAB','name'=>'Garut'],
                ['type'=>'KAB','name'=>'Indramayu'],
                ['type'=>'KAB','name'=>'Karawang'],
                ['type'=>'KAB','name'=>'Kuningan'],
                ['type'=>'KAB','name'=>'Majalengka'],
                ['type'=>'KAB','name'=>'Pangandaran'],
                ['type'=>'KAB','name'=>'Purwakarta'],
                ['type'=>'KAB','name'=>'Subang'],
                ['type'=>'KAB','name'=>'Sukabumi'],
                ['type'=>'KAB','name'=>'Sumedang'],
                ['type'=>'KAB','name'=>'Tasikmalaya'],
                ['type'=>'KOTA','name'=>'Bandung'],
                ['type'=>'KOTA','name'=>'Banjar'],
                ['type'=>'KOTA','name'=>'Bekasi'],
                ['type'=>'KOTA','name'=>'Bogor'],
                ['type'=>'KOTA','name'=>'Cimahi'],
                ['type'=>'KOTA','name'=>'Cirebon'],
                ['type'=>'KOTA','name'=>'Depok'],
                ['type'=>'KOTA','name'=>'Sukabumi'],
                ['type'=>'KOTA','name'=>'Tasikmalaya'],
            ],

            // JAWA TENGAH
            'Jawa Tengah' => [
                ['type'=>'KAB','name'=>'Banjarnegara'],
                ['type'=>'KAB','name'=>'Banyumas'],
                ['type'=>'KAB','name'=>'Batang'],
                ['type'=>'KAB','name'=>'Blora'],
                ['type'=>'KAB','name'=>'Boyolali'],
                ['type'=>'KAB','name'=>'Brebes'],
                ['type'=>'KAB','name'=>'Cilacap'],
                ['type'=>'KAB','name'=>'Demak'],
                ['type'=>'KAB','name'=>'Grobogan'],
                ['type'=>'KAB','name'=>'Jepara'],
                ['type'=>'KAB','name'=>'Karanganyar'],
                ['type'=>'KAB','name'=>'Kebumen'],
                ['type'=>'KAB','name'=>'Kendal'],
                ['type'=>'KAB','name'=>'Klaten'],
                ['type'=>'KAB','name'=>'Kudus'],
                ['type'=>'KAB','name'=>'Magelang'],
                ['type'=>'KAB','name'=>'Pati'],
                ['type'=>'KAB','name'=>'Pekalongan'],
                ['type'=>'KAB','name'=>'Pemalang'],
                ['type'=>'KAB','name'=>'Purbalingga'],
                ['type'=>'KAB','name'=>'Purworejo'],
                ['type'=>'KAB','name'=>'Rembang'],
                ['type'=>'KAB','name'=>'Semarang'],
                ['type'=>'KAB','name'=>'Sragen'],
                ['type'=>'KAB','name'=>'Sukoharjo'],
                ['type'=>'KAB','name'=>'Tegal'],
                ['type'=>'KAB','name'=>'Temanggung'],
                ['type'=>'KAB','name'=>'Wonogiri'],
                ['type'=>'KAB','name'=>'Wonosobo'],
                ['type'=>'KOTA','name'=>'Magelang'],
                ['type'=>'KOTA','name'=>'Pekalongan'],
                ['type'=>'KOTA','name'=>'Salatiga'],
                ['type'=>'KOTA','name'=>'Semarang'],
                ['type'=>'KOTA','name'=>'Surakarta'],
                ['type'=>'KOTA','name'=>'Tegal'],
            ],

            // DI YOGYAKARTA
            'DI Yogyakarta' => [
                ['type'=>'KAB','name'=>'Bantul'],
                ['type'=>'KAB','name'=>'Gunungkidul'],
                ['type'=>'KAB','name'=>'Kulon Progo'],
                ['type'=>'KAB','name'=>'Sleman'],
                ['type'=>'KOTA','name'=>'Yogyakarta'],
            ],

            // JAWA TIMUR
            'Jawa Timur' => [
                ['type'=>'KAB','name'=>'Bangkalan'],
                ['type'=>'KAB','name'=>'Banyuwangi'],
                ['type'=>'KAB','name'=>'Blitar'],
                ['type'=>'KAB','name'=>'Bojonegoro'],
                ['type'=>'KAB','name'=>'Bondowoso'],
                ['type'=>'KAB','name'=>'Gresik'],
                ['type'=>'KAB','name'=>'Jember'],
                ['type'=>'KAB','name'=>'Jombang'],
                ['type'=>'KAB','name'=>'Kediri'],
                ['type'=>'KAB','name'=>'Lamongan'],
                ['type'=>'KAB','name'=>'Lumajang'],
                ['type'=>'KAB','name'=>'Madiun'],
                ['type'=>'KAB','name'=>'Magetan'],
                ['type'=>'KAB','name'=>'Malang'],
                ['type'=>'KAB','name'=>'Mojokerto'],
                ['type'=>'KAB','name'=>'Nganjuk'],
                ['type'=>'KAB','name'=>'Ngawi'],
                ['type'=>'KAB','name'=>'Pacitan'],
                ['type'=>'KAB','name'=>'Pamekasan'],
                ['type'=>'KAB','name'=>'Pasuruan'],
                ['type'=>'KAB','name'=>'Ponorogo'],
                ['type'=>'KAB','name'=>'Probolinggo'],
                ['type'=>'KAB','name'=>'Sampang'],
                ['type'=>'KAB','name'=>'Sidoarjo'],
                ['type'=>'KAB','name'=>'Situbondo'],
                ['type'=>'KAB','name'=>'Sumenep'],
                ['type'=>'KAB','name'=>'Trenggalek'],
                ['type'=>'KAB','name'=>'Tuban'],
                ['type'=>'KAB','name'=>'Tulungagung'],
                ['type'=>'KOTA','name'=>'Batu'],
                ['type'=>'KOTA','name'=>'Blitar'],
                ['type'=>'KOTA','name'=>'Kediri'],
                ['type'=>'KOTA','name'=>'Madiun'],
                ['type'=>'KOTA','name'=>'Malang'],
                ['type'=>'KOTA','name'=>'Mojokerto'],
                ['type'=>'KOTA','name'=>'Pasuruan'],
                ['type'=>'KOTA','name'=>'Probolinggo'],
                ['type'=>'KOTA','name'=>'Surabaya'],
            ],

            // BANTEN
            'Banten' => [
                ['type'=>'KAB','name'=>'Lebak'],
                ['type'=>'KAB','name'=>'Pandeglang'],
                ['type'=>'KAB','name'=>'Serang'],
                ['type'=>'KAB','name'=>'Tangerang'],
                ['type'=>'KOTA','name'=>'Cilegon'],
                ['type'=>'KOTA','name'=>'Serang'],
                ['type'=>'KOTA','name'=>'Tangerang'],
                ['type'=>'KOTA','name'=>'Tangerang Selatan'],
            ],
        ];

        // Build rows & upsert
        $rows = [];
        foreach ($java as $provName => $items) {
            $provId = $provIds[$provName] ?? null;
            if (!$provId) continue; // skip kalau nama provinsi tidak ketemu
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

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('regencies')->upsert(
                $chunk,
                ['province_id','type','name'],
                ['updated_at']
            );
        }
    }
}
