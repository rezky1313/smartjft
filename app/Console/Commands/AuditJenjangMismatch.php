<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PengangkatanPermohonan;

/**
 * READ-ONLY. Audit kecocokan formasi_jabatan_id SDM saat ini terhadap formasi TUJUAN
 * dari Pengangkatan JFT yang sudah "selesai" (kandidat berstatus 'direkomendasikan' --
 * SAMA persis dengan filter yang dipakai PengangkatanPermohonan::selesaikan()).
 *
 * Kalau seorang pegawai punya lebih dari satu permohonan selesai (naik jenjang lebih
 * dari sekali), yang dibandingkan HANYA permohonan TERBARU (by tanggal_disetujui, lalu
 * created_at) -- permohonan lama secara sah "digantikan" oleh yang baru, bukan mismatch.
 *
 * PENTING: "mismatch" di sini bukan otomatis berarti bug -- formasi pegawai bisa saja
 * berubah lagi SETELAH pengangkatan lewat proses lain yang sah (restrukturisasi Formasi,
 * dsb). Command ini TIDAK mengoreksi apapun, cuma melaporkan untuk ditinjau manual.
 */
class AuditJenjangMismatch extends Command
{
    protected $signature = 'pkr:audit-jenjang-mismatch';

    protected $description = 'READ-ONLY: audit mismatch formasi_jabatan_id SDM vs formasi tujuan Pengangkatan JFT yang sudah selesai';

    public function handle(): int
    {
        $permohonanSelesai = PengangkatanPermohonan::where('status', 'selesai')
            ->with(['kandidat' => fn ($q) => $q->where('status_kandidat', 'direkomendasikan')->with(['pegawai', 'jabatanTujuan.jenjang'])])
            ->get();

        $this->info("Permohonan Pengangkatan JFT berstatus 'selesai': {$permohonanSelesai->count()}");

        // Ambil kandidat TERBARU per pegawai_id (kalau pegawai naik jenjang > 1x)
        $terbaruPerPegawai = [];
        foreach ($permohonanSelesai as $permohonan) {
            foreach ($permohonan->kandidat as $kandidat) {
                $pegawaiId = $kandidat->pegawai_id;
                $existing = $terbaruPerPegawai[$pegawaiId] ?? null;
                $tanggalIni = $permohonan->tanggal_disetujui ?? $permohonan->created_at;
                $tanggalExisting = $existing ? ($existing['permohonan']->tanggal_disetujui ?? $existing['permohonan']->created_at) : null;

                if (!$existing || $tanggalIni->gt($tanggalExisting)) {
                    $terbaruPerPegawai[$pegawaiId] = ['kandidat' => $kandidat, 'permohonan' => $permohonan];
                }
            }
        }

        $this->info('Pegawai unik yang pernah diangkat (kandidat direkomendasikan, permohonan selesai): ' . count($terbaruPerPegawai));

        $mismatches = [];
        $pegawaiHilang = [];

        foreach ($terbaruPerPegawai as $pegawaiId => $row) {
            $kandidat = $row['kandidat'];
            $permohonan = $row['permohonan'];
            $pegawai = $kandidat->pegawai;

            if (!$pegawai) {
                $pegawaiHilang[] = ['pegawai_id' => $pegawaiId, 'kode_permohonan' => $permohonan->kode_permohonan];
                continue;
            }

            if ((int) $pegawai->formasi_jabatan_id !== (int) $kandidat->jabatan_tujuan_id) {
                $mismatches[] = [
                    'sdm_id' => $pegawai->id,
                    'nama' => $pegawai->nama_lengkap,
                    'nip' => $pegawai->nip,
                    'formasi_jabatan_id_saat_ini' => $pegawai->formasi_jabatan_id,
                    'jenjang_kode_saat_ini' => $pegawai->jenjang_kode,
                    'formasi_tujuan_seharusnya' => $kandidat->jabatan_tujuan_id,
                    'jenjang_tujuan_nama' => $kandidat->jabatanTujuan?->jenjang?->nama_jenjang,
                    'kode_permohonan' => $permohonan->kode_permohonan,
                    'tanggal_disetujui' => optional($permohonan->tanggal_disetujui)->format('Y-m-d'),
                ];
            }
        }

        $this->newLine();
        $this->info(count($mismatches) . ' mismatch ditemukan.');
        if (!empty($pegawaiHilang)) {
            $this->warn(count($pegawaiHilang) . ' kandidat menunjuk ke pegawai_id yang sudah tidak ada di sumber_daya_manusia (dilewati, bukan mismatch):');
            $this->table(['pegawai_id', 'kode_permohonan'], $pegawaiHilang);
        }

        if (!empty($mismatches)) {
            $this->table(
                ['SDM ID', 'Nama', 'NIP', 'Formasi Saat Ini', 'Jenjang Saat Ini', 'Formasi Seharusnya', 'Jenjang Tujuan', 'Kode Permohonan', 'Tgl Disetujui'],
                array_map(fn ($m) => [
                    $m['sdm_id'], $m['nama'], $m['nip'],
                    $m['formasi_jabatan_id_saat_ini'], $m['jenjang_kode_saat_ini'] ?? '-',
                    $m['formasi_tujuan_seharusnya'], $m['jenjang_tujuan_nama'] ?? '-',
                    $m['kode_permohonan'], $m['tanggal_disetujui'],
                ], $mismatches)
            );

            $filename = 'audit-jenjang-mismatch-' . now()->format('Ymd_His') . '.csv';
            $path = storage_path('app/' . $filename);
            $fh = fopen($path, 'w');
            fputcsv($fh, ['sdm_id', 'nama', 'nip', 'formasi_jabatan_id_saat_ini', 'jenjang_kode_saat_ini', 'formasi_tujuan_seharusnya', 'jenjang_tujuan_nama', 'kode_permohonan', 'tanggal_disetujui']);
            foreach ($mismatches as $m) {
                fputcsv($fh, $m);
            }
            fclose($fh);

            $this->info("Detail lengkap disimpan di: {$path}");
        }

        $this->newLine();
        $this->comment('CATATAN: "mismatch" TIDAK otomatis berarti bug -- formasi pegawai bisa berubah lagi SETELAH pengangkatan lewat proses lain yang sah (restrukturisasi Formasi/import). Command ini TIDAK mengubah data apapun, cuma melaporkan untuk ditinjau manual.');

        return self::SUCCESS;
    }
}
