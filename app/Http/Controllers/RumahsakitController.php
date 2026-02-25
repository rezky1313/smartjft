<?php

namespace App\Http\Controllers;

use App\Models\Rumahsakit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Province;
use App\Models\Regency;

class RumahsakitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $rumahsakits = Rumahsakit::all();
        $rumahsakits = Rumahsakit::with(['regency.province'])
        ->orderBy('nama_rumahsakit')
        ->get();
        $trashed = \App\Models\Rumahsakit::onlyTrashed()->count();
    return view('users.index', compact('rumahsakits','trashed'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
           $provinces = Province::orderBy('name')->get(['id','name']);
    $regencies = collect(); // kosong sampai provinsi dipilih
    $rumahsakit = new Rumahsakit();
        return view('users.create' , compact('provinces','regencies', 'rumahsakit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // sementara untuk debug
// dd($request->regency_id);

       $validated =  $request->validate([
            'nama_rumahsakit' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'regency_id' => 'required|exists:regencies,id',
              'matra'           => 'required|in:Darat,Laut,Udara,Kereta', // <—
    'instansi'        => 'required|in:Pusat,Daerah',            // <—
        ]);

       

        Rumahsakit::create($validated);

        return redirect()->route('user.unitkerja.index')
            ->with('success', 'Unit Kerja berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Rumahsakit $rumahsakit)
    {
        return view('users.show', compact('rumahsakit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rumahsakit $unitkerja)
    {
         $provinces = Province::orderBy('name')->get(['id','name']);
    $regencies = $unitkerja->regency
        ? Regency::where('province_id', $unitkerja->regency->province_id)
            ->orderBy('type')->orderBy('name')->get(['id','name','type'])
        : collect();
        return view('users.edit', compact('unitkerja','provinces','regencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rumahsakit $unitkerja)
    {
       $validated = $request->validate([
            'nama_rumahsakit' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
             'regency_id' => 'required|exists:regencies,id',
               'matra'           => 'required|in:Darat,Laut,Udara,Kereta',
    'instansi'        => 'required|in:Pusat,Daerah',
        ]);


      

        $unitkerja->update($validated);

        return redirect()->route('user.unitkerja.index')
            ->with('success', 'Unit Kerja berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rumahsakit $rumahsakit)
    {
        $rumahsakit->delete();
        return redirect()->route('user.unitkerja.index')
            ->with('success', 'Unit Kerja berhasil dihapus.');
    }

public function trash()
{
    $rumahsakits = \App\Models\Rumahsakit::onlyTrashed()
        ->with(['regency.province'])->orderBy('nama_rumahsakit')->get();

    return view('users.trash', compact('rumahsakits'));
}

public function restore($id)
{
    $rs = \App\Models\Rumahsakit::withTrashed()->whereKey($id)->firstOrFail();
    $rs->restore();
    return back()->with('success','Unit Kerja direstore.');
}

public function forceDelete($id)
{
    $rs = \App\Models\Rumahsakit::withTrashed()->whereKey($id)->firstOrFail();
    $rs->forceDelete(); // hati-hati: permanen
    return back()->with('success','Unit Kerja dihapus permanen.');
}


}
