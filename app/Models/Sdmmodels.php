<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Sdmmodels extends Model
{
    use SoftDeletes;

    protected $table = 'sumber_daya_manusia';

    protected $fillable = [
        'nip',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'pendidikan_terakhir',
        'pangkat_golongan',
        'status_kepegawaian',
        'formasi_jabatan_id',
          'unit_kerja_id',   // <— penting
        'tmt_pengangkatan',
        'aktif',
        'status_formasi',   // status formasi: terpenuhi / di_luar_formasi
    ];

    protected $casts = [
        'tmt_pengangkatan' => 'date',
        'aktif' => 'boolean',
    ];

    // Relasi ke Formasi (opsi A)
    public function formasi()
    {
        return $this->belongsTo(\App\Models\Formasijabatan::class, 'formasi_jabatan_id');
    }

    // Alias formasi() — dipakai di dashboard.blade.php & PengangkatanPermohonan.php
    public function formasiJabatan()
    {
        return $this->belongsTo(\App\Models\Formasijabatan::class, 'formasi_jabatan_id');
    }

    public function unitKerja() {
  return $this->belongsTo(\App\Models\UnitKerja::class, 'unit_kerja_id');
}

    // Atribut hitungan lama menjabat (pakai yg sudah kamu tulis pun boleh)
    public function getMasaJabatanAttribute(): ?string
    {
        if (!$this->tmt_pengangkatan) return null;
        $diff = $this->tmt_pengangkatan->diff(Carbon::today());
        return "{$diff->y} th {$diff->m} bln {$diff->d} hr";
    }

    // Tidak ada kolom `jenjang` langsung di tabel ini -- jenjang selalu diturunkan
    // dari formasi_jabatan_id -> jenjang_jabatan.nama_jenjang (format "{Nama JF} {Jenjang}").
    // Accessor ini menormalkan nama_jenjang jadi short-code (mis. "ahli_pertama") dengan
    // mencocokkan akhiran nama terhadap 8 nama jenjang resmi (diverifikasi cocok 100%
    // untuk seluruh 198 baris jenjang_jabatan, semua 22 JF).
    public function getJenjangKodeAttribute(): ?string
    {
        $namaJenjang = $this->formasi?->jenjang?->nama_jenjang;
        if (!$namaJenjang) {
            return null;
        }

        $petaAkhiran = [
            'Ahli Pertama' => 'ahli_pertama',
            'Ahli Muda' => 'ahli_muda',
            'Ahli Madya' => 'ahli_madya',
            'Ahli Utama' => 'ahli_utama',
            'Penyelia' => 'penyelia',
            'Terampil' => 'terampil',
            'Mahir' => 'mahir',
            'Pemula' => 'pemula',
        ];

        foreach ($petaAkhiran as $akhiran => $kode) {
            if (str_ends_with(trim($namaJenjang), $akhiran)) {
                return $kode;
            }
        }

        return null;
    }

    // Nama jenjang lengkap (mis. "Penguji Kendaraan Bermotor Ahli Pertama"), untuk tampilan/audit.
    public function getJenjangNamaAttribute(): ?string
    {
        return $this->formasi?->jenjang?->nama_jenjang;
    }

    /**
     * Sinkronkan kolom fisik `jenjang_kode` (PKR-01 Bagian 3) dari hasil accessor di atas.
     *
     * PENTING: karena Eloquent SELALU mendahulukan accessor saat nama sama, `$sdm->jenjang_kode`
     * di PHP TETAP live-compute dari relasi (TIDAK membaca kolom fisik ini) -- kolom fisik cuma
     * berguna lewat query builder mentah (WHERE/subquery), BUKAN lewat properti model. Sengaja
     * TIDAK dimasukkan ke $fillable supaya tidak bisa di-set sembarangan dari form/mass-assignment,
     * cuma lewat method ini.
     *
     * Panggil SETELAH formasi_jabatan_id disimpan (bukan dalam satu save() yang sama), di titik mana
     * pun formasi_jabatan_id pegawai berubah -- lihat daftar titik panggil di PkrSyncJenjangKode command
     * & CHANGELOG v1.28.0.
     */
    public function syncJenjangKode(): void
    {
        $this->unsetRelation('formasi');
        $this->forceFill(['jenjang_kode' => $this->getJenjangKodeAttribute()])->save();
    }

    /**
     * Sinkronkan jenjang_kode untuk banyak SDM sekaligus (dipakai setelah bulk update
     * formasi_jabatan_id lewat query builder, yang TIDAK memicu event/accessor Eloquent sama sekali).
     */
    public static function syncJenjangKodeForIds(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        self::with('formasi.jenjang')->whereIn('id', $ids)->get()->each(function (self $sdm) {
            $sdm->forceFill(['jenjang_kode' => $sdm->getJenjangKodeAttribute()])->save();
        });
    }
}
