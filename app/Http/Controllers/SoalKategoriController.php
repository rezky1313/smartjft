<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SoalKategori;

class SoalKategoriController extends Controller
{
    public function index()
    {
        $kategoris = SoalKategori::withCount('soal')->orderBy('nama')->get();
        return view('soal_kategori.index', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'    => 'required|string|max:255|unique:soal_kategori,nama',
            'jabatan' => 'nullable|string|max:255',
            'jenjang' => 'required|in:pemula,terampil,mahir,penyelia,ahli_pertama,ahli_muda,ahli_madya,ahli_utama,umum',
            'matra'   => 'required|in:darat,laut,udara,asdp,umum',
            'bidang'  => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        SoalKategori::create(array_merge($request->only([
            'nama', 'jabatan', 'jenjang', 'matra', 'bidang', 'deskripsi',
        ]), ['aktif' => true]));

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $kategori = SoalKategori::findOrFail($id);

        $request->validate([
            'nama'    => 'required|string|max:255|unique:soal_kategori,nama,' . $id,
            'jabatan' => 'nullable|string|max:255',
            'jenjang' => 'required|in:pemula,terampil,mahir,penyelia,ahli_pertama,ahli_muda,ahli_madya,ahli_utama,umum',
            'matra'   => 'required|in:darat,laut,udara,asdp,umum',
            'bidang'  => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'aktif'   => 'boolean',
        ]);

        $kategori->update($request->only([
            'nama', 'jabatan', 'jenjang', 'matra', 'bidang', 'deskripsi', 'aktif',
        ]));

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kategori = SoalKategori::withCount('soal')->findOrFail($id);

        if ($kategori->soal_count > 0) {
            return back()->with('error', "Kategori tidak dapat dihapus karena masih memiliki {$kategori->soal_count} soal.");
        }

        $kategori->delete();
        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
