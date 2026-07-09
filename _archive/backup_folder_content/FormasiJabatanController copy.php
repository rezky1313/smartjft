<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\Formasijabatan;
use App\models\Rumahsakit;

class FormasiJabatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $formasi = Formasijabatan::with('unitkerja')->get();
        return view('formasi_jabatan.index',compact('formasi'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $unitkerja = Rumahsakit::all();
       $jenjang = JenjangJabatan::orderBy('kategori')->orderBy('golongan')->get();
        return view('formasi_jabatan.create', compact('unitkerja', 'jenjang'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_formasi' => 'required',
            'jenjang' => 'required',
            'unit_kerja_id' => 'required|exists:rumahsakit,id',
            'kuota' => 'required|integer',
            'tahun_formasi' => 'required|digits:4',
        ]);

        Formasijabatan::create($request->all());
        return redirect()->route('formasi-jabatan.index')->with('success', 'Data Berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
         $formasi = Formasijabatan::findOrFail($id);
    $unitKerja = Rumahsakit::all();
    return view('formasi_jabatan.edit', compact('formasi', 'unitKerja'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
          $request->validate([
        'nama_formasi' => 'required',
        'jenjang' => 'required',
        'unit_kerja_id' => 'required|exists:rumahsakit,id',
        'kuota' => 'required|integer',
        'tahun_formasi' => 'required|digits:4',
    ]);

    $formasi = Formasijabatan::findOrFail($id);
    $formasi->update($request->all());

    return redirect()->route('formasi-jabatan.index')->with('success', 'Data berhasil diubah.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
           $formasi = Formasijabatan::findOrFail($id);
    $formasi->delete();

    return redirect()->route('formasi-jabatan.index')->with('success', 'Data berhasil dihapus.');

    }
}
