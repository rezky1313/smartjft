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
        if ($request->filled('tingkat')) {
            $query->where('tingkat_kesulitan', $request->tingkat);
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

        return view('bank_soal.index', compact('soals', 'kategoris', 'statistik'));
    }

    public function create()
    {
        $kategoris = SoalKategori::aktif()->orderBy('nama')->get();
        return view('bank_soal.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis'            => 'required|in:umum,spesifik',
            'soal_kategori_id' => 'nullable|exists:soal_kategori,id',
            'tingkat_kesulitan'=> 'required|in:mudah,sedang,sulit',
            'taksonomi_bloom'  => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'pertanyaan'       => 'required|string',
            'pembahasan'       => 'nullable|string',
            'pilihan'          => 'required|array|size:4',
            'pilihan.*.teks'   => 'required|string',
            'jawaban_benar'    => 'required|in:A,B,C,D',
            'simpan_sebagai'   => 'required|in:draft,aktif',
        ]);

        $status = $request->simpan_sebagai === 'aktif' ? 'aktif' : 'draft';

        $soal = BankSoal::create([
            'soal_kategori_id'  => $request->jenis === 'spesifik' ? $request->soal_kategori_id : null,
            'pertanyaan'        => $request->pertanyaan,
            'pembahasan'        => $request->pembahasan,
            'tingkat_kesulitan' => $request->tingkat_kesulitan,
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
                'is_benar'     => $request->jawaban_benar === $kode,
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
            'jenis'            => 'required|in:umum,spesifik',
            'soal_kategori_id' => 'nullable|exists:soal_kategori,id',
            'tingkat_kesulitan'=> 'required|in:mudah,sedang,sulit',
            'taksonomi_bloom'  => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta',
            'pertanyaan'       => 'required|string',
            'pembahasan'       => 'nullable|string',
            'pilihan'          => 'required|array|size:4',
            'pilihan.*.teks'   => 'required|string',
            'jawaban_benar'    => 'required|in:A,B,C,D',
        ]);

        $soal->update([
            'soal_kategori_id'  => $request->jenis === 'spesifik' ? $request->soal_kategori_id : null,
            'pertanyaan'        => $request->pertanyaan,
            'pembahasan'        => $request->pembahasan,
            'tingkat_kesulitan' => $request->tingkat_kesulitan,
            'taksonomi_bloom'   => $request->taksonomi_bloom,
            'jenis'             => $request->jenis,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $kode) {
            BankSoalPilihan::where('bank_soal_id', $soal->id)
                ->where('kode_pilihan', $kode)
                ->update([
                    'teks_pilihan' => $request->pilihan[$kode]['teks'],
                    'is_benar'     => $request->jawaban_benar === $kode,
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

        if ($soal->status !== 'draft') {
            return back()->with('error', 'Hanya soal berstatus Draft yang dapat diaktifkan.');
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
                $q->where('jenis', 'umum')
                  ->orWhere(function ($q2) use ($kategoriId) {
                      $q2->where('jenis', 'spesifik')->where('soal_kategori_id', $kategoriId);
                  });
            })
            ->get(['id', 'pertanyaan', 'tingkat_kesulitan', 'taksonomi_bloom', 'jenis']);

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
