<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BankSoal;
use App\Models\BankSoalPilihan;
use App\Models\BankSoalImportLog;
use App\Models\SoalKategori;
use App\Imports\BankSoalImport;
use App\Exports\BankSoalTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class BankSoalController extends Controller
{
    public function index(Request $request)
    {
        $query = BankSoal::with(['kategori', 'pembuat'])->orderByDesc('created_at');

        if ($request->filled('kategori_id')) {
            $query->where('soal_kategori_id', $request->kategori_id);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('matra')) {
            $query->where('matra', $request->matra);
        }
        if ($request->filled('taksonomi')) {
            $query->where('taksonomi_bloom', $request->taksonomi);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $soals      = $query->paginate(20)->withQueryString();
        $kategoris  = SoalKategori::aktif()->orderBy('nama')->get();
        $statistik  = [
            'total'    => BankSoal::count(),
            'aktif'    => BankSoal::where('status', 'aktif')->count(),
            'draft'    => BankSoal::where('status', 'draft')->count(),
            'nonaktif' => BankSoal::where('status', 'nonaktif')->count(),
        ];
        $mansoskulBelumLengkap = BankSoal::where('jenis', 'mansoskul')->whereNull('matra')->count();

        return view('bank_soal.index', compact('soals', 'kategoris', 'statistik', 'mansoskulBelumLengkap'));
    }

    public function create()
    {
        $kategoris = SoalKategori::aktif()->orderBy('nama')->get();
        return view('bank_soal.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis'                 => 'required|in:mansoskul,teknis',
            'soal_kategori_id'      => 'nullable|exists:soal_kategori,id',
            'matra'                 => 'nullable|in:darat,laut,udara,asdp,perkeretaapian',
            'taksonomi_bloom'       => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'pertanyaan'            => 'required|string',
            'pembahasan'            => 'nullable|string',
            'pilihan'               => 'required|array|size:4',
            'pilihan.*.teks'        => 'required|string',
            'jawaban_benar'         => 'required_if:jenis,teknis|nullable|in:A,B,C,D',
            'pilihan.*.nilai_skala' => 'required_if:jenis,mansoskul|nullable|integer|min:1|max:5',
            'simpan_sebagai'        => 'required|in:draft,aktif',
        ]);

        if ($request->jenis === 'teknis' && !$request->soal_kategori_id) {
            return back()->withInput()->withErrors(['soal_kategori_id' => 'Kategori wajib diisi untuk soal Teknis.']);
        }
        if ($request->jenis === 'mansoskul' && !$request->matra) {
            return back()->withInput()->withErrors(['matra' => 'Matra wajib diisi untuk soal Mansoskul.']);
        }

        $status = $request->simpan_sebagai === 'aktif' ? 'aktif' : 'draft';

        $soal = BankSoal::create([
            'soal_kategori_id'  => $request->jenis === 'teknis' ? $request->soal_kategori_id : null,
            'matra'             => $request->jenis === 'mansoskul' ? $request->matra : null,
            'pertanyaan'        => $request->pertanyaan,
            'pembahasan'        => $request->pembahasan,
            'taksonomi_bloom'   => $request->taksonomi_bloom,
            'jenis'             => $request->jenis,
            'status'            => $status,
            'dibuat_oleh'       => Auth::id(),
            'disetujui_oleh'    => $status === 'aktif' ? Auth::id() : null,
            'tanggal_disetujui' => $status === 'aktif' ? now() : null,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $kode) {
            BankSoalPilihan::create([
                'bank_soal_id' => $soal->id,
                'kode_pilihan' => $kode,
                'teks_pilihan' => $request->pilihan[$kode]['teks'],
                'is_benar'     => $request->jenis === 'teknis' && $request->jawaban_benar === $kode,
                'nilai_skala'  => $request->jenis === 'mansoskul' ? (int) $request->pilihan[$kode]['nilai_skala'] : null,
            ]);
        }

        return redirect()->route('bank-soal.show', $soal->id)
            ->with('success', 'Soal berhasil disimpan sebagai ' . ($status === 'aktif' ? 'Aktif' : 'Draft') . '.');
    }

    public function show($id)
    {
        $soal = BankSoal::with(['kategori', 'pilihan', 'pembuat', 'penyetuju'])->findOrFail($id);
        return view('bank_soal.show', compact('soal'));
    }

    public function edit($id)
    {
        $soal      = BankSoal::with('pilihan')->findOrFail($id);
        $kategoris = SoalKategori::aktif()->orderBy('nama')->get();
        return view('bank_soal.edit', compact('soal', 'kategoris'));
    }

    public function update(Request $request, $id)
    {
        $soal = BankSoal::findOrFail($id);

        $request->validate([
            'jenis'                 => 'required|in:mansoskul,teknis',
            'soal_kategori_id'      => 'nullable|exists:soal_kategori,id',
            'matra'                 => 'nullable|in:darat,laut,udara,asdp,perkeretaapian',
            'taksonomi_bloom'       => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'pertanyaan'            => 'required|string',
            'pembahasan'            => 'nullable|string',
            'pilihan'               => 'required|array|size:4',
            'pilihan.*.teks'        => 'required|string',
            'jawaban_benar'         => 'required_if:jenis,teknis|nullable|in:A,B,C,D',
            'pilihan.*.nilai_skala' => 'required_if:jenis,mansoskul|nullable|integer|min:1|max:5',
            'status'                => 'required|in:draft,aktif,nonaktif',
        ]);

        if ($request->jenis === 'teknis' && !$request->soal_kategori_id) {
            return back()->withInput()->withErrors(['soal_kategori_id' => 'Kategori wajib diisi untuk soal Teknis.']);
        }
        if ($request->jenis === 'mansoskul' && !$request->matra) {
            return back()->withInput()->withErrors(['matra' => 'Matra wajib diisi untuk soal Mansoskul.']);
        }

        $soal->update([
            'soal_kategori_id'  => $request->jenis === 'teknis' ? $request->soal_kategori_id : null,
            'matra'             => $request->jenis === 'mansoskul' ? $request->matra : null,
            'pertanyaan'        => $request->pertanyaan,
            'pembahasan'        => $request->pembahasan,
            'taksonomi_bloom'   => $request->taksonomi_bloom,
            'jenis'             => $request->jenis,
            'status'            => $request->status,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $kode) {
            BankSoalPilihan::where('bank_soal_id', $soal->id)
                ->where('kode_pilihan', $kode)
                ->update([
                    'teks_pilihan' => $request->pilihan[$kode]['teks'],
                    'is_benar'     => $request->jenis === 'teknis' && $request->jawaban_benar === $kode,
                    'nilai_skala'  => $request->jenis === 'mansoskul' ? (int) $request->pilihan[$kode]['nilai_skala'] : null,
                ]);
        }

        return redirect()->route('bank-soal.show', $soal->id)->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $soal = BankSoal::findOrFail($id);
        $soal->delete();
        return redirect()->route('bank-soal.index')->with('success', 'Soal berhasil dihapus.');
    }

    public function approve($id)
    {
        $soal = BankSoal::findOrFail($id);

        if (!in_array($soal->status, ['draft', 'nonaktif'])) {
            return back()->with('error', 'Hanya soal berstatus Draft atau Nonaktif yang dapat diaktifkan.');
        }

        $soal->update([
            'status'            => 'aktif',
            'disetujui_oleh'    => Auth::id(),
            'tanggal_disetujui' => now(),
        ]);

        return back()->with('success', 'Soal berhasil diaktifkan.');
    }

    public function nonaktifkan($id)
    {
        $soal = BankSoal::findOrFail($id);

        if ($soal->status !== 'aktif') {
            return back()->with('error', 'Hanya soal berstatus Aktif yang dapat dinonaktifkan.');
        }

        $soal->update(['status' => 'nonaktif']);
        return back()->with('success', 'Soal berhasil dinonaktifkan.');
    }

    public function getByKategori(Request $request)
    {
        $kategoriId = $request->kategori_id;
        $soals = BankSoal::with('pilihan')
            ->aktif()
            ->where(function ($q) use ($kategoriId) {
                $q->where('jenis', 'mansoskul')
                  ->orWhere(function ($q2) use ($kategoriId) {
                      $q2->where('jenis', 'teknis')->where('soal_kategori_id', $kategoriId);
                  });
            })
            ->get(['id', 'pertanyaan', 'matra', 'taksonomi_bloom', 'jenis']);

        return response()->json($soals);
    }

    // ─── Import ───────────────────────────────────────────────────────────────

    public function importPage()
    {
        $logs = BankSoalImportLog::with('pengimport')
            ->orderByDesc('created_at')
            ->paginate(10);
        return view('bank_soal.import', compact('logs'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        $namaFile = $request->file('file')->getClientOriginalName();
        $import   = new BankSoalImport($namaFile);

        Excel::import($import, $request->file('file'));

        $pesan = "Import selesai: {$import->berhasil} soal berhasil diimport" .
                 ($import->gagal > 0 ? ", {$import->gagal} baris gagal (lihat detail error)." : '.');

        return redirect()->route('bank-soal.import')
            ->with($import->gagal > 0 ? 'warning' : 'success', $pesan);
    }

    public function downloadTemplate()
    {
        return Excel::download(new BankSoalTemplateExport(), 'template_bank_soal.xlsx');
    }

    public function lihatDetailError($id)
    {
        $log = BankSoalImportLog::findOrFail($id);
        return response()->json([
            'nama_file'    => $log->nama_file,
            'total_baris'  => $log->total_baris,
            'berhasil'     => $log->berhasil,
            'gagal'        => $log->gagal,
            'detail_gagal' => $log->detail_gagal ?? [],
        ]);
    }
}
