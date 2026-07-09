<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PengangkatanPermohonan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengangkatan_permohonan';

    protected $fillable = [
        'kode_permohonan',
        'unit_kerja_id',
        'file_surat_permohonan',
        'tanggal_permohonan',
        'status',
        'catatan_pusbin',
        'diajukan_oleh',
        'diproses_oleh',
        'tanggal_disetujui',
    ];

    protected $casts = [
        'tanggal_permohonan' => 'date',
        'tanggal_disetujui'  => 'date',
    ];

    // ─── Relasi ───

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function pemroses()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function kandidat()
    {
        return $this->hasMany(PengangkatanKandidat::class, 'pengangkatan_permohonan_id');
    }

    public function surat()
    {
        return $this->hasOne(PengangkatanSurat::class, 'pengangkatan_permohonan_id');
    }

    // ─── Accessor ───

    public function getLabelStatusAttribute(): string
    {
        return [
            'draft'     => 'Draft',
            'diajukan'  => 'Diajukan',
            'diproses'  => 'Sedang Diproses',
            'disetujui' => 'Disetujui',
            'ditolak'   => 'Ditolak',
            'selesai'   => 'Selesai',
        ][$this->status] ?? $this->status;
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'secondary',
            'diajukan'  => 'primary',
            'diproses'  => 'warning',
            'disetujui' => 'info',
            'ditolak'   => 'danger',
            'selesai'   => 'success',
            default     => 'secondary',
        };
    }

    // ─── Helper ───

    public static function generateKode(): string
    {
        $now   = Carbon::now();
        $bulan = toRoman($now->month);
        $tahun = $now->year;
        $urut  = static::whereYear('created_at', $tahun)->count() + 1;

        return sprintf('ANGKAT/%s/%d/%04d', $bulan, $tahun, $urut);
    }

    /**
     * Generate kandidat berdasarkan hasil ujikom lulus di unit kerja ini.
     */
    public function generateKandidat(): void
    {
        // 1. Hapus kandidat lama
        $this->kandidat()->delete();

        // 2. Ambil semua pegawai di unit kerja ini yang sudah lulus ujikom
        $pegawaiLulus = UjikomHasil::whereHas('peserta.pendaftaran', function ($q) {
                $q->where('unit_kerja_id', $this->unit_kerja_id);
            })
            ->where('status_kelulusan', 'lulus')
            ->with(['peserta.pegawai.formasiJabatan.jenjang', 'peserta.jabatanTujuan.jenjang'])
            ->get();

        // 3. Group per jabatan tujuan
        $perJabatan = $pegawaiLulus->groupBy(fn($hasil) => $hasil->peserta->jabatan_tujuan_id ?? 'unknown');

        foreach ($perJabatan as $jabatanTujuanId => $kandidatList) {
            if ($jabatanTujuanId === 'unknown') continue;

            // 4. Cek formasi tersedia
            $formasi = Formasijabatan::find($jabatanTujuanId);
            $sisaFormasi = $formasi ? $formasi->sisa : 0;

            // 5. Ranking berdasarkan nilai tertinggi
            $ranked = $kandidatList->sortByDesc('nilai')->values();

            foreach ($ranked as $index => $hasil) {
                $ranking = $index + 1;
                $statusKandidat = ($ranking <= $sisaFormasi && $sisaFormasi > 0)
                    ? 'direkomendasikan'
                    : 'antrian';

                $pegawai = $hasil->peserta->pegawai ?? null;

                PengangkatanKandidat::create([
                    'pengangkatan_permohonan_id' => $this->id,
                    'pegawai_id'                 => $hasil->peserta->pegawai_id,
                    'ujikom_hasil_id'            => $hasil->id,
                    'jabatan_asal'               => $pegawai?->formasiJabatan?->nama_formasi ?? '-',
                    'jenjang_asal'               => $pegawai?->formasiJabatan?->jenjang?->nama_jenjang ?? '-',
                    'jabatan_tujuan_id'          => $jabatanTujuanId,
                    'jenjang_tujuan'             => $hasil->peserta->jenjang_tujuan ?? '-',
                    'nilai_ujikom'               => $hasil->nilai,
                    'ranking'                    => $ranking,
                    'formasi_tersedia'           => $sisaFormasi,
                    'status_kandidat'            => $statusKandidat,
                ]);
            }
        }
    }

    /**
     * Selesaikan permohonan: update formasi pegawai yang direkomendasikan.
     */
    public function selesaikan(): void
    {
        foreach ($this->kandidat()->where('status_kandidat', 'direkomendasikan')->get() as $kandidat) {
            $pegawai = $kandidat->pegawai;
            if (!$pegawai) continue;

            // Update formasi pegawai ke jabatan tujuan
            $pegawai->update([
                'formasi_jabatan_id' => $kandidat->jabatan_tujuan_id,
                'tmt_pengangkatan'   => now()->toDateString(),
            ]);
        }

        $this->update(['status' => 'selesai']);
    }
}
