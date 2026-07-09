<?php

namespace App\Http\Controllers;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Province;
use App\Models\Regency;

class UnitKerjaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $unitKerjas = UnitKerja::all();
        $unitKerjas = UnitKerja::with(['regency.province'])
        ->orderBy('nama_unit_kerja')
        ->get();
        $trashed = \App\Models\UnitKerja::onlyTrashed()->count();
    return view('users.index', compact('unitKerjas','trashed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
           $provinces = Province::orderBy('name')->get(['id','name']);
    $regencies = collect(); // kosong sampai provinsi dipilih
    $unitKerja = new UnitKerja();
        return view('users.create' , compact('provinces','regencies', 'unitKerja'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // sementara untuk debug
// dd($request->regency_id);

       $validated =  $request->validate([
            'nama_unit_kerja' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'regency_id' => 'required|exists:regencies,id',
              'matra'           => 'required|in:Darat,Laut,Udara,Kereta', // <—
    'instansi'        => 'required|in:Pusat,Daerah',            // <—
        ]);



        UnitKerja::create($validated);

        return redirect()->route('user.unitkerja.index')
            ->with('success', 'Unit Kerja berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(UnitKerja $unitKerja)
    {
        return view('users.show', compact('unitKerja'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UnitKerja $unitKerja)
    {
         $provinces = Province::orderBy('name')->get(['id','name']);
    $regencies = $unitKerja->regency
        ? Regency::where('province_id', $unitKerja->regency->province_id)
            ->orderBy('type')->orderBy('name')->get(['id','name','type'])
        : collect();
        return view('users.edit', ['unitkerja' => $unitKerja, 'provinces' => $provinces, 'regencies' => $regencies]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitKerja $unitKerja)
    {
       $validated = $request->validate([
            'nama_unit_kerja' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
             'regency_id' => 'required|exists:regencies,id',
               'matra'           => 'required|in:Darat,Laut,Udara,Kereta',
    'instansi'        => 'required|in:Pusat,Daerah',
        ]);




        $unitKerja->update($validated);

        return redirect()->route('user.unitkerja.index')
            ->with('success', 'Unit Kerja berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnitKerja $unitKerja)
    {
        $unitKerja->delete();
        return redirect()->route('user.unitkerja.index')
            ->with('success', 'Unit Kerja berhasil dihapus.');
    }

public function trash()
{
    $unitKerjas = \App\Models\UnitKerja::onlyTrashed()
        ->with(['regency.province'])->orderBy('nama_unit_kerja')->get();

    return view('users.trash', compact('unitKerjas'));
}

public function restore($id)
{
    $rs = \App\Models\UnitKerja::withTrashed()->whereKey($id)->firstOrFail();
    $rs->restore();
    return back()->with('success','Unit Kerja direstore.');
}

public function forceDelete($id)
{
    $rs = \App\Models\UnitKerja::withTrashed()->whereKey($id)->firstOrFail();
    $rs->forceDelete(); // hati-hati: permanen
    return back()->with('success','Unit Kerja dihapus permanen.');
}


}
