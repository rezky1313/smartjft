<?php

namespace App\Http\Controllers;

use App\Models\Sdmmodels;
use App\Models\Formasijabatan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Rumahsakit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;



class SdmController extends Controller
{

    public function index()
{

    $sdm = Sdmmodels::with([
    'formasi.jenjang',
    'formasi.unitKerja.regency.province',
    'unitKerja.regency.province', // <— penting untuk SDM tanpa formasi
])->orderByDesc('created_at')->get();

    return view('sdm.index', compact('sdm'));
}


    public function create()
    {
        $formasi = Formasijabatan::with(['jenjang','unitKerja'])
        ->withCount(['sdmAktif as terisi'])
        ->select('id','jenjang_id','unit_kerja_id','kuota')
        ->orderBy('unit_kerja_id')
        ->orderBy('jenjang_id')
        ->get();
        $unitkerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs','nama_rumahsakit']);
        return view('sdm.create', compact('formasi','unitkerja'));
    }

    public function store(Request $r)
{
    $validated = $r->validate([
        'nip'                 => 'nullable|string|max:50',
        'nik'                 => 'nullable|string|max:50',
        'nama_lengkap'        => 'required|string|max:150',
        'jenis_kelamin'       => 'nullable|in:L,P',
        'pendidikan_terakhir' => 'nullable|string|max:120',
        'pangkat_golongan'    => 'nullable|string|max:50',
        'status_kepegawaian'  => 'required|in:PNS,PPPK,CPNS,Non ASN',
        'tmt_pengangkatan'    => 'nullable|date',

        // salah satu wajib terisi
        'formasi_jabatan_id'  => 'nullable|exists:formasi_jabatan,id',
        'unit_kerja_id'       => 'required_without:formasi_jabatan_id|nullable|exists:rumahsakits,no_rs',

        'aktif'               => 'nullable|boolean',
    ]);

    // Tentukan unit_kerja_id final
    $unitKerjaId = $validated['unit_kerja_id'] ?? null;

    if (!empty($validated['formasi_jabatan_id'])) {
        // Jika memilih formasi, ikut unit kerja dari formasi tsb
        $unitKerjaId = Formasijabatan::whereKey($validated['formasi_jabatan_id'])->value('unit_kerja_id');
    }

    Sdmmodels::create([
        'nip'                 => $validated['nip'] ?? null,
        'nik'                 => $validated['nik'] ?? null,
        'nama_lengkap'        => $validated['nama_lengkap'],
        'jenis_kelamin'       => $validated['jenis_kelamin'] ?? null,
        'pendidikan_terakhir' => $validated['pendidikan_terakhir'] ?? null,
        'pangkat_golongan'    => $validated['pangkat_golongan'] ?? null,
        'status_kepegawaian'  => $validated['status_kepegawaian'],
        'formasi_jabatan_id'  => $validated['formasi_jabatan_id'] ?? null,
        'unit_kerja_id'       => $unitKerjaId,                     // <-- pakai variabel yang sudah dihitung
        'tmt_pengangkatan'    => $validated['tmt_pengangkatan'] ?? null,
        'aktif'               => (bool)($validated['aktif'] ?? true),
    ]);

    return redirect()->route('user.sdm.index')->with('success','SDM berhasil ditambahkan.');
}

    public function edit(Sdmmodels $sdm)
    {
       // $formasi = Formasijabatan::select('id','nama_formasi','jenjang_id')->orderBy('nama_formasi')->get();
      $formasi = Formasijabatan::with(['jenjang','unitKerja'])
        ->withCount(['sdmAktif as terisi'])
        ->select('id','jenjang_id','unit_kerja_id','kuota')
        ->orderBy('unit_kerja_id')
        ->orderBy('jenjang_id')
        ->get();
        $unitkerja = Rumahsakit::orderBy('nama_rumahsakit')->get(['no_rs','nama_rumahsakit']);
        return view('sdm.edit', compact('sdm','formasi','unitkerja'));
    }


    public function update(Request $r, Sdmmodels $sdm)
{
    $validated = $r->validate([
        'nama_lengkap'        => 'required|string|max:150',
        'jenis_kelamin'       => 'nullable|in:L,P',
        'pendidikan_terakhir' => 'nullable|string|max:120',
        'pangkat_golongan'    => 'nullable|string|max:50',
        'status_kepegawaian'  => 'required|in:PNS,PPPK,CPNS,Non ASN',
        'tmt_pengangkatan'    => 'nullable|date',
        'formasi_jabatan_id'  => 'nullable|exists:formasi_jabatan,id',
        'unit_kerja_id'       => 'required_without:formasi_jabatan_id|nullable|exists:rumahsakits,no_rs',
        'aktif'               => 'nullable|boolean',
    ]);

    $unitKerjaId = $validated['unit_kerja_id'] ?? null;
    if (!empty($validated['formasi_jabatan_id'])) {
        $unitKerjaId = Formasijabatan::whereKey($validated['formasi_jabatan_id'])->value('unit_kerja_id');
    }

    $sdm->update([
        'nip'                 => $r->nip,
        'nik'                 => $r->nik,
        'nama_lengkap'        => $validated['nama_lengkap'],
        'jenis_kelamin'       => $validated['jenis_kelamin']?? null,
        'pendidikan_terakhir' => $validated['pendidikan_terakhir'] ?? null,
        'pangkat_golongan'    => $validated['pangkat_golongan'] ?? null,
        'status_kepegawaian'  => $validated['status_kepegawaian'],
        'formasi_jabatan_id'  => $validated['formasi_jabatan_id'] ?? null,
        'unit_kerja_id'       => $unitKerjaId,
        'tmt_pengangkatan'    => $validated['tmt_pengangkatan'] ?? null,
        'aktif'               => (bool)($validated['aktif'] ?? $sdm->aktif),
    ]);

    return redirect()->route('user.sdm.index')->with('success','SDM berhasil diperbarui.');
}

    public function destroy(Sdmmodels $sdm)
    {
        $sdm->delete();
        return redirect()->route('user.sdm.index')->with('success','SDM berhasil dihapus.');
    }

    public function trash()
{
    $sdm = Sdmmodels::onlyTrashed()
        ->with(['formasi.jenjang','formasi.unitKerja.regency.province','unitKerja.regency.province'])
        ->orderBy('nama_lengkap')
        ->get();

    return view('sdm.trash', compact('sdm')); // buat view sederhana
}

public function restore($id)
{
    $sdm = Sdmmodels::withTrashed()->findOrFail($id);
    $sdm->restore(); // kembalikan dari soft delete
    return back()->with('success','SDM berhasil direstore.');
}

public function forceDelete($id)
{
    $sdm = Sdmmodels::withTrashed()->findOrFail($id);
    $sdm->forceDelete(); // hapus permanen
    return back()->with('success','SDM dihapus permanen.');
}

public function importForm()
{
    return view('sdm.import');
}

// Ekstrak level di akhir teks formasi (opsional)
private function splitFormasiAndLevel(string $text): array
{
    $levels = [
        'Ahli Pertama','Ahli Madya','Ahli Muda','Ahli Utama',
        'Penyelia','Terampil','Mahir','Pemula',
    ];
    $t = \Illuminate\Support\Str::of($text)->trim();

    // urutkan dari yang terpanjang agar "Ahli Utama" tidak ketiban "Ahli"
    usort($levels, fn($a,$b)=> strlen($b) <=> strlen($a));

    foreach ($levels as $L) {
        if (\Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::upper($t), \Illuminate\Support\Str::upper(' '.$L))) {
            $nama = trim(substr($t, 0, -strlen($L)));
            $nama = rtrim($nama);
            return ['nama' => $nama, 'level' => $L];
        }
    }
    return ['nama' => (string)$t, 'level' => null];
}

// Normalisasi status kepegawaian dari berbagai variasi
private function normalizeStatusKepeg(?string $raw): string
{
    $s = \Illuminate\Support\Str::of((string)$raw)->upper()->trim()->toString();
    if ($s === '') return 'PNS';
    if (in_array($s, ['PNS','PPPK','CPNS'], true)) return $s;

    // variasi Non ASN
    $aliases = ['NON ASN','NON-ASN','NONASN','NON PNS','NONPNS','HONORER','THL'];
    if (in_array($s, $aliases, true)) return 'Non ASN';

    return 'PNS';
}

// Pencocokan nama unit kerja → row rumahsakits (boleh null)
private function resolveUnitByName(string $name): ?\App\Models\Rumahsakit
{
    $norm = fn($s)=> \Illuminate\Support\Str::of((string)$s)->lower()->replaceMatches('/\s+/', ' ')->trim()->toString();
    $target = $norm($name);

    // exact normalize match
    $row = \App\Models\Rumahsakit::get(['no_rs','nama_rumahsakit'])->first(function($u) use ($norm,$target) {
        return $norm($u->nama_rumahsakit) === $target;
    });
    if ($row) return $row;

    // contains match (fallback)
    return \App\Models\Rumahsakit::get(['no_rs','nama_rumahsakit'])->first(function($u) use ($norm,$target) {
        return \Illuminate\Support\Str::contains($norm($u->nama_rumahsakit), $target);
    });
}


private function normalizeJK(?string $raw): ?string
{
    $s = \Illuminate\Support\Str::of((string)$raw)
            ->replace(["\u{00A0}", "\u{200B}"], ' ') // non-breaking/zero-width space
            ->lower()->trim()->toString();

    if ($s === '') return null;

    // Ambil huruf pertama yang alphabet saja (menghapus angka/spasi/tanda baca)
    $first = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $s), 0, 1));

    // Terima beberapa variasi umum
    if (in_array($first, ['L','P'], true)) return $first;

    // Kata kunci lengkap
    if (str_contains($s, 'laki')) return 'L';
    if (str_contains($s, 'perempuan') || str_contains($s, 'wanita')) return 'P';

    return null; // tidak dikenali
}


public function importStore(Request $request)
{
    $request->validate([
        'file'          => 'required|file|mimes:xlsx,xls,csv|max:20480',
        'default_aktif' => 'nullable|in:0,1',
    ]);

    $defaultAktif = (int)$request->input('default_aktif', 1);

    // Baca sheet pertama langsung dari memory (tidak menyimpan file ke storage)
    $sheet = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $request->file('file'))->first();
    if (!$sheet || $sheet->count() < 2) {
        return back()->withErrors(['file' => 'File kosong atau tidak terbaca.']);
    }


// Normalisasi header (baris pertama)
$firstRow = $sheet[0];
if ($firstRow instanceof \Illuminate\Support\Collection) {
    $firstRow = $firstRow->toArray();
}

$header = collect($firstRow)->map(
    fn($h) => \Illuminate\Support\Str::of((string)($h ?? ''))->lower()->trim()->toString()
);




    // Helper cari index kolom dengan banyak alias header
    $findIdx = function (array $aliases) use ($header) {
        foreach ($aliases as $a) {
            $i = $header->search(\Illuminate\Support\Str::of($a)->lower()->trim()->toString());
            if ($i !== false) return $i;
        }
        return false;
    };

    // Pemetaan indeks kolom (tanpa "tahun" & "level")
    $idx = [
        'nip'                 => $findIdx(['nip']),
        'nik'                 => $findIdx(['nik']),
        'nama_lengkap'        => $findIdx(['nama_lengkap','nama','nama pegawai','nama lengkap']),
        'jenis_kelamin'       => $findIdx(['jenis_kelamin','jk','gender']),
        'pendidikan_terakhir' => $findIdx(['pendidikan_terakhir','pendidikan','pend terakhir']),
        'pangkat_golongan'    => $findIdx(['pangkat_golongan','pangkat','golongan','pangkat/golongan']),
        'status_kepegawaian'  => $findIdx(['status_kepegawaian','status','status pegawai']),
        'aktif'               => $findIdx(['aktif','status_aktif']),
        'nama_formasi'        => $findIdx(['formasi jabatan','nama_formasi','formasi','jabatan']),
        'unit_name'           => $findIdx(['unit kerja','unit_name','unit','nama unit kerja','instansi','nama unit']),
        'tmt_pengangkatan'    => $findIdx(['tmt pengangkatan','tmt_pengangkatan','tmt']),
    ];

    // Kolom wajib minimal
    $wajib = [
        'nama_lengkap' => "Nama Lengkap",
        'nama_formasi' => "Formasi Jabatan",
        'unit_name'    => "Unit Kerja",
    ];
    foreach ($wajib as $k => $label) {
        if ($idx[$k] === false) {
            return back()->withErrors(['file' => "Kolom wajib '{$label}' tidak ditemukan pada header."]);
        }
    }

    $inserted = 0;
    $updated  = 0;
    $missUnit = [];
    $missForm = [];
    $badEnum  = [];

    \Illuminate\Support\Facades\DB::transaction(function () use (
        $sheet, $idx, $defaultAktif, &$inserted, &$updated, &$missUnit, &$missForm, &$badEnum
    ) {
        $norm = fn($s)=> \Illuminate\Support\Str::of((string)$s)->lower()->replaceMatches('/\s+/', ' ')->trim()->toString();

        // Mulai dari baris data ke-2 (index 1)
        for ($r = 1; $r < $sheet->count(); $r++) {
            $rowRaw = $sheet[$r] ?? [];
            if ($rowRaw instanceof \Illuminate\Support\Collection) $rowRaw = $rowRaw->toArray();
            $row = is_array($rowRaw) ? $rowRaw : [];

            // Getter aman — bila kolom tak ada, kembalikan string kosong
            $get = function (string $key) use ($idx, $row) {
                if (!array_key_exists($key, $idx)) return '';
                $i = $idx[$key];
                return $i === false ? '' : trim((string)($row[$i] ?? ''));
            };

            $nip   = $get('nip');
            $nik   = $get('nik');
            $nama  = $get('nama_lengkap');
          $jkRaw = $get('jenis_kelamin');
$jk    = $this->normalizeJK($jkRaw); // hasilnya 'L', 'P', atau null
            $pend  = $get('pendidikan_terakhir');        // opsional
            $pangkat = $get('pangkat_golongan');         // opsional
            $status  = $this->normalizeStatusKepeg($get('status_kepegawaian')); // default PNS jika kosong/tidak dikenal
            $aktif   = $idx['aktif'] !== false ? (int)$get('aktif') : $defaultAktif;

            $formasiNm = $get('nama_formasi');           // wajib
            $unitName  = $get('unit_name');              // wajib (tp kalau tidak match, bisa fallback dari formasi)
            $tmt       = $get('tmt_pengangkatan');       // opsional

            // Minimal butuh Nama + Formasi (unit bisa fallback dari formasi)
            if ($nama === '' || $formasiNm === '') {
                continue;
            }
            if ($jkRaw !== '' && $jk === null) {
    $badEnum["JK:{$jkRaw}"] = true; // catat yang tak dikenal
}
            if (!in_array($status, ['PNS','PPPK','CPNS','Non ASN'], true)) {
                $badEnum["Status:{$status}"] = true; $status = 'PNS';
            }

            // 1) Resolve unit dari nama (bisa tidak ketemu)
            $unitRow = $unitName !== '' ? $this->resolveUnitByName($unitName) : null;
            if (!$unitRow && $unitName !== '') { $missUnit[$unitName] = true; }

            // 2) Pecah "Formasi + Level" jika excel menulis level di belakang
            $parsed   = $this->splitFormasiAndLevel($formasiNm);
            $baseNama = $parsed['nama'];   // inti nama formasi
            $levelTxt = $parsed['level'];  // "Mahir"/"Ahli Madya"/... atau null

            // 3) Cari formasi:
            $q = \App\Models\Formasijabatan::query()
                ->when($unitRow, fn($qq)=>$qq->where('unit_kerja_id', $unitRow->no_rs))
                ->where(function($qq) use ($baseNama, $formasiNm) {
                    $qq->whereRaw('LOWER(nama_formasi) = ?', [mb_strtolower($baseNama)])
                       ->orWhereRaw('LOWER(nama_formasi) = ?', [mb_strtolower($formasiNm)])
                       ->orWhereRaw('LOWER(nama_formasi) LIKE ?', ['%'.mb_strtolower($baseNama).'%']);
                })
                ->with('jenjang');

            $formasi = null;
            if ($levelTxt) {
                $formasi = (clone $q)
                    ->whereHas('jenjang', function($j) use ($levelTxt) {
                        $j->whereRaw('LOWER(nama_jenjang) LIKE ?', ['%'.mb_strtolower($levelTxt).'%']);
                    })
                    ->orderByDesc('tahun_formasi')
                    ->first();
            }
            if (!$formasi) {
                // fallback: tanpa filter jenjang → ambil yang terbaru
                $formasi = (clone $q)->orderByDesc('tahun_formasi')->first();
            }

            if (!$formasi && !$unitRow) {
                // benar-benar tidak bisa ditentukan
                $missForm[ ($unitName ?: '-') .'|'. $formasiNm ] = true;
                continue;
            }

            // 4) Tentukan unit final
            $finalUnitId = $formasi ? $formasi->unit_kerja_id : ($unitRow ? $unitRow->no_rs : null);
            if (!$formasi) {
                // formasi belum tersedia meski unit ada
                $missForm[ ($unitName ?: '-') .'|'. $formasiNm ] = true;
            }

            // Payload siap simpan
            $payload = [
                'nama_lengkap'        => $nama,
                'nik'                 => $nik ?: null,
                'jenis_kelamin'       => $jk ?: null,
                'pendidikan_terakhir' => $pend ?: null,
                'pangkat_golongan'    => $pangkat ?: null,
                'status_kepegawaian'  => $status,
                'aktif'               => $aktif ? 1 : 0,
                'formasi_jabatan_id'  => $formasi?->id,     // null jika belum ada formasi
                'unit_kerja_id'       => $finalUnitId,      // bisa ikut formasi atau hasil resolve nama unit
            ];
            if ($tmt) $payload['tmt_pengangkatan'] = $tmt;

            // Upsert berdasar NIP (jika ada)
            if ($nip !== '') {
                $sdm = \App\Models\Sdmmodels::where('nip', $nip)->first();
                if ($sdm) {
                    $sdm->update(array_merge($payload, ['nip' => $nip]));
                    $updated++;
                } else {
                    \App\Models\Sdmmodels::create(array_merge($payload, ['nip' => $nip]));
                    $inserted++;
                }
            } else {
                \App\Models\Sdmmodels::create($payload);
                $inserted++;
            }
        }
    });

    $msg = "Import SDM selesai. Insert: {$inserted}, Update: {$updated}.";
    if (!empty($missUnit)) $msg .= " Unit tidak ditemukan (nama di file): ".implode('; ', array_keys($missUnit)).".";
    if (!empty($missForm)) $msg .= " Formasi belum ada / tidak cocok: ".implode(' || ', array_keys($missForm)).".";
    if (!empty($badEnum))  $msg .= " Nilai enum tak dikenal: ".implode(', ', array_keys($badEnum))." (diabaikan/default).";

    return redirect()->route('user.sdm.index')->with('success', $msg);
}



}
