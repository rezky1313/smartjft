<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
  public function pemangkuSimple()
{
    // 8 kolom jenjang baku
    $jenjangOrder = [
        'Pemula','Terampil','Mahir','Penyelia',
        'Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'
    ];

    // 22 baris JFT
    $allJft = [
        'Asisten Inspektur Angkutan Udara',
        'Inspektur Angkutan Udara',
        'Asisten Inspektur Bandar Udara',
        'Inspektur Bandar Udara',
        'Asisten Inspektur Keamanan Penerbangan',
        'Inspektur Keamanan Penerbangan',
        'Asisten Inspektur Navigasi Penerbangan',
        'Inspektur Navigasi Penerbangan',
        'Penguji Kendaraan Bermotor',
        'Pengawas Keselamatan Pelayaran',
        'Penguji Sarana Perkeretaapian',
        'Asisten Penguji Sarana Perkeretaapian',
        'Penguji Prasarana Perkeretaapian',
        'Asisten Penguji Prasarana Perkeretaapian',
        'Inspektur Sarana Perkeretaapian',
        'Inspektur Prasarana Perkeretaapian',
        'Auditor Perkeretaapian',
        'Asisten Inspektur Kelaikudaraan Pesawat Udara',
        'Inspektur Kelaikudaraan Pesawat Udara',
        'Asisten Inspektur Pengoperasian Pesawat Udara',
        'Inspektur Pengoperasian Pesawat Udara',
        'Teknisi Penerbangan',
    ];

    $hasJftType = Schema::hasTable('jft_types') && Schema::hasColumn('formasi_jabatan','jft_type_id');

    // Ambil data mentah (tanpa filter)
    $rows = DB::table('sumber_daya_manusia as sdm')
        ->whereNull('sdm.deleted_at')
        ->join('formasi_jabatan as fj','fj.id','=','sdm.formasi_jabatan_id')
        ->whereNull('fj.deleted_at')
        ->leftJoin('jenjang_jabatan as jj','jj.id','=','fj.jenjang_id')
        ->when($hasJftType, function($q){
            $q->leftJoin('jft_types as jt','jt.id','=','fj.jft_type_id');
        })
        ->selectRaw(($hasJftType ? 'COALESCE(jt.nama, fj.nama_formasi)' : 'fj.nama_formasi').' AS jft_nama')
        ->selectRaw('jj.nama_jenjang AS jj_full')  // ← ambil string jenjang lengkap (bercampur nama JFT)
        ->selectRaw('COUNT(sdm.id) AS total')
        ->groupBy('jft_nama','jj_full')
        ->get();

    // Helper: normalkan spasi
    $norm = function($s){
        return trim(preg_replace('/\s+/u',' ', (string)$s));
    };

    // Helper: ekstrak jenjang baku di bagian akhir string
    $extractJenjang = function(string $full) use ($jenjangOrder): ?string {
        foreach ($jenjangOrder as $jj) {
            // cocokkan di UJUNG string (… <spasi> Jenjang)
            if (preg_match('/\b'.preg_quote($jj,'/').'\b$/u', $full)) {
                return $jj;
            }
            // kadang DB menyimpan tepat "Ahli Muda" saja
            if ($full === $jj) return $jj;
        }
        return null; // tidak dikenali
    };

    //Siapkan matriks 0
    $matrix = [];
    foreach ($allJft as $jft) {
        foreach ($jenjangOrder as $jj) $matrix[$jft][$jj] = 0;
    }

//     // --- siapkan matriks 0 (+ satu bucket Lainnya) ---
// $otherLabel = 'Lainnya (nama JFT tidak standar)';
// $allJftPlus = array_merge($allJft, [$otherLabel]);

// $matrix = [];
// foreach ($allJftPlus as $jft) {
//     foreach ($jenjangOrder as $jj) $matrix[$jft][$jj] = 0;
// }

// // --- audit outliers & isi matriks ---
// $outliers = []; // untuk ditampilkan
// $known = array_flip($allJft);

// foreach ($rows as $r) {
//     $jftRaw = $norm($r->jft_nama);
//     $jj     = $extractJenjang($norm($r->jj_full));
//     $cnt    = (int)$r->total;

//     if (!$jj) continue; // jenjang tak dikenal, abaikan dulu

//     if (isset($known[$jftRaw])) {
//         $matrix[$jftRaw][$jj] += $cnt;
//     } else {
//         // taruh ke bucket Lainnya agar total tidak hilang
//         $matrix[$otherLabel][$jj] += $cnt;
//         $outliers[] = ['jft_raw'=>$jftRaw, 'jenjang'=>$jj, 'total'=>$cnt];
//     }
// }

// // --- hitung total baris/kolom ---
// $rowTotals = [];
// $colTotals = array_fill_keys($jenjangOrder, 0);
// $grand = 0;

// foreach ($allJftPlus as $jft) {
//     $rowTotals[$jft] = array_sum($matrix[$jft]);
//     $grand += $rowTotals[$jft];
//     foreach ($jenjangOrder as $jj) {
//         $colTotals[$jj] += $matrix[$jft][$jj];
//     }
// }

// return view('reports.pemangku.simple', [
//     'jenjangOrder'=>$jenjangOrder,
//     'allJft'      =>$allJftPlus,   // ← pakai plus agar “Lainnya” ikut tampil
//     'matrix'      =>$matrix,
//     'rowTotals'   =>$rowTotals,
//     'colTotals'   =>$colTotals,
//     'grand'       =>$grand,
//     'outliers'    =>$outliers,     // ← kirim untuk audit di view
// ]);


    // Isi matriks
    foreach ($rows as $r) {
        $jft = $norm($r->jft_nama);
        $jj  = $extractJenjang($norm($r->jj_full));
        if ($jj && in_array($jft, $allJft, true)) {
            $matrix[$jft][$jj] = (int)$r->total;
        }
    }

    // Hitung total baris/kolom/grand
    $rowTotals = [];
    $colTotals = array_fill_keys($jenjangOrder, 0);
    $grand = 0;

    foreach ($allJft as $jft) {
        $rowTotals[$jft] = array_sum($matrix[$jft]);
        $grand += $rowTotals[$jft];
        foreach ($jenjangOrder as $jj) {
            $colTotals[$jj] += $matrix[$jft][$jj];
        }
    }

    return view('reports.pemangku.simple', compact(
        'jenjangOrder','allJft','matrix','rowTotals','colTotals','grand'
    ));
}

}
