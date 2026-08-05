<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sdmmodels;

/**
 * Backfill kolom fisik `jenjang_kode` (PKR-01 Bagian 3) untuk SEMUA pegawai, dihitung
 * PERSIS lewat accessor Sdmmodels::getJenjangKodeAttribute() yang sudah ada sejak
 * Bagian 1 (suffix-matching formasi->jenjang->nama_jenjang) -- supaya tidak ada drift
 * antara logika accessor dan kolom fisik. Jalankan sekali setelah migration kolom baru,
 * lalu titik-titik lain (SdmController, FormasiJabatanController, RekomendasiFormasiController)
 * menjaga kolom ini tetap sinkron ke depan lewat Sdmmodels::syncJenjangKode()/syncJenjangKodeForIds().
 */
class SyncJenjangKode extends Command
{
    protected $signature = 'pkr:sync-jenjang-kode';

    protected $description = 'Backfill kolom jenjang_kode di sumber_daya_manusia dari accessor jenjang_kode yang sudah ada';

    public function handle(): int
    {
        $total = Sdmmodels::count();
        $berhasil = 0;
        $null = 0;

        $this->info("Sinkronisasi jenjang_kode untuk {$total} pegawai...");
        $bar = $this->output->createProgressBar($total);

        Sdmmodels::with('formasi.jenjang')->chunkById(200, function ($chunk) use (&$berhasil, &$null, $bar) {
            foreach ($chunk as $sdm) {
                $kode = $sdm->getJenjangKodeAttribute();
                $sdm->forceFill(['jenjang_kode' => $kode])->save();

                if ($kode) {
                    $berhasil++;
                } else {
                    $null++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Berhasil (jenjang_kode terisi): {$berhasil}. NULL (formasi/jenjang tidak lengkap): {$null}. Total: {$total}.");

        return self::SUCCESS;
    }
}
