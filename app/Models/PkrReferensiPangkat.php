<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkrReferensiPangkat extends Model
{
    protected $table = 'pkr_referensi_pangkat';

    protected $fillable = [
        'urutan',
        'golongan_ruang',
        'nama_pangkat',
        'kategori',
    ];

    /** Cache statis per-request: tabel ini kecil (17 baris) & dipanggil per-baris di halaman index (ribuan pegawai) -- hindari query berulang. */
    private static ?\Illuminate\Support\Collection $cache = null;

    public static function semua(): \Illuminate\Support\Collection
    {
        return self::$cache ??= self::all();
    }

    public static function normalisasiGolongan(string $golongan): string
    {
        $golongan = strtoupper(trim($golongan));
        // Rapikan spasi di sekitar "/" -> "III / B" atau "III/ b" jadi "III/B"
        $golongan = preg_replace('/\s*\/\s*/', '/', $golongan);
        return $golongan;
    }

    public static function cari(string $golonganRuang): ?self
    {
        $normal = self::normalisasiGolongan($golonganRuang);
        return self::semua()->first(fn ($row) => self::normalisasiGolongan($row->golongan_ruang) === $normal);
    }

    public static function next(string $golonganRuangSaatIni): ?self
    {
        $current = self::cari($golonganRuangSaatIni);
        if (!$current) {
            return null;
        }

        return self::semua()->first(fn ($row) => $row->urutan === $current->urutan + 1);
    }
}
