<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Formasijabatan;
use App\Models\Rumahsakit;

class FormasiJabatanController extends Controller
{
    public function index()
    {
        $formasi = Formasijabatan::with('unitkerja')->get();
        return view('formasi_jabatan.index', compact('formasi'));
    }

    public function create()
    {
        $unitkerja = Rumahsakit::all();
        return view('formasi_jabatan.create', compact('unitkerja'));
    }

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

        return redirect()->route('admin.formasi-jabatan.index')->with('success', 'Data berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $formasi = Formasijabatan::findOrFail($id);
        $unitkerja = Rumahsakit::all();

        return view('formasi_jabatan.edit', compact('formasi', 'unitkerja'));
    }

    public function update(Request $request, $id)
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

        return redirect()->route('admin.formasi-jabatan.index')->with('success', 'Data berhasil diubah.');
    }

    public function destroy($id)
    {
        $formasi = Formasijabatan::findOrFail($id);
        $formasi->delete();

        return redirect()->route('admin.formasi-jabatan.index')->with('success', 'Data berhasil dihapus.');
    }
}
