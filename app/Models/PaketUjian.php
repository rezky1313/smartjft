<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class PaketUjian extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paket_ujian';

    protected $fillable = [
        'nama',
        'ujikom_jadwal_id',
        'deskripsi',
        'durasi_menit',
        'passing_grade',
        'jumlah_soal',
        'mode_pemilihan',
        'status',
        'acak_soal',
        'acak_pilihan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'acak_soal'    => 'boolean',
        'acak_pilihan' => 'boolean',
    ];

    // ─── Relasi ──────────────────────────────────────────────────────────────

    public function jadwal()
    {
        return $this->belongsTo(UjikomJadwal::class, 'ujikom_jadwal_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function soal()
    {
        return $this->hasMany(PaketUjianSoal::class, 'paket_ujian_id')->orderBy('urutan');
    }

    public function kategoriAcak()
    {
        return $this->hasMany(PaketUjianKategoriAcak::class, 'paket_ujian_id');
    }

    public function bankSoal()
    {
        return $this->belongsToMany(BankSoal::class, 'paket_ujian_soal', 'paket_ujian_id', 'bank_soal_id')
                    ->withPivot('urutan')
                    ->orderByPivot('urutan');
    }

    // ─── Accessor ────────────────────────────────────────────────────────────

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'    => 'Draft',
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            default    => $this->status,
        };
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'success',
            'draft'    => 'secondary',
            'nonaktif' => 'danger',
            default    => 'secondary',
        };
    }

    public function getLabelModeAttribute(): string
    {
        return match ($this->mode_pemilihan) {
            'acak_otomatis' => 'Acak Otomatis',
            'manual'        => 'Manual',
            default         => $this->mode_pemilihan,
        };
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Generate set soal untuk peserta saat mulai ujian.
     * Dipanggil dari modul Ujikom Online.
     */
    public function generateSoalUntukPeserta($pesertaId): Collection
    {
        if ($this->mode_pemilihan === 'manual') {
            $soal = $this->bankSoal()
                ->where('bank_soal.status', 'aktif')
                ->with('pilihan')
                ->get();
        } else {
            // Mode acak: ambil soal per konfigurasi kategori
            $soal = collect();

            foreach ($this->kategoriAcak as $config) {
                if ($config->jenis_soal === 'umum') {
                    $pool = BankSoal::umum()
                        ->aktif()
                        ->with('pilihan')
                        ->inRandomOrder()
                        ->take($config->jumlah_soal)
                        ->get();
                } else {
                    $pool = BankSoal::aktif()
                        ->where('soal_kategori_id', $config->soal_kategori_id)
                        ->with('pilihan')
                        ->inRandomOrder()
                        ->take($config->jumlah_soal)
                        ->get();
                }
                $soal = $soal->merge($pool);
            }
        }

        // Acak urutan soal
        if ($this->acak_soal) {
            $soal = $soal->shuffle();
        }

        // Acak pilihan jawaban per soal
        if ($this->acak_pilihan) {
            $soal = $soal->map(function ($s) {
                $s->pilihan_acak = $s->pilihan->shuffle();
                return $s;
            });
        }

        return $soal;
    }

    /**
     * Hitung total soal tersedia di bank berdasarkan konfigurasi acak.
     * Return array ['cukup' => bool, 'detail' => [...]]
     */
    public function cekKetersediaanSoal(): array
    {
        if ($this->mode_pemilihan === 'manual') {
            $jumlahDipilih = $this->soal()->count();
            return [
                'cukup'  => $jumlahDipilih >= $this->jumlah_soal,
                'detail' => ["Soal terpilih: {$jumlahDipilih} dari {$this->jumlah_soal} yang dibutuhkan"],
            ];
        }

        $detail = [];
        $semuaCukup = true;

        foreach ($this->kategoriAcak as $config) {
            if ($config->jenis_soal === 'umum') {
                $tersedia = BankSoal::umum()->aktif()->count();
                $label    = 'Soal Umum';
            } else {
                $tersedia = BankSoal::aktif()->where('soal_kategori_id', $config->soal_kategori_id)->count();
                $label    = $config->kategori?->nama ?? "Kategori #{$config->soal_kategori_id}";
            }

            $cukup = $tersedia >= $config->jumlah_soal;
            if (!$cukup) $semuaCukup = false;

            $detail[] = [
                'label'     => $label,
                'butuh'     => $config->jumlah_soal,
                'tersedia'  => $tersedia,
                'cukup'     => $cukup,
            ];
        }

        return ['cukup' => $semuaCukup, 'detail' => $detail];
    }
}
