<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Formasijabatan;
use App\Models\Rumahsakit;
use App\Models\Jenjangjabatan;   // <- nama class kamu memang ini
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\FormasiJabatanHistori;

class FormasiJabatanController extends Controller
{
    /* ========================
     * Konstanta & Helper
     * ======================*/
    private array $DAFTAR_FORMASI_JFT = [
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
        'Penguji Sarana Perkeretaapiaan',
        'Penguji Prasarana Perkeretaapian',
        'Inspektur Sarana Perkeretaapian',
        'Inspektur Prasarana Perkeretaapian',
        'Auditor Perkeretaapian',
        'Asisten Penguji Sarana Perkeretaapian',
        'Asisten Penguji Prasarana Perkeretaapian',
    ];

    // kolom yang akan muncul di pivot (mengikuti contoh Excel)
    private array $PIVOT_COLS = ['Pemula','Terampil','Mahir','Penyelia','Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'];

    private function normLevel(?string $nama): ?string
    {
        if (!$nama) return null;
        $x = mb_strtolower($nama);
        if (str_contains($x, 'pemula')) return 'Pemula';
        if (str_contains($x, 'terampil')) return 'Terampil';
        if (str_contains($x, 'mahir'))    return 'Mahir';
        if (str_contains($x, 'penyelia')) return 'Penyelia';
        if (str_contains($x, 'pertama'))  return 'Ahli Pertama';
        if (str_contains($x, 'muda'))     return 'Ahli Muda';
        if (str_contains($x, 'madya'))    return 'Ahli Madya';
        if (str_contains($x, 'utama'))    return 'Ahli Utama';
        return null;
    }

    /* ========================
     * INDEX: tabel pivot (mengikuti gambar)
     * ======================*/
  public function index(Request $req)
{
    // ========= FILTER DARI QUERYSTRING =========
    $tahun      = $req->query('tahun');            // optional
    $unitId     = $req->query('unit_kerja_id');    // optional
    $provinceId = $req->query('province_id');      // optional (baru)
    $regencyId  = $req->query('regency_id');       // optional (baru)

    // Kolom pivot per jenjang
    $cols = ['Pemula','Terampil','Mahir','Penyelia','Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'];

    // ========= QUERY FORMASI (belum get) =========
    $q = \App\Models\Formasijabatan::query()
        ->with([
            'jenjang:id,nama_jenjang',
            'unitkerja:no_rs,nama_rumahsakit,regency_id',
            'unitkerja.regency:id,name,type,province_id',
            'unitkerja.regency.province:id,name',
        ])
        ->withCount(['sdmAktif as terisi']);

    if ($tahun)  { $q->where('tahun_formasi', $tahun); }
    if ($unitId) { $q->where('unit_kerja_id', $unitId); }

    // filter wilayah (BARU)
    if ($regencyId) {
        $q->whereHas('unitkerja', fn($uq) => $uq->where('regency_id', $regencyId));
    } elseif ($provinceId) {
        $q->whereHas('unitkerja.regency', fn($rq) => $rq->where('province_id', $provinceId));
    }

    // ambil data
    $rows = $q->orderBy('unit_kerja_id')->orderBy('nama_formasi')->get();

    // ========= BANGUN PIVOT =========
    $table = []; // [unitName => [ '_meta' => [...], key => [jabatan, kuota[], terisi[], sisa[]] ]]
    foreach ($rows as $f) {
        $unitName = optional($f->unitkerja)->nama_rumahsakit ?? ('Unit #'.$f->unit_kerja_id);
        $jabatan  = $f->nama_formasi ?? '-';
        $lvlName  = $this->normLevel(optional($f->jenjang)->nama_jenjang);
        if (!$lvlName) continue;

        // Simpan metadata unit kerja
        if (!isset($table[$unitName]['_meta'])) {
            $table[$unitName]['_meta'] = [
                'unit_kerja_id' => $f->unit_kerja_id,
                'tahuns' => [],
            ];
        }
        // Kumpulkan tahun-tahun yang ada di unit kerja ini
        $tahunFormasi = $f->tahun_formasi ?? null;
        if ($tahunFormasi && !in_array($tahunFormasi, $table[$unitName]['_meta']['tahuns'])) {
            $table[$unitName]['_meta']['tahuns'][] = $tahunFormasi;
        }

        $key = md5($unitName.'|'.$jabatan);

        if (!isset($table[$unitName][$key])) {
            $table[$unitName][$key] = [
                'jabatan' => $jabatan,
                'kuota'   => array_fill_keys($cols, 0),
                'terisi'  => array_fill_keys($cols, 0),
                'sisa'    => array_fill_keys($cols, 0),
            ];
        }

        $kuota  = (int)($f->kuota ?? 0);
        $terisi = (int)($f->terisi ?? 0);

        $table[$unitName][$key]['kuota'][$lvlName]  += $kuota;
        $table[$unitName][$key]['terisi'][$lvlName] += $terisi;
        $table[$unitName][$key]['sisa'][$lvlName]    =
            $table[$unitName][$key]['kuota'][$lvlName] - $table[$unitName][$key]['terisi'][$lvlName];
        // Sisa bisa MINUS (over kuota diizinkan) - tanpa max(0, ...)
    }

    // ========= DATA DROPDOWN =========
    // Provinces (selalu ada)
    $provinces = \App\Models\Province::orderBy('name')->get(['id','name']);

    // Regencies (hanya yg berada di province terpilih)
    $regencies = collect();
    if ($provinceId) {
        $regencies = \App\Models\Regency::where('province_id', $provinceId)
            ->orderBy('type')->orderBy('name')
            ->get(['id','name','type','province_id']);
    }

    // Units, ikut filter province/regency bila diisi
    $unitsQ = \App\Models\Rumahsakit::query()
        ->select('no_rs','nama_rumahsakit','regency_id')
        ->orderBy('nama_rumahsakit');

    if ($regencyId) {
        $unitsQ->where('regency_id', $regencyId);
    } elseif ($provinceId) {
        $unitsQ->whereHas('regency', fn($rq) => $rq->where('province_id', $provinceId));
    }
    $units = $unitsQ->get();

    // Tahun & trash count
    $tahuns  = \App\Models\Formasijabatan::select('tahun_formasi')->distinct()
                ->orderBy('tahun_formasi')->pluck('tahun_formasi');
    $trashed = \App\Models\Formasijabatan::onlyTrashed()->count();

    // kirim ke view
    return view('formasi_jabatan.index', [
        'cols'      => $cols,
        'table'     => $table,
        'units'     => $units,
        'tahuns'    => $tahuns,
        'trashed'   => $trashed,
        'provinces' => $provinces,
        'regencies' => $regencies,
        'filter'    => [
            'tahun'        => $tahun,
            'unit_kerja_id'=> $unitId,
            'province_id'  => $provinceId,
            'regency_id'   => $regencyId,
        ],
    ]);
}




public function create()
{
    // HARUS: $unitkerja, $jenjang, $daftarFormasi (bukan $units / $jenjangs)
    $unitkerja = \App\Models\Rumahsakit::orderBy('nama_rumahsakit')
        ->get(['no_rs','nama_rumahsakit']);

    $jenjang = \App\Models\Jenjangjabatan::orderBy('kategori')
        ->orderBy('nama_jenjang')
        ->get(['id','nama_jenjang','kategori']);

    $daftarFormasi = $this->DAFTAR_FORMASI_JFT;

    return view('formasi_jabatan.create', compact('unitkerja','jenjang','daftarFormasi'));
}


    public function store(Request $request)
    {
        // Dukung 2 bentuk: (A) multi items[]  (B) single field lama (fallback)
        $isMulti = $request->has('items');

        if ($isMulti) {
            $data = $request->validate([
                'unit_kerja_id'            => ['required','integer','exists:rumahsakits,no_rs'],
                'tahun_formasi'            => ['required','string','max:50'],
                'items'                    => ['required','array','min:1'],
                'items.*.nama_formasi'     => ['required', Rule::in($this->DAFTAR_FORMASI_JFT)],
                'items.*.jenjang_id'       => ['required','integer','exists:jenjang_jabatan,id'],
                'items.*.kuota'            => ['required','integer','min:0'],
            ]);

            DB::transaction(function() use ($data) {
                foreach ($data['items'] as $row) {
                    Formasijabatan::create([
                        'unit_kerja_id' => $data['unit_kerja_id'],
                        'tahun_formasi' => $data['tahun_formasi'],
                        'nama_formasi'  => $row['nama_formasi'],
                        'jenjang_id'    => $row['jenjang_id'],
                        'kuota'         => $row['kuota'],
                    ]);
                }
            });

        } else {
            // fallback: form lama (single record)
            $request->validate([
                'nama_formasi'  => ['required', Rule::in($this->DAFTAR_FORMASI_JFT)],
                'jenjang_id'    => 'required|exists:jenjang_jabatan,id',
                'unit_kerja_id' => 'required|exists:rumahsakits,no_rs',
                'kuota'         => 'required|integer|min:0',
                'tahun_formasi' => 'required|string|max:50',
            ]);

            Formasijabatan::create($request->only([
                'nama_formasi','jenjang_id','unit_kerja_id','kuota','tahun_formasi'
            ]));
        }

        return redirect()->route('user.formasi.index')
            ->with('success', 'Data formasi berhasil ditambahkan.');
    }

    /* ========================
     * EDIT/UPDATE (GRUP) – Unit Kerja + Tahun
     * ======================*/
//     public function editGroup(Request $request)
// {
//     $unitId = $request->query('unit_kerja_id');
//     $tahun  = $request->query('tahun_formasi');
//     abort_if(!$unitId || !$tahun, 404);

//     $mode = 'edit';
//     $unit = \App\Models\Rumahsakit::findOrFail($unitId, ['no_rs','nama_rumahsakit']);
//     $rows = \App\Models\Formasijabatan::where('unit_kerja_id',$unitId)
//             ->where('tahun_formasi',$tahun)
//             ->orderBy('nama_formasi')
//             ->get(['id','nama_formasi','jenjang_id','kuota']);
//     $jenjangs = \App\Models\Jenjangjabatan::orderBy('kategori')
//         ->orderBy('nama_jenjang')
//         ->get(['id','nama_jenjang','kategori']);

//     // gunakan view create yang sama (punya switch $mode)
//     return view('formasi_jabatan.create', compact('mode','unit','tahun','rows','jenjangs'));
// }

// public function editGroup(Request $req)
// {
//     $unitId = $req->query('unit_kerja_id');
//     $tahun  = $req->query('tahun_formasi');

//     // data formasi sesuai unit & tahun
//     $formasi = \App\Models\Formasijabatan::with(['jenjang','unitkerja'])
//         ->where('unit_kerja_id', $unitId)
//         ->where('tahun_formasi', $tahun)
//         ->orderBy('jenjang_id')
//         ->get();

//     // dropdown jenjang
//     $jenjang = \App\Models\Jenjangjabatan::orderBy('kategori')->orderBy('nama_jenjang')->get();

//     // dropdown unit kerja (supaya $unitkerja tidak undefined)
//     $unitkerja = \App\Models\Rumahsakit::orderBy('nama_rumahsakit')
//         ->get(['no_rs','nama_rumahsakit']);

//     return view('formasi_jabatan.edit-group', compact('formasi','jenjang','unitkerja','unitId','tahun'));
// }


public function editGroup(Request $request, $unit = null, $tahun = null)
{
    $unit  = $unit  ?? $request->query('unit');
    $tahun = $tahun ?? $request->query('tahun');

    if (!$unit || !$tahun) {
        return redirect()->route('user.formasi.index')
            ->with('error','Pilih Unit & Tahun dulu sebelum Edit Grup.');
    }

    $rows = \App\Models\Formasijabatan::with('jenjang')
        ->where('unit_kerja_id', $unit)
        ->where('tahun_formasi', $tahun) // kalau kolom disimpan string, ini tetap aman
        ->orderBy('jenjang_id')
        ->get();

    $jenjang = \App\Models\Jenjangjabatan::orderBy('kategori')
        ->orderBy('nama_jenjang')->get();

    $unitkerja = \App\Models\Rumahsakit::orderBy('nama_rumahsakit')
        ->get(['no_rs','nama_rumahsakit']);

    $unitRow = \App\Models\Rumahsakit::where('no_rs', $unit)->first();

    return view('formasi_jabatan.edit-group', [
        'rows'          => $rows,                  // <<< pakai 'rows' agar cocok dengan blade
        'jenjang'       => $jenjang,
        'unitkerja'     => $unitkerja,
        'unit'          => $unitRow,
        'tahun'         => $tahun,
        'daftarFormasi' => $this->DAFTAR_FORMASI_JFT,
        'mode'          => 'group',
    ]);
}





//    public function updateGroup(Request $request)
// {
//     $data = $request->validate([
//         'unit_kerja_id'              => ['required','integer','exists:rumahsakits,no_rs'],
//         'tahun_formasi'              => ['required','string','max:50'],
//         'items'                      => ['required','array','min:1'],
//         'items.*.id'                 => ['nullable','integer','exists:formasi_jabatan,id'],
//         'items.*.nama_formasi'       => ['required', Rule::in($this->DAFTAR_FORMASI_JFT)],
//         'items.*.jenjang_id'         => ['required','integer','exists:jenjang_jabatan,id'],
//         'items.*.kuota'              => ['required','integer','min:0'],
//     ]);

//     DB::transaction(function() use ($data) {
//         $keep = [];
//         foreach ($data['items'] as $row) {
//             if (!empty($row['id'])) {
//                 $m = \App\Models\Formasijabatan::findOrFail($row['id']);
//                 $m->update([
//                     'nama_formasi' => $row['nama_formasi'],
//                     'jenjang_id'   => $row['jenjang_id'],
//                     'kuota'        => $row['kuota'],
//                 ]);
//                 $keep[] = (int)$row['id'];
//             } else {
//                 $m = \App\Models\Formasijabatan::create([
//                     'unit_kerja_id' => $data['unit_kerja_id'],
//                     'tahun_formasi' => $data['tahun_formasi'],
//                     'nama_formasi'  => $row['nama_formasi'],
//                     'jenjang_id'    => $row['jenjang_id'],
//                     'kuota'         => $row['kuota'],
//                 ]);
//                 $keep[] = (int)$m->id;
//             }
//         }

//         \App\Models\Formasijabatan::where('unit_kerja_id',$data['unit_kerja_id'])
//             ->where('tahun_formasi',$data['tahun_formasi'])
//             ->whereNotIn('id', $keep)
//             ->delete();
//     });

//     return redirect()->route('user.formasi.index')->with('success','Perubahan formasi tersimpan.');
// }

public function updateGroup(Request $request)
{
    $data = $request->validate([
        'unit_kerja_id'        => ['required','integer','exists:rumahsakits,no_rs'],
        'tahun_formasi'        => ['required','string','max:50'],
        'items'                => ['required','array','min:1'],
        'items.*.nama_formasi' => ['required', Rule::in($this->DAFTAR_FORMASI_JFT)],
        'items.*.jenjang_id'   => ['required','integer','exists:jenjang_jabatan,id'],
        'items.*.kuota'        => ['required','integer','min:0'],
    ]);

    $unitId = (int)$data['unit_kerja_id'];
    $tahun  = (string)$data['tahun_formasi'];
    $items  = $data['items'];

    DB::transaction(function () use ($unitId, $tahun, $items) {

        // 1) Snapshot set lama ke histori
        $this->snapshotFormasiSet($unitId, $tahun);

        // 2) Hapus set lama
        Formasijabatan::where('unit_kerja_id', $unitId)
            ->where('tahun_formasi', $tahun)
            ->delete();

        // 3) Insert set baru
        $rows = [];
        foreach ($items as $it) {
            $rows[] = [
                'unit_kerja_id' => $unitId,
                'tahun_formasi' => $tahun,
                'nama_formasi'  => $it['nama_formasi'],
                'jenjang_id'    => (int)$it['jenjang_id'],
                'kuota'         => (int)$it['kuota'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        if ($rows) {
            Formasijabatan::insert($rows);
        }
    });

    return redirect()
        ->route('user.formasi.index', ['unit_kerja_id'=>$unitId, 'tahun'=>$tahun])
        ->with('success','Formasi diperbarui & versi lama disimpan ke histori.');
}

/**
 * Hapus semua formasi dalam unit kerja & tahun tertentu
 */
public function deleteGroup(Request $request)
{
    $unitId = $request->query('unit');
    $tahun  = $request->query('tahun');

    if (!$unitId) {
        return redirect()
            ->route('user.formasi.index')
            ->with('error', 'Unit kerja tidak valid.');
    }

    // Query builder untuk hapus
    $query = Formasijabatan::where('unit_kerja_id', $unitId);

    if ($tahun) {
        // Hapus hanya tahun tertentu
        $query->where('tahun_formasi', $tahun);
        $message = "Semua formasi untuk tahun {$tahun} berhasil dihapus.";
    } else {
        // Hapus semua tahun
        $message = "Semua formasi (semua tahun) berhasil dihapus.";
    }

    // Hitung jumlah yang akan dihapus
    $count = $query->count();

    if ($count === 0) {
        return redirect()
            ->route('user.formasi.index')
            ->with('error', 'Tidak ada data formasi yang ditemukan.');
    }

    // Lakukan penghapusan
    $query->delete();

    return redirect()
        ->route('user.formasi.index')
        ->with('success', "{$count} data formasi berhasil dihapus. {$message}");
}


    /* ========================
     * ENDPOINT LAMA (single row) – tetap ada
     * ======================*/
    public function edit($id)
    {
        $formasi   = Formasijabatan::with('jenjang')->withCount(['sdmAktif as terisi'])->findOrFail($id);
        $unitkerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs','nama_rumahsakit']);
        $jenjang   = Jenjangjabatan::orderBy('kategori')->orderBy('nama_jenjang')->get(['id','nama_jenjang','kategori']);
        $daftarFormasi = $this->DAFTAR_FORMASI_JFT;

        return view('formasi_jabatan/edit', compact('formasi','unitkerja','jenjang','daftarFormasi'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_formasi'  => ['required', Rule::in($this->DAFTAR_FORMASI_JFT)],
            'jenjang_id'    => 'required|exists:jenjang_jabatan,id',
            'unit_kerja_id' => 'required|exists:rumahsakits,no_rs',
            'kuota'         => 'required|integer|min:0',
            'tahun_formasi' => 'required|string|max:50',
        ]);

        $formasi = Formasijabatan::findOrFail($id);
        $formasi->update($request->only(['nama_formasi','jenjang_id','unit_kerja_id','kuota','tahun_formasi']));

        return redirect()->route('user.formasi.index')->with('success', 'Data berhasil diubah.');
    }

    public function destroy($id)
    {
        $formasi = Formasijabatan::findOrFail($id);
        $formasi->delete();
        return redirect()->route('user.formasi.index')->with('success', 'Data berhasil dihapus.');
    }

    public function trash()
    {
        $formasi = Formasijabatan::onlyTrashed()
            ->with(['unitKerja','jenjang'])
            ->orderBy('nama_formasi')->get();

        return view('formasi_jabatan.trash', compact('formasi'));
    }

    public function restore($id)
    {
        $f = Formasijabatan::withTrashed()->findOrFail($id);
        $f->restore();
        return back()->with('success','Formasi direstore.');
    }

    public function forceDelete($id)
    {
        $f = Formasijabatan::withTrashed()->findOrFail($id);
        $f->forceDelete();
        return back()->with('success','Formasi dihapus permanen.');
    }


public function importPivotForm()
{
    return view('formasi_jabatan.import_pivot'); // view upload sederhana (tahun + file)
}

public function importPivotStore(Request $request)
{
    $request->validate([
        'file'           => 'required|file|mimes:xlsx,xls|max:20480',
        'tahun_formasi'  => 'required',
    ]);

    $tahun = trim($request->input('tahun_formasi'));

    $sheet = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $request->file('file'))->first();
    if (!$sheet || $sheet->count() < 2) {
        return back()->withErrors(['file' => 'File kosong atau tidak terbaca.']);
    }

    // ====== DETEKSI KOLom (sama seperti punyamu) ======
    $levelsWanted = ['Pemula','Terampil','Mahir','Penyelia','Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'];
    $colIndex = ['unit'=>null, 'jabatan'=>null];
    foreach ($levelsWanted as $lvl) $colIndex[$lvl] = null;

    $scanHeaderRows = min(8, $sheet->count());
    for ($r = 0; $r < $scanHeaderRows; $r++) {
        $rowRaw = $sheet[$r] ?? [];
        if ($rowRaw instanceof \Illuminate\Support\Collection) $rowRaw = $rowRaw->toArray();
        $row = is_array($rowRaw) ? $rowRaw : [];
        foreach ($row as $cIdx => $val) {
            $txt = '';
            if (is_scalar($val))               $txt = (string)$val;
            elseif (is_object($val) && method_exists($val,'__toString')) $txt = (string)$val;
            else continue;

            $v = \Illuminate\Support\Str::of($txt)->lower()->trim();
            if ($v->contains('nama unit kerja')) $colIndex['unit'] = $cIdx;
            if ($v->contains('nama jabatan'))    $colIndex['jabatan'] = $cIdx;
            foreach ($levelsWanted as $lvl) {
                if ($v->contains(\Illuminate\Support\Str::lower($lvl))) $colIndex[$lvl] = $cIdx;
            }
        }
    }
    if ($colIndex['unit'] === null && $colIndex['jabatan'] === null) {
        $colIndex['unit']=2; $colIndex['jabatan']=4;
        $colIndex['Pemula']??=5; $colIndex['Terampil']??=6; $colIndex['Mahir']??=7; $colIndex['Penyelia']??=8;
        $colIndex['Ahli Pertama']??=9; $colIndex['Ahli Muda']??=10; $colIndex['Ahli Madya']??=11; $colIndex['Ahli Utama']??=12;
    }
    if ($colIndex['unit'] === null && $colIndex['jabatan'] === null) {
        $colIndex['unit']=1; $colIndex['jabatan']=2;
        $colIndex['Pemula']??=3; $colIndex['Terampil']??=4; $colIndex['Mahir']??=5; $colIndex['Penyelia']??=6;
        $colIndex['Ahli Pertama']??=7; $colIndex['Ahli Muda']??=8; $colIndex['Ahli Madya']??=9; $colIndex['Ahli Utama']??=10;
    }

    $inserted=0; $updated=0; $unitNotFound=[];

    DB::transaction(function() use ($sheet,$colIndex,$levelsWanted,$tahun,&$inserted,&$updated,&$unitNotFound) {

        $currentUnit = null;

        // Per-unit context untuk kontrol snapshot/replace/remap
        $ctx = []; 
        // Struktur:
        // $ctx[$unitId] = [
        //    'mode'      => 'same'|'cross',
        //    'fromYear'  => string|null,   // tahun sumber utk remap (cross) atau sama (same)
        //    'touched'   => [ 'nama|jenjang_id' => true, ... ]  // key yang muncul di file
        // ];

        for ($r = 1; $r < $sheet->count(); $r++) {
            $rowRaw = $sheet[$r] ?? [];
            if ($rowRaw instanceof \Illuminate\Support\Collection) $rowRaw = $rowRaw->toArray();
            $row = is_array($rowRaw) ? $rowRaw : [];

            $unitCell = $row[$colIndex['unit']] ?? null;
            $unitTxt  = is_scalar($unitCell) ? trim((string)$unitCell) : '';
            if ($unitTxt !== '') $currentUnit = $unitTxt;
            if (!$currentUnit) continue;

            $jabCell = $row[$colIndex['jabatan']] ?? null;
            $jabatan = is_scalar($jabCell) ? trim((string)$jabCell) : '';
            if ($jabatan === '' || \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($jabatan),'nama jabatan')) continue;

            // resolve unit
            $unit = \App\Models\Rumahsakit::where('nama_rumahsakit',$currentUnit)->first(['no_rs','nama_rumahsakit']);
            if (!$unit) { $unitNotFound[$currentUnit] = true; continue; }

            // Siapkan context per unit (sekali di awal saat unit pertama kali ditemui)
            if (!isset($ctx[$unit->no_rs])) {
                $lastYear = \App\Models\Formasijabatan::where('unit_kerja_id',$unit->no_rs)->max('tahun_formasi');

                // 1) Selalu snapshot set lama (jika ada)
                if ($lastYear) {
                    // jika lastYear == $tahun, snapshot kondisi sebelum replace (same-year)
                    // jika lastYear != $tahun, snapshot tahun lama (cross-year)
                    $this->snapshotFormasiSet($unit->no_rs, $lastYear);
                }

                // 2) Tentukan mode
                $mode = ($lastYear && $lastYear != $tahun) ? 'cross' : 'same';

                // 3) Jangan hapus set tahun saat ini (same-year) sebelum upsert — biar SDM tidak hilang ID formasinya.
                //    Untuk cross-year: boleh kosongkan dulu set target tahun agar benar2 replace.
                if ($mode === 'cross') {
                    \App\Models\Formasijabatan::where('unit_kerja_id',$unit->no_rs)
                        ->where('tahun_formasi',$tahun)
                        ->delete();
                }

                $ctx[$unit->no_rs] = [
                    'mode'     => $mode,
                    'fromYear' => $lastYear ?? null, // bisa sama/tidak sama dg $tahun
                    'touched'  => [],
                ];
            }

            // Proses kolom level
            foreach ($levelsWanted as $lvl) {
                $idx = $colIndex[$lvl];
                if ($idx === null) continue;
                $val = $row[$idx] ?? null;
                $kuota = (is_numeric($val) ? (int)$val : 0);
                if ($kuota <= 0) continue;

                $kategori = $this->kategoriByLevel($lvl);
                $fullName = $jabatan.' '.$lvl;

                $jenjang = \App\Models\Jenjangjabatan::firstOrCreate(
                    ['nama_jenjang' => $fullName],
                    ['kategori' => $kategori]
                );

                $key = $jabatan.'|'.$jenjang->id;
                $ctx[$unit->no_rs]['touched'][$key] = true;

                // Upsert untuk tahun yang diimport
                $existing = \App\Models\Formasijabatan::where('unit_kerja_id', $unit->no_rs)
                    ->where('tahun_formasi', $tahun)
                    ->where('nama_formasi', $jabatan)
                    ->where('jenjang_id', $jenjang->id)
                    ->first();

                if ($existing) {
                    // SAME-YEAR → update in-place (SDM tetap nempel)
                    $existing->update(['kuota' => $kuota]);
                    $updated++;
                } else {
                    \App\Models\Formasijabatan::create([
                        'unit_kerja_id' => $unit->no_rs,
                        'tahun_formasi' => $tahun,
                        'nama_formasi'  => $jabatan,
                        'jenjang_id'    => $jenjang->id,
                        'kuota'         => $kuota,
                    ]);
                    $inserted++;
                }
            }
        }

        // ====== POST-PROCESS PER UNIT ======
        foreach ($ctx as $unitId => $info) {
            $mode     = $info['mode'];
            $fromYear = $info['fromYear'] ?? null;
            $touched  = array_keys($info['touched'] ?? []);

            if ($mode === 'cross' && $fromYear) {
                // CROSS-YEAR:
                // 1) remap SDM dari tahun lama -> tahun impor
                $this->remapSdmToNewFormasi($unitId, $fromYear, $tahun);
                // 2) hapus set lama
                \App\Models\Formasijabatan::where('unit_kerja_id',$unitId)
                    ->where('tahun_formasi',$fromYear)
                    ->delete();
            } else {
                // SAME-YEAR:
                // Hapus formasi yg TIDAK ada di file baru (replace set sepenuhnya).
                // Kumpulkan key "nama|jenjang_id" untuk semua formasi tahun ini
                $allThisYear = \App\Models\Formasijabatan::where('unit_kerja_id',$unitId)
                    ->where('tahun_formasi',$tahun)
                    ->get(['id','nama_formasi','jenjang_id']);

                $toDeleteIds = $allThisYear->filter(function($f) use ($touched){
                    $key = $f->nama_formasi.'|'.$f->jenjang_id;
                    return !in_array($key, $touched, true);
                })->pluck('id')->all();

                if (!empty($toDeleteIds)) {
                    // Lepaskan SDM dari formasi yang akan dihapus (unit_kerja_id tetap diisi)
                    \App\Models\Sdmmodels::whereIn('formasi_jabatan_id', $toDeleteIds)
                        ->update([
                            'formasi_jabatan_id' => null,
                            'unit_kerja_id'      => $unitId,
                        ]);

                    // Hapus formasinya
                    \App\Models\Formasijabatan::whereIn('id', $toDeleteIds)->delete();
                }
                // Catatan: karena upsert in-place, SDM yang menempel di formasi yang masih ada akan tetap aman.
            }
        }
    });

    $msg = "Import selesai. Insert: {$inserted}, Update: {$updated}.";
    if (!empty($unitNotFound)) {
        $msg .= " (Unit tidak ditemukan: ".implode('; ', array_keys($unitNotFound)).")";
    }

    return redirect()->route('user.formasi.index')->with('success', $msg);
}


/**
 * Remap SDM dari set formasi lama (fromYear) ke set formasi baru (toYear) untuk unit yang sama.
 * Kunci mapping: (nama_formasi, jenjang_id).
 */
protected function remapSdmToNewFormasi(int $unitId, string $fromYear, string $toYear): void
{
    // Index formasi lama: key = "nama|jenjang_id" => old_id
    $old = \App\Models\Formasijabatan::where('unit_kerja_id', $unitId)
        ->where('tahun_formasi', $fromYear)
        ->get(['id','nama_formasi','jenjang_id'])
        ->keyBy(fn($f) => $f->nama_formasi.'|'.$f->jenjang_id);

    if ($old->isEmpty()) return;

    // Index formasi baru: key = "nama|jenjang_id" => row baru (kuota untuk kontrol)
    $new = \App\Models\Formasijabatan::where('unit_kerja_id', $unitId)
        ->where('tahun_formasi', $toYear)
        ->withCount(['sdmAktif as terisi'])
        ->get(['id','nama_formasi','jenjang_id','kuota'])
        ->keyBy(fn($f) => $f->nama_formasi.'|'.$f->jenjang_id);

    foreach ($old as $key => $oldForm) {
        // Ambil semua SDM (urut paling lama masuk dulu)
        $allIds = \App\Models\Sdmmodels::where('formasi_jabatan_id', $oldForm->id)
            ->orderByRaw('COALESCE(tmt_pengangkatan, created_at) ASC') // pastikan ASC lengkap
            ->pluck('id')
            ->all();

        if (!$new->has($key)) {
            // Tidak ada padanan di set baru → lepas semua ke null, unit tetap diisi
            if (!empty($allIds)) {
                \App\Models\Sdmmodels::whereIn('id', $allIds)->update([
                    'formasi_jabatan_id' => null,
                    'unit_kerja_id'      => $unitId,
                ]);
            }
            continue;
        }

        $newForm   = $new[$key];
        $kuotaBaru = (int)($newForm->kuota ?? 0);

        if ($kuotaBaru <= 0) {
            // Kuota 0 → semua dilepas
            if (!empty($allIds)) {
                \App\Models\Sdmmodels::whereIn('id', $allIds)->update([
                    'formasi_jabatan_id' => null,
                    'unit_kerja_id'      => $unitId,
                ]);
            }
            continue;
        }

        // Bagi ke "masuk" dan "sisa" tanpa OFFSET
        $idsMasuk = array_slice($allIds, 0, $kuotaBaru);
        $idsSisa  = array_slice($allIds, $kuotaBaru);

        if (!empty($idsMasuk)) {
            \App\Models\Sdmmodels::whereIn('id', $idsMasuk)->update([
                'formasi_jabatan_id' => $newForm->id,
                'unit_kerja_id'      => $unitId, // redundan tapi aman
            ]);
        }
        if (!empty($idsSisa)) {
            \App\Models\Sdmmodels::whereIn('id', $idsSisa)->update([
                'formasi_jabatan_id' => null,
                'unit_kerja_id'      => $unitId,
            ]);
        }
    }
}


private function kategoriByLevel(string $level): string
{
    $s = Str::lower($level);
    return Str::contains($s, ['pemula','terampil','mahir','penyelia']) ? 'Terampil' : 'Ahli';
}

// ...

/**
 * Simpan snapshot semua formasi untuk (unit, tahun) ke tabel histori.
 */
private function snapshotFormasiSet(int $unitId, string $tahun): void
{
    $rows = Formasijabatan::withCount(['sdmAktif as terisi'])
        ->where('unit_kerja_id', $unitId)
        ->where('tahun_formasi', $tahun)
        ->get();

    if ($rows->isEmpty()) return;

    $payload = $rows->map(function ($f) {
        return [
            'formasi_id'    => $f->id,
            'unit_kerja_id' => $f->unit_kerja_id,
            'tahun_formasi' => $f->tahun_formasi,
            'nama_formasi'  => $f->nama_formasi,
            'jenjang_id'    => $f->jenjang_id,
            'kuota'         => (int) $f->kuota,
            'terisi'        => (int) ($f->terisi ?? 0), // sudah withCount
            'snapshot_at'   => now(),
        ];
    })->all();

    FormasiJabatanHistori::insert($payload);
}

public function history(Request $req)
{
    $unitId = $req->query('unit_kerja_id');
    $tahun  = $req->query('tahun');

    $q = \App\Models\FormasiJabatanHistori::query()
        ->with(['unitKerja.regency.province','jenjang'])
        ->orderByDesc('snapshot_at');

    if ($unitId) $q->where('unit_kerja_id', $unitId);
    if ($tahun)  $q->where('tahun_formasi', $tahun);

    $hist = $q->paginate(20);

    $units  = \App\Models\Rumahsakit::orderBy('nama_rumahsakit')
                ->get(['no_rs','nama_rumahsakit']);
    $tahuns = \App\Models\Formasijabatan::select('tahun_formasi')
                ->distinct()->orderBy('tahun_formasi')->pluck('tahun_formasi');

    return view('formasi_jabatan.history', compact('hist','units','tahuns','unitId','tahun'));
}



}
