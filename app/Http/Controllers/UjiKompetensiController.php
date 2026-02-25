<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UjiKompetensi;   // kalau modelmu bernama lain, pakai alias: use App\Models\Ujikempetensimodels as UjiKompetensi;
use App\Models\Sdmmodels;

class UjiKompetensiController extends Controller
{
    public function index()
    {
        $uji = UjiKompetensi::with([
            'sdm.formasi.jenjang',
            'sdm.formasi.unitKerja.regency.province',
            'sdm.unitKerja.regency.province',
        ])->latest()->get();

         $trashed = \App\Models\UjiKompetensi::onlyTrashed()->count();
    return view('uji_kompetensi.index', compact('uji','trashed'));
    }

    public function create()
    {
        $opsiKompetensi = ['PT1','PT2','PT3','PT4','PT5','Perpanjangan'];
        return view('uji_kompetensi.create', compact('opsiKompetensi'));
    }

    public function store(Request $r)
    {
        $v = $r->validate([
            'sdm_id'           => 'required|exists:sumber_daya_manusia,id',
            'kompetensi'       => 'required|in:PT1,PT2,PT3,PT4,PT5,Perpanjangan',
            'nilai'            => 'nullable|numeric|min:0',
            'tanggal_uji'      => 'nullable|date',
            'nomor_sertifikat' => 'nullable|string|max:120',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        UjiKompetensi::create($v);
        return redirect()->route('user.uji.index')->with('success', 'Uji kompetensi tersimpan.');
    }

    // ====== FIX di sini
    public function edit(UjiKompetensi $uji)
    {
        $uji->load([
            'sdm.formasi.jenjang',
            'sdm.formasi.unitKerja.regency.province',
            'sdm.unitKerja.regency.province',
        ]);

        $opsiKompetensi = ['PT1','PT2','PT3','PT4','PT5','Perpanjangan'];
        return view('uji_kompetensi.edit', compact('uji','opsiKompetensi'));
    }

    public function update(Request $r, UjiKompetensi $uji)
    {
        $v = $r->validate([
            'sdm_id'           => 'required|exists:sumber_daya_manusia,id',
            'kompetensi'       => 'required|in:PT1,PT2,PT3,PT4,PT5,Perpanjangan',
            'nilai'            => 'nullable|numeric|min:0',
            'tanggal_uji'      => 'nullable|date',
            'nomor_sertifikat' => 'nullable|string|max:120',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        $uji->update($v);
        return redirect()->route('user.uji.index')->with('success', 'Uji kompetensi diperbarui.');
    }

    public function destroy(UjiKompetensi $uji)
    {
        $uji->delete();
        return redirect()->route('user.uji.index')->with('success', 'Uji kompetensi dihapus.');
    }

    // ========= AJAX =========
    public function sdmSearch(Request $r)
    {
        $q = trim($r->get('q',''));

        $sdm = Sdmmodels::with([
                'formasi.jenjang',
                'formasi.unitKerja.regency.province',
                'unitKerja.regency.province',
            ])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function($x) use ($q){
                    $x->where('nama_lengkap','like',"%{$q}%")
                      ->orWhere('nip','like',"%{$q}%");
                });
            })
            ->orderBy('nama_lengkap')->limit(20)->get();

        $results = $sdm->map(function($s){
            $uk = $s->formasi?->unitKerja ?? $s->unitKerja;
            $kab = $uk?->regency;
            $jenjang = $s->formasi?->jenjang?->nama_jenjang;

            return [
                'id'       => $s->id,
                'text'     => sprintf('%s — %s — %s — %s',
                                      $s->nama_lengkap ?? '-',
                                      $s->nip ?? '-',
                                      $uk->nama_rumahsakit ?? '-',
                                      $jenjang ?? '-'),
                'nama'     => $s->nama_lengkap,
                'nip'      => $s->nip,
                'unit'     => $uk->nama_rumahsakit ?? null,
                'jenjang'  => $jenjang,
                'kabkota'  => $kab ? ($kab->type.' '.$kab->name) : null,
                'instansi' => $uk->instansi ?? null,
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function sdmMini(Sdmmodels $sdm)
    {
        $sdm->load([
            'formasi.jenjang',
            'formasi.unitKerja.regency.province',
            'unitKerja.regency.province',
        ]);

        $uk = $sdm->formasi?->unitKerja ?? $sdm->unitKerja;
        $kab = $uk?->regency;

        return response()->json([
            'id'       => $sdm->id,
            'nama'     => $sdm->nama_lengkap,
            'nip'      => $sdm->nip,
            'unit'     => $uk->nama_rumahsakit ?? null,
            'jenjang'  => $sdm->formasi?->jenjang?->nama_jenjang,
            'kabkota'  => $kab ? ($kab->type.' '.$kab->name) : null,
            'instansi' => $uk->instansi ?? null,
        ]);
    }

    public function trash()
{
    $uji = \App\Models\UjiKompetensi::onlyTrashed()
        ->with([
            'sdm.formasi.jenjang',
            'sdm.formasi.unitKerja.regency.province',
            'sdm.unitKerja.regency.province',
        ])->latest()->get();

    return view('uji_kompetensi.trash', compact('uji'));
}

public function restore($id)
{
    $u = \App\Models\UjiKompetensi::withTrashed()->findOrFail($id);
    $u->restore();
    return back()->with('success','Uji kompetensi direstore.');
}

public function forceDelete($id)
{
    $u = \App\Models\UjiKompetensi::withTrashed()->findOrFail($id);
    $u->forceDelete();
    return back()->with('success','Uji kompetensi dihapus permanen.');
}
}
