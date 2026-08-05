<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UnitKerja;

/**
 * PKR-03 -- Analitik & Tren Pengembangan Karir JFT. Level AGREGAT/NASIONAL (beda dari
 * PkrController yang per-individu pegawai) -- controller terpisah karena scope dan cara
 * kerjanya beda: semua angka di sini murni hasil GROUP BY/aggregate SQL, PHP cuma
 * merapikan format output (bukan loop hitung per SDM seperti status komposit PKR-01).
 */
class PkrAnalitikController extends Controller
{
    /**
     * Label jenjang resmi per short-code, dipakai utk CASE WHEN SQL (mengubah
     * jenjang_jabatan.nama_jenjang jadi short-code yang SAMA dengan Sdmmodels::jenjang_kode).
     */
    private const JENJANG_LABEL = [
        'ahli_pertama' => 'Ahli Pertama',
        'ahli_muda' => 'Ahli Muda',
        'ahli_madya' => 'Ahli Madya',
        'ahli_utama' => 'Ahli Utama',
        'penyelia' => 'Penyelia',
        'terampil' => 'Terampil',
        'mahir' => 'Mahir',
        'pemula' => 'Pemula',
    ];

    public function index(Request $request)
    {
        $tren = $this->trenPerTahun(
            $request->integer('dari') ?: null,
            $request->integer('sampai') ?: null
        );

        $tahunFormasiDefault = $this->tahunFormasiTerbanyakSdm();
        $tahunFormasiDipilih = (int) ($request->input('tahun_formasi') ?: $tahunFormasiDefault);
        $daftarTahunFormasi = DB::table('formasi_jabatan')->whereNull('deleted_at')->distinct()->orderByDesc('tahun_formasi')->pluck('tahun_formasi');

        $formasi = $this->analisisFormasi($tahunFormasiDipilih);

        $unitKerjaList = UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja']);

        return view('pkr.analitik', array_merge($tren, [
            'formasiNasional' => $formasi['nasional'],
            'formasiPerUnit' => $formasi['per_unit'],
            'tahunFormasiDipilih' => $tahunFormasiDipilih,
            'daftarTahunFormasi' => $daftarTahunFormasi,
            'unitKerjaList' => $unitKerjaList,
        ]));
    }

    /**
     * Task 1: tren jumlah pengangkatan per tahun, breakdown per jenjang DAN per moda
     * (matra) -- keduanya dihitung sekaligus (server-side), toggle di UI TANPA reload/AJAX.
     *
     * KEPUTUSAN (dikonfirmasi user, diagnostik PKR-03): dasar tahun = YEAR(tmt_pengangkatan),
     * BUKAN formasi_jabatan.tahun_formasi (itu tahun anggaran formasi, bukan tanggal
     * pengangkatan -- dipakai chart "Tren per Tahun" Dashboard yang sudah ada, tapi beda
     * makna dari yang diminta di sini). tmt_pengangkatan baru terisi 15/3.940 (0,4%) pegawai
     * saat ini -- chart akan terlihat sepi sampai lebih banyak pengangkatan lewat sistem.
     */
    public function trenPerTahun(?int $dari = null, ?int $sampai = null): array
    {
        // NB: sumber_daya_manusia/formasi_jabatan/unit_kerja SEMUA pakai SoftDeletes --
        // DB::table() query builder TIDAK otomatis exclude baris soft-deleted (beda dari
        // Eloquent model), jadi whereNull('deleted_at') WAJIB ditulis eksplisit di semua
        // query raw di controller ini. Ketahuan lewat cross-check manual (sum kuota Task 2
        // sempat kelebihan 78 krn 16 baris formasi_jabatan soft-deleted ikut terhitung).
        $rentang = DB::table('sumber_daya_manusia')
            ->whereNotNull('tmt_pengangkatan')
            ->whereNull('deleted_at')
            ->selectRaw('MIN(YEAR(tmt_pengangkatan)) as min_tahun, MAX(YEAR(tmt_pengangkatan)) as max_tahun')
            ->first();

        $tahunMin = $dari ?? (int) ($rentang->min_tahun ?? now()->year);
        $tahunMax = $sampai ?? (int) ($rentang->max_tahun ?? now()->year);
        $tahunList = range($tahunMin, $tahunMax);

        // --- Breakdown per jenjang ---
        $rowsJenjang = DB::table('sumber_daya_manusia')
            ->whereNotNull('tmt_pengangkatan')
            ->whereNotNull('jenjang_kode')
            ->whereNull('deleted_at')
            ->where('aktif', 1)
            ->whereBetween(DB::raw('YEAR(tmt_pengangkatan)'), [$tahunMin, $tahunMax])
            ->selectRaw('YEAR(tmt_pengangkatan) as tahun, jenjang_kode, COUNT(*) as jumlah')
            ->groupBy('tahun', 'jenjang_kode')
            ->get();

        $jenjangKodeAda = $rowsJenjang->pluck('jenjang_kode')->unique()->values();
        $datasetJenjang = [];
        foreach (self::JENJANG_LABEL as $kode => $label) {
            if (!$jenjangKodeAda->contains($kode)) {
                continue;
            }
            $data = array_fill(0, count($tahunList), 0);
            foreach ($rowsJenjang->where('jenjang_kode', $kode) as $r) {
                $idx = array_search((int) $r->tahun, $tahunList, true);
                if ($idx !== false) {
                    $data[$idx] = (int) $r->jumlah;
                }
            }
            $datasetJenjang[] = ['label' => $label, 'data' => $data];
        }

        // --- Breakdown per moda (matra) ---
        $rowsModa = DB::table('sumber_daya_manusia as s')
            ->leftJoin('formasi_jabatan as f', function ($j) {
                $j->on('f.id', '=', 's.formasi_jabatan_id')->whereNull('f.deleted_at');
            })
            ->join('unit_kerja as u', DB::raw('COALESCE(s.unit_kerja_id, f.unit_kerja_id)'), '=', 'u.id')
            ->whereNotNull('s.tmt_pengangkatan')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereBetween(DB::raw('YEAR(s.tmt_pengangkatan)'), [$tahunMin, $tahunMax])
            ->selectRaw('YEAR(s.tmt_pengangkatan) as tahun, u.matra, COUNT(*) as jumlah')
            ->groupBy('tahun', 'u.matra')
            ->get();

        $modaAda = $rowsModa->pluck('matra')->unique()->values();
        $datasetModa = [];
        foreach ($modaAda as $matra) {
            $data = array_fill(0, count($tahunList), 0);
            foreach ($rowsModa->where('matra', $matra) as $r) {
                $idx = array_search((int) $r->tahun, $tahunList, true);
                if ($idx !== false) {
                    $data[$idx] = (int) $r->jumlah;
                }
            }
            $datasetModa[] = ['label' => $matra, 'data' => $data];
        }

        return [
            'trenTahunList' => $tahunList,
            'trenTahunMinData' => (int) ($rentang->min_tahun ?? $tahunMin),
            'trenTahunMaxData' => (int) ($rentang->max_tahun ?? $tahunMax),
            'trenDatasetJenjang' => $datasetJenjang,
            'trenDatasetModa' => $datasetModa,
        ];
    }

    /** Tahun formasi dengan jumlah SDM aktif-berformasi terbanyak (bukan now()->year -- lihat diagnostik). */
    private function tahunFormasiTerbanyakSdm(): int
    {
        $row = DB::table('sumber_daya_manusia as s')
            ->join('formasi_jabatan as f', 'f.id', '=', 's.formasi_jabatan_id')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('f.deleted_at')
            ->selectRaw('f.tahun_formasi, COUNT(*) as c')
            ->groupBy('f.tahun_formasi')
            ->orderByDesc('c')
            ->first();

        return $row ? (int) $row->tahun_formasi : now()->year;
    }

    /**
     * Task 2: analisis formasi tersedia/terisi/kosong -- agregat nasional + per unit kerja,
     * breakdown per jenjang. "Kosong" TIDAK PERNAH minus (GREATEST(...,0) di SQL), kelebihan
     * dicatat terpisah sebagai over_kuota -- konsisten dgn soft-warning Formasijabatan::sisa
     * yang sudah ada (sisa boleh minus di sana, di sini dipisah jadi 2 angka non-negatif).
     *
     * "Terisi" dihitung TERPISAH dari "tersedia" (2 query beda, bukan 1 query pakai LEFT
     * JOIN+SUM) supaya tidak kena fan-out: LEFT JOIN formasi->SDM mengalikan baris formasi
     * sebanyak SDM yang match, jadi SUM(kuota) di query gabungan akan salah hitung berkali-lipat.
     */
    public function analisisFormasi(int $tahunFormasi): array
    {
        $jenjangCase = $this->sqlCaseJenjang('j.nama_jenjang');

        // Tersedia (SUM kuota), per unit + jenjang.
        // GROUP BY pakai ekspresi CASE WHEN yang sama persis (bukan alias) -- MySQL
        // only_full_group_by menolak GROUP BY alias hasil ekspresi non-agregat.
        $tersediaPerUnit = DB::table('formasi_jabatan as f')
            ->join('jenjang_jabatan as j', 'j.id', '=', 'f.jenjang_id')
            ->where('f.tahun_formasi', $tahunFormasi)
            ->whereNull('f.deleted_at')
            ->selectRaw("f.unit_kerja_id, {$jenjangCase} as jenjang_kode, SUM(f.kuota) as tersedia")
            ->groupBy('f.unit_kerja_id')
            ->groupByRaw($jenjangCase)
            ->get();

        // Terisi (COUNT SDM aktif), per unit + jenjang
        $terisiPerUnit = DB::table('sumber_daya_manusia as s')
            ->join('formasi_jabatan as f', 'f.id', '=', 's.formasi_jabatan_id')
            ->join('jenjang_jabatan as j', 'j.id', '=', 'f.jenjang_id')
            ->where('s.aktif', 1)
            ->where('f.tahun_formasi', $tahunFormasi)
            ->whereNull('s.deleted_at')
            ->whereNull('f.deleted_at')
            ->selectRaw("f.unit_kerja_id, {$jenjangCase} as jenjang_kode, COUNT(*) as terisi")
            ->groupBy('f.unit_kerja_id')
            ->groupByRaw($jenjangCase)
            ->get();

        $perUnit = $this->gabungTersediaTerisi($tersediaPerUnit, $terisiPerUnit, true);
        $nasional = $this->gabungTersediaTerisi($tersediaPerUnit, $terisiPerUnit, false);

        return ['per_unit' => $perUnit, 'nasional' => $nasional];
    }

    /**
     * Gabungkan hasil query "tersedia" dan "terisi" (sudah teragregat SQL) jadi satu struktur
     * per baris kosong/over_kuota -- murni format-ulang array kecil (bukan hitung ulang per SDM).
     */
    private function gabungTersediaTerisi($tersediaRows, $terisiRows, bool $perUnit): array
    {
        $result = [];

        foreach ($tersediaRows as $row) {
            $key = $perUnit ? $row->unit_kerja_id . '|' . $row->jenjang_kode : $row->jenjang_kode;
            if (!isset($result[$key])) {
                $result[$key] = [
                    'unit_kerja_id' => $perUnit ? $row->unit_kerja_id : null,
                    'jenjang_kode' => $row->jenjang_kode,
                    'tersedia' => 0,
                    'terisi' => 0,
                ];
            }
            $result[$key]['tersedia'] += (int) $row->tersedia;
        }

        foreach ($terisiRows as $row) {
            $key = $perUnit ? $row->unit_kerja_id . '|' . $row->jenjang_kode : $row->jenjang_kode;
            if (!isset($result[$key])) {
                $result[$key] = [
                    'unit_kerja_id' => $perUnit ? $row->unit_kerja_id : null,
                    'jenjang_kode' => $row->jenjang_kode,
                    'tersedia' => 0,
                    'terisi' => 0,
                ];
            }
            $result[$key]['terisi'] += (int) $row->terisi;
        }

        foreach ($result as &$r) {
            $r['kosong'] = max($r['tersedia'] - $r['terisi'], 0);
            $r['over_kuota'] = max($r['terisi'] - $r['tersedia'], 0);
            $r['label_jenjang'] = self::JENJANG_LABEL[$r['jenjang_kode']] ?? $r['jenjang_kode'];
        }
        unset($r);

        return array_values($result);
    }

    /** CASE WHEN SQL: nama_jenjang -> short-code (SAMA taksonomi dgn Sdmmodels::jenjang_kode). */
    private function sqlCaseJenjang(string $column): string
    {
        $when = '';
        foreach (self::JENJANG_LABEL as $kode => $label) {
            $when .= "WHEN {$column} LIKE '%{$label}' THEN '{$kode}' ";
        }
        return "CASE {$when} ELSE 'lainnya' END";
    }
}
