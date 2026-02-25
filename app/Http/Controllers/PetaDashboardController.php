<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Rumahsakit;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PetaDashboardController extends Controller
{

    

    private function matrixData(Request $request): array
{
    // urutan jenjang yang dipakai tabel
    $levels = ['Pemula','Terampil','Mahir','Penyelia','Pertama','Muda','Madya','Utama'];

    // 22 JFT (pastikan ejaannya sudah benar)
    $orderJft = [
        'Asisten Inspektur Angkutan Udara','Inspektur Angkutan Udara',
        'Asisten Inspektur Bandar Udara','Inspektur Bandar Udara',
        'Asisten Inspektur Keamanan Penerbangan','Inspektur Keamanan Penerbangan',
        'Asisten Inspektur Navigasi Penerbangan','Inspektur Navigasi Penerbangan',
        'Penguji Kendaraan Bermotor','Pengawas Keselamatan Pelayaran',
        'Penguji Sarana Perkeretaapian','Asisten Penguji Sarana Perkeretaapian',
        'Penguji Prasarana Perkeretaapian','Asisten Penguji Prasarana Perkeretaapian',
        'Inspektur Sarana Perkeretaapian','Inspektur Prasarana Perkeretaapian',
        'Auditor Perkeretaapian',
        'Asisten Inspektur Kelaikudaraan Pesawat Udara','Inspektur Kelaikudaraan Pesawat Udara',
        'Asisten Inspektur Pengoperasian Pesawat Udara','Inspektur Pengoperasian Pesawat Udara',
        'Teknisi Penerbangan',
    ];

    $fMatra     = $request->query('matra');
    $fFormasi   = $request->query('formasi');
    $fProvince  = $request->query('province_id');
    $fRegency   = $request->query('regency_id');

    // base query (LEFT JOIN agar baris tidak “hilang”)
    $q = DB::table('sumber_daya_manusia as sdm')
        ->join('formasi_jabatan as f','f.id','=','sdm.formasi_jabatan_id')
        ->join('jenjang_jabatan as j','j.id','=','f.jenjang_id')
        ->leftJoin('rumahsakits as rs','rs.no_rs','=','f.unit_kerja_id')
        ->leftJoin('regencies as rg','rg.id','=','rs.regency_id')
        ->leftJoin('provinces as pr','pr.id','=','rg.province_id')
        ->where('sdm.aktif', 1);

    if ($fMatra)          $q->where('rs.matra', $fMatra);
    if ($fFormasi)        $q->where('f.nama_formasi', $fFormasi);
    if (is_numeric($fRegency))      $q->where('rs.regency_id', $fRegency);
    elseif (is_numeric($fProvince)) $q->where('rg.province_id', $fProvince);

    $rows = $q->selectRaw("
                TRIM(f.nama_formasi) as jft,
                CASE
                  WHEN j.nama_jenjang LIKE '%Utama%'    THEN 'Utama'
                  WHEN j.nama_jenjang LIKE '%Madya%'    THEN 'Madya'
                  WHEN j.nama_jenjang LIKE '%Muda%'     THEN 'Muda'
                  WHEN j.nama_jenjang LIKE '%Pertama%'  THEN 'Pertama'
                  WHEN j.nama_jenjang LIKE '%Penyelia%' THEN 'Penyelia'
                  WHEN j.nama_jenjang LIKE '%Mahir%'    THEN 'Mahir'
                  WHEN j.nama_jenjang LIKE '%Terampil%' THEN 'Terampil'
                  WHEN j.nama_jenjang LIKE '%Pemula%'   THEN 'Pemula'
                  ELSE 'Lainnya'
                END as level,
                COUNT(*) as jumlah
            ")
            ->groupBy('jft','level')
            ->get();

    // siapkan matriks kosong
    $matrix    = [];
    $rowTotals = [];
    foreach ($orderJft as $nm) {
        $matrix[$nm]    = array_fill_keys($levels, 0);
        $rowTotals[$nm] = 0;
    }
    $colTotals = array_fill_keys($levels, 0);
    $grand = 0;

   foreach ($rows as $r) {
    // cek kunci jenjang DI DALAM baris JFT yang bersangkutan
    if (!isset($matrix[$r->jft]) || !array_key_exists($r->level, $matrix[$r->jft])) {
        continue;
    }
    $v = (int)$r->jumlah;
    $matrix[$r->jft][$r->level] += $v;
    $rowTotals[$r->jft]         += $v;
    $colTotals[$r->level]       += $v;
    $grand                      += $v;
}


    return compact('levels','orderJft','matrix','rowTotals','colTotals','grand');
}

// public function exportMatrixExcel(Request $request)
// {
//     $d = $this->matrixData($request);

//     // Header kolom
//     $headings = array_merge(['#','Jenis JFT'], $d['levels'], ['Total']);

//     // Baris data
//     $rows = [];
//     // Baris agregat "Semua JFT"
//     $rows[] = array_merge(
//         ['','Semua JFT'],
//         array_map(fn($lvl)=> (int)($d['colTotals'][$lvl] ?? 0), $d['levels']),
//         [(int)$d['grand']]
//     );
//     // 22 JFT
//     $i = 1;
//     foreach ($d['orderJft'] as $nm) {
//         $rows[] = array_merge(
//             [$i++, $nm],
//             array_map(fn($lvl)=> (int)($d['matrix'][$nm][$lvl] ?? 0), $d['levels']),
//             [(int)($d['rowTotals'][$nm] ?? 0)]
//         );
//     }

//     // Anonymous exporter tanpa view
//     $export = new class($rows, $headings)
//         implements \Maatwebsite\Excel\Concerns\FromArray,
//                    \Maatwebsite\Excel\Concerns\WithHeadings,
//                    \Maatwebsite\Excel\Concerns\ShouldAutoSize {

//         private array $rows; private array $headings;
//         public function __construct(array $rows, array $headings){ $this->rows=$rows; $this->headings=$headings; }
//         public function array(): array   { return $this->rows;     }
//         public function headings(): array{ return $this->headings; }
//     };

//     $filename = 'rekap_pemangku_'.now()->format('Ymd_His').'.xlsx';
//     return Excel::download($export, $filename);
// }


public function exportMatrixExcel(Request $request)
{
    $d        = $this->matrixData($request);
    $levels   = $d['levels'];        // ['Pemula','Terampil',...,'Utama']
    $orderJft = $d['orderJft'];      // 22 JFT

    $ss   = new Spreadsheet();
    $sh   = $ss->getActiveSheet();
    $sh->setTitle('Rekap Pemangku');

    // --- Header
    $r = 1; $c = 1;
    $sh->setCellValueByColumnAndRow($c++, $r, '#');
    $sh->setCellValueByColumnAndRow($c++, $r, 'Nama Jabatan');
    foreach ($levels as $lv) $sh->setCellValueByColumnAndRow($c++, $r, $lv);
    $sh->setCellValueByColumnAndRow($c++, $r, 'Total');
    $sh->getStyle("A{$r}:" . $sh->getHighestColumn() . "{$r}")->getFont()->setBold(true);

    // --- 22 baris detail (mulai nomor 1 di "Asisten Inspektur Angkutan Udara")
    $r++; $no = 1;
    foreach ($orderJft as $jft) {
        $c = 1;
        $sh->setCellValueByColumnAndRow($c++, $r, $no++);   // 1,2,3,…
        $sh->setCellValueByColumnAndRow($c++, $r, $jft);

        $rowTotal = 0;
        foreach ($levels as $lv) {
            $val = (int)($d['matrix'][$jft][$lv] ?? 0);
            $rowTotal += $val;
            $sh->setCellValueByColumnAndRow($c++, $r, $val);
        }
        $sh->setCellValueByColumnAndRow($c++, $r, $rowTotal);
        $r++;
    }

    // --- Ringkasan "Semua JFT" di PALING BAWAH (tanpa nomor)
    $c = 1;
    $sh->setCellValueByColumnAndRow($c++, $r, '');              // kolom #
    $sh->setCellValueByColumnAndRow($c++, $r, 'Total');
    foreach ($levels as $lv) {
        $sh->setCellValueByColumnAndRow($c++, $r, (int)($d['colTotals'][$lv] ?? 0));
    }
    $sh->setCellValueByColumnAndRow($c++, $r, (int)($d['grand'] ?? 0));
    $sh->getStyle("A{$r}:" . $sh->getHighestColumn() . "{$r}")->getFont()->setBold(true);

    // Auto width
    foreach (range('A', $sh->getHighestColumn()) as $col) {
        $sh->getColumnDimension($col)->setAutoSize(true);
    }

    // Stream download XLSX
    $writer   = new Xlsx($ss);
    $filename = 'rekap_pemangku_'.now()->format('Ymd_His').'.xlsx';
    return response()->streamDownload(function() use ($writer) {
        $writer->save('php://output');
    }, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]);
}




public function exportMatrixPdf(Request $request)
{
    $d   = $this->matrixData($request);
    $css = <<<CSS
      *{box-sizing:border-box}
      body{font-family:DejaVu Sans, Arial, sans-serif; font-size:11px; color:#111;}
      table{width:100%; border-collapse:collapse}
      th,td{border:1px solid #ccc; padding:6px 8px; vertical-align:top}
      thead th, tfoot th{background:#f5f6f8}
      .right{text-align:right}
      .nw{white-space:nowrap}
CSS;

    // Bangun HTML tabel langsung dari data matriks
    $html  = '<!doctype html><html><head><meta charset="utf-8"><style>'.$css.'</style></head><body>';
    $html .= '<h3 style="margin:0 0 8px;">Rekap Jumlah Pemangku Jabatan Fungsional Tansportasi<h3>';

    $html .= '<table><thead><tr>';
    $html .= '<th style="width:28px;">#</th><th>Nama Jabatan</th>';
    foreach ($d['levels'] as $lvl) $html .= '<th class="nw">'.$lvl.'</th>';
    $html .= '<th class="right">Total</th></tr></thead><tbody>';

    // Baris agregat "Semua JFT"
    // $html .= '<tr><td></td><td><b>Semua JFT</b></td>';
    // foreach ($d['levels'] as $lvl) $html .= '<td class="right"><b>'.number_format((int)($d['colTotals'][$lvl] ?? 0)).'</b></td>';
    // $html .= '<td class="right"><b>'.number_format((int)$d['grand']).'</b></td></tr>';

    // 22 JFT
    $i = 1;
    foreach ($d['orderJft'] as $nm) {
        $html .= '<tr><td>'.$i++.'</td><td>'.$nm.'</td>';
        foreach ($d['levels'] as $lvl) $html .= '<td class="right">'.number_format((int)($d['matrix'][$nm][$lvl] ?? 0)).'</td>';
        $html .= '<td class="right"><b>'.number_format((int)($d['rowTotals'][$nm] ?? 0)).'</b></td></tr>';
    }

    $html .= '</tbody><tfoot><tr><th colspan="2">Total per Jenjang</th>';
    foreach ($d['levels'] as $lvl) $html .= '<th class="right">'.number_format((int)($d['colTotals'][$lvl] ?? 0)).'</th>';
    $html .= '<th class="right">'.number_format((int)$d['grand']).'</th></tr></tfoot></table>';
    $html .= '</body></html>';

    $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'chroot'               => public_path(), // supaya asset lokal aman bila nanti butuh logo
            ])
            ->setPaper('a4','landscape')
            ->loadHTML($html);

    return $pdf->download('rekap_pemangku_'.now()->format('Ymd_His').'.pdf');
}




private function buildJftJenjangMatrix(?string $fMatra, ?string $fFormasi, ?int $fProvinceId, ?int $fRegencyId, array $orderJft, array $levels)
{
    // base query mengikuti filter dashboard (aktif saja)
    $q = DB::table('sumber_daya_manusia as sdm')
        ->join('formasi_jabatan as f','f.id','=','sdm.formasi_jabatan_id')
        ->join('jenjang_jabatan as j','j.id','=','f.jenjang_id')
        ->join('rumahsakits as rs','rs.no_rs','=','f.unit_kerja_id')
        ->join('regencies as rg','rg.id','=','rs.regency_id')
        ->join('provinces as pr','pr.id','=','rg.province_id')
        ->where('sdm.aktif',1);

    if ($fMatra)          $q->where('rs.matra',$fMatra);
    if ($fFormasi)        $q->where('f.nama_formasi',$fFormasi);
    if ($fRegencyId)      $q->where('rs.regency_id',$fRegencyId);
    elseif ($fProvinceId) $q->where('rg.province_id',$fProvinceId);

    $rows = $q->selectRaw("
                COALESCE(NULLIF(f.nama_formasi,''),'(Tanpa Nama)') as jft,
                CASE
                  WHEN j.nama_jenjang LIKE '%Utama%'    THEN 'Utama'
                  WHEN j.nama_jenjang LIKE '%Madya%'    THEN 'Madya'
                  WHEN j.nama_jenjang LIKE '%Muda%'     THEN 'Muda'
                  WHEN j.nama_jenjang LIKE '%Pertama%'  THEN 'Pertama'
                  WHEN j.nama_jenjang LIKE '%Penyelia%' THEN 'Penyelia'
                  WHEN j.nama_jenjang LIKE '%Mahir%'    THEN 'Mahir'
                  WHEN j.nama_jenjang LIKE '%Terampil%' THEN 'Terampil'
                  WHEN j.nama_jenjang LIKE '%Pemula%'   THEN 'Pemula'
                  ELSE 'Lainnya'
                END as level,
                COUNT(*) as jumlah
            ")
            ->groupBy('jft','level')
            ->get();

    // siapkan kerangka matriks sesuai urutan 22 JFT & jenjang
    $matrix = [];
    foreach ($orderJft as $nm) {
        $matrix[$nm] = array_fill_keys($levels, 0);
    }
    $rowTotals = array_fill_keys($orderJft, 0);
    $colTotals = array_fill_keys($levels, 0);
    $grand     = 0;

    foreach ($rows as $r) {
        if (!isset($matrix[$r->jft])) continue;         // abaikan formasi di luar 22 daftar
        if (!isset($matrix[$r->jft][$r->level])) continue;
        $v = (int)$r->jumlah;
        $matrix[$r->jft][$r->level] += $v;
        $rowTotals[$r->jft]         += $v;
        $colTotals[$r->level]       += $v;
        $grand                      += $v;
    }

    return [
        'jenjangOrder' => $levels,
        'allJft'       => $orderJft,
        'matrix'       => $matrix,
        'rowTotals'    => $rowTotals,
        'colTotals'    => $colTotals,
        'grand'        => $grand,
    ];
}

    
    public function index(Request $request)
    {
        
        

        // ================== FILTER INPUT ==================
        $matras = ['Darat','Laut','Udara','Kereta'];

        $unitPerMatra = Rumahsakit::select('matra')
        ->selectRaw('COUNT(*) as total')
        ->groupBy('matra')
        ->pluck('total','matra')
        ->toArray();

        $daftarFormasi = [
            'Penguji Kendaraan Bermotor',
            'Pengawas Keselamatan Pelayaran',
            'Teknisi Penerbangan',
            'Asisten Inspektur Angkutan Udara',
            'Inspektur Angkutan Udara',
            'Asisten Inspektur Bandar Udara',
            'Inspektur Bandar Udara',
            'Asisten Inspektur Keamanan Penerbangan',
            'Inspektur Keamanan Penerbangan',
            'Asisten Inspektur Navigasi Penerbangan',
            'Inspektur Navigasi Penerbangan',
            'Asisten Inspektur Kelaikudaraan Pesawat Udara',
            'Inspektur Kelaikudaraan Pesawat Udara',
            'Asisten Inspektur Pengoperasian Pesawat Udara',
            'Inspektur Pengoperasian Pesawat Udara',
            'Penguji Sarana Perkeretaapian',
            'Penguji Prasarana Perkeretaapian',
            'Inspektur Sarana Perkeretaapian',
            'Inspektur Prasarana Perkeretaapian',
            'Auditor Perkeretaapian',
            'Asisten Penguji Sarana Perkeretaapian',
            'Asisten Penguji Prasarana Perkeretaapian',
        ];

        $fMatra     = $request->query('matra');
        $fFormasi   = $request->query('formasi');
        $fProvince  = $request->query('province_id');
        $fRegency   = $request->query('regency_id');

        if (!in_array($fMatra, $matras, true)) { $fMatra = null; }
        if (!in_array($fFormasi, $daftarFormasi, true)) { $fFormasi = null; }
        $fProvinceId = is_numeric($fProvince) ? (int)$fProvince : null;
        $fRegencyId  = is_numeric($fRegency)  ? (int)$fRegency  : null;

        $isFiltered = $fMatra || $fFormasi || $fProvinceId || $fRegencyId;

        // ================== REKAP NASIONAL ==================
        $levels = ['Pemula','Terampil','Mahir','Penyelia','Pertama','Muda','Madya','Utama'];

        $totalJftAktif = DB::table('sumber_daya_manusia as sdm')
            ->where('sdm.aktif', 1)
            ->whereNotNull('sdm.formasi_jabatan_id')
            ->count();

        $rowsPerJenjang = DB::table('sumber_daya_manusia as sdm')
            ->join('formasi_jabatan as f', 'f.id', '=', 'sdm.formasi_jabatan_id')
            ->join('jenjang_jabatan as j', 'j.id', '=', 'f.jenjang_id')
            ->where('sdm.aktif', 1)
            ->selectRaw("
                CASE
                  WHEN j.nama_jenjang LIKE '%Utama%'     THEN 'Utama'
                  WHEN j.nama_jenjang LIKE '%Madya%'     THEN 'Madya'
                  WHEN j.nama_jenjang LIKE '%Muda%'      THEN 'Muda'
                  WHEN j.nama_jenjang LIKE '%Pertama%'   THEN 'Pertama'
                  WHEN j.nama_jenjang LIKE '%Penyelia%'  THEN 'Penyelia'
                  WHEN j.nama_jenjang LIKE '%Mahir%'     THEN 'Mahir'
                  WHEN j.nama_jenjang LIKE '%Terampil%'  THEN 'Terampil'
                  WHEN j.nama_jenjang LIKE '%Pemula%'    THEN 'Pemula'
                  ELSE 'Lainnya'
                END AS level,
                COUNT(*) AS jumlah
            ")
            ->groupBy('level')
            ->get();

        $perJenjang = [];
        foreach ($levels as $lvl) {
            $perJenjang[$lvl] = (int) optional($rowsPerJenjang->firstWhere('level', $lvl))->jumlah;
        }

        // ================== REKAP TERFILTER ==================
        $filteredPerJenjang = [];
        $filteredTotal = 0;

        // base query untuk rekap terfilter + top chart
        $base = DB::table('sumber_daya_manusia as sdm')
            ->join('formasi_jabatan as f', 'f.id', '=', 'sdm.formasi_jabatan_id')
            ->join('jenjang_jabatan as j', 'j.id', '=', 'f.jenjang_id')
            ->join('rumahsakits as rs', 'rs.no_rs', '=', 'f.unit_kerja_id')
            ->join('regencies as rg', 'rg.id', '=', 'rs.regency_id')
            ->join('provinces as pr', 'pr.id', '=', 'rg.province_id')
            ->where('sdm.aktif', 1);

        if ($fMatra)          { $base->where('rs.matra', $fMatra); }
        if ($fFormasi)        { $base->where('f.nama_formasi', $fFormasi); }
        if ($fRegencyId)      { $base->where('rs.regency_id', $fRegencyId); }
        elseif ($fProvinceId) { $base->where('rg.province_id', $fProvinceId); }

        if ($isFiltered) {
            $filteredTotal = (clone $base)->count();

            $rowsFiltered = (clone $base)
                ->selectRaw("
                    CASE
                      WHEN j.nama_jenjang LIKE '%Utama%'     THEN 'Utama'
                      WHEN j.nama_jenjang LIKE '%Madya%'     THEN 'Madya'
                      WHEN j.nama_jenjang LIKE '%Muda%'      THEN 'Muda'
                      WHEN j.nama_jenjang LIKE '%Pertama%'   THEN 'Pertama'
                      WHEN j.nama_jenjang LIKE '%Penyelia%'  THEN 'Penyelia'
                      WHEN j.nama_jenjang LIKE '%Mahir%'     THEN 'Mahir'
                      WHEN j.nama_jenjang LIKE '%Terampil%'  THEN 'Terampil'
                      WHEN j.nama_jenjang LIKE '%Pemula%'    THEN 'Pemula'
                      ELSE 'Lainnya'
                    END AS level,
                    COUNT(*) AS jumlah
                ")
                ->groupBy('level')
                ->get();

            foreach ($levels as $lvl) {
                $filteredPerJenjang[$lvl] = (int) optional($rowsFiltered->firstWhere('level', $lvl))->jumlah;
            }
        } else {
            $filteredPerJenjang = $perJenjang;
            $filteredTotal      = $totalJftAktif;
        }

        //-------------------------
        $matrixJft = $this->buildJftJenjangMatrix(
    $fMatra, $fFormasi, $fProvinceId, $fRegencyId,
    $daftarFormasi,           // urutan 22 JFT yg sudah ada di controller
    $levels                   // ['Pemula','Terampil',...,'Utama']
);


        // === Donut-ready data: buang kategori bernilai 0 (agar tidak muncul "0 (0.0%)")
$donutLabels = [];
$donutData   = [];
foreach ($filteredPerJenjang as $namaLvl => $jumlah) {
    if ((int) $jumlah > 0) {
        $donutLabels[] = $namaLvl;
        $donutData[]   = (int) $jumlah;
    }
}

// === Pyramid-ready data (satu sisi): urutkan kecil→besar agar bentuk piramid rapi (kecil di atas, besar di bawah)
$pyramidPairs = [];
foreach ($filteredPerJenjang as $namaLvl => $jumlah) {
    if ((int) $jumlah > 0) {
        $pyramidPairs[] = ['label' => $namaLvl, 'value' => (int) $jumlah];
    }
}
usort($pyramidPairs, fn($a, $b) => $a['value'] <=> $b['value']); // ascending

$pyramidLabels = array_column($pyramidPairs, 'label');
$pyramidValues = array_column($pyramidPairs, 'value');


        // ================== MARKERS PETA (ikut filter) ==================
        $rumahsakitsQ = Rumahsakit::with(['regency.province','formasis.jenjang'])
            ->whereNotNull('latitude')->whereNotNull('longitude');

        if ($fMatra)     { $rumahsakitsQ->where('matra', $fMatra); }
        if ($fProvinceId){ $rumahsakitsQ->whereHas('regency', fn($q)=>$q->where('province_id', $fProvinceId)); }
        if ($fRegencyId) { $rumahsakitsQ->where('regency_id', $fRegencyId); }

        $markers = $rumahsakitsQ->get(['no_rs','nama_rumahsakit','regency_id','latitude','longitude','matra','instansi'])
            ->map(function ($rs) {
                $formasiList = $rs->formasis->map(function ($f) {
                    $kuota  = (int)($f->kuota ?? 0);
                    $terisi = (int)($f->sdmAktif()->count() ?? 0);
                    return [
                        'jenjang' => $f->jenjang->nama_jenjang ?? '-',
                        'kuota'   => $kuota,
                        'terisi'  => $terisi,
                        'sisa'    => max($kuota - $terisi, 0),
                    ];
                });

                return [
                    'lat'          => (float)$rs->latitude,
                    'lng'          => (float)$rs->longitude,
                    'unit'         => $rs->nama_rumahsakit,
                    'prov'         => optional(optional($rs->regency)->province)->name,
                    'kab'          => $rs->regency ? ($rs->regency->type.' '.$rs->regency->name) : null,
                    'matra'        => $rs->matra,
                    'instansi'     => $rs->instansi,
                    'total_kuota'  => $formasiList->sum('kuota'),
                    'total_terisi' => $formasiList->sum('terisi'),
                    'total_sisa'   => $formasiList->sum('sisa'),
                    'per_jenjang'  => $formasiList->groupBy('jenjang')->map(fn($g,$k)=>[
                        'nama'=>$k,
                        'kuota'=>$g->sum('kuota'),
                        'terisi'=>$g->sum('terisi'),
                        'sisa'=>$g->sum('sisa')
                    ])->values()->all(),
                ];
            });


        // ================== DATA CHART 3: LINE JFT PER JENJANG PER TAHUN ==================
$rowsLine = DB::table('sumber_daya_manusia as sdm')
    ->join('formasi_jabatan as f', 'f.id', '=', 'sdm.formasi_jabatan_id')
    ->join('jenjang_jabatan as j', 'j.id', '=', 'f.jenjang_id')
    ->where('sdm.aktif', 1)
    ->selectRaw("
        f.tahun_formasi as tahun_formasi,
        CASE
          WHEN j.nama_jenjang LIKE '%Utama%'     THEN 'Utama'
          WHEN j.nama_jenjang LIKE '%Madya%'     THEN 'Madya'
          WHEN j.nama_jenjang LIKE '%Muda%'      THEN 'Muda'
          WHEN j.nama_jenjang LIKE '%Pertama%'   THEN 'Pertama'
          WHEN j.nama_jenjang LIKE '%Penyelia%'  THEN 'Penyelia'
          WHEN j.nama_jenjang LIKE '%Mahir%'     THEN 'Mahir'
          WHEN j.nama_jenjang LIKE '%Terampil%'  THEN 'Terampil'
          WHEN j.nama_jenjang LIKE '%Pemula%'    THEN 'Pemula'
          ELSE 'Lainnya'
        END AS level,
        COUNT(*) as jumlah
    ")
    ->groupBy('tahun_formasi','level')
    ->orderBy('tahun_formasi')
    ->get();

// susun data menjadi format [ 'labels' => [2020,2021,...], 'datasets' => [ {label:'Utama', data:[..]}, ... ] ]
$lineYears = $rowsLine->pluck('tahun_formasi')->unique()->sort()->values()->all();

$lineDatasets = [];
foreach ($levels as $lvl) {
    $lineDatasets[$lvl] = array_fill(0, count($lineYears), 0);
}

foreach ($rowsLine as $r) {
    $yearIndex = array_search($r->tahun_formasi, $lineYears);
    if ($yearIndex !== false) {
        $lineDatasets[$r->level][$yearIndex] = (int) $r->jumlah;
    }
}

// siapkan untuk dikirim ke view
$lineChartYears = $lineYears;
$lineChartData = [];
foreach ($levels as $lvl) {
    $lineChartData[] = [
        'label' => $lvl,
        'data'  => $lineDatasets[$lvl],
    ];
}


        // ================== DROPDOWN DATA ==================
        $provinces = Province::orderBy('name')->get(['id','name']);
        $regencies = collect();
        if ($fProvinceId) {
            $regencies = Regency::where('province_id', $fProvinceId)
                ->orderBy('type')->orderBy('name')
                ->get(['id','name','type']);
        }

        $jumlahrs = Rumahsakit::count();


   return view('users.dashboard', compact(
    'markers','jumlahrs',
    'totalJftAktif','perJenjang','levels',
    'matras','daftarFormasi','provinces','regencies',
    'fMatra','fFormasi','fProvinceId','fRegencyId',
    'filteredTotal','filteredPerJenjang',
    'donutLabels','donutData',
    'lineChartYears','lineChartData',
    'unitPerMatra',
    'pyramidLabels','pyramidValues',
    'matrixJft' // <—— tambahkan ini

));


    }

    public function regencies($provinceId)
    {
        $rows = Regency::where('province_id', $provinceId)
            ->orderBy('type')->orderBy('name')
            ->get(['id','name','type']);

        // kembalikan standar
        return $rows->map(fn($r)=>['id'=>$r->id,'name'=>$r->name,'type'=>$r->type])->values();
    }
}
