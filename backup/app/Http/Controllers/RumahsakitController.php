<?php

namespace App\Http\Controllers;

use App\Models\Rumahsakit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class RumahsakitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rumahsakits = Rumahsakit::all();
        return view('users.index', compact('rumahsakits'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_rumahsakit' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'jam_kerja' => 'required',
            'fasilitas' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        Rumahsakit::create([
            'nama_rumahsakit' => $request->nama_rumahsakit,
            'alamat' => $request->alamat,
            'no_telp' => $request->no_telp,
            'jam_kerja' => $request->jam_kerja,
            'fasilitas' => $request->fasilitas,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return redirect()->route('admin.index')
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
    public function edit(Rumahsakit $rumahsakit)
    {
        return view('users.edit', compact('rumahsakit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rumahsakit $rumahsakit)
    {
        $request->validate([
            'nama_rumahsakit' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'jam_kerja' => 'required',
            'fasilitas' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);


        $rumahsakit->update([
            'nama_rumahsakit' => $request->nama_rumahsakit,
            'alamat' => $request->alamat,
            'no_telp' => $request->no_telp,
            'jam_kerja' => $request->jam_kerja,
            'fasilitas' => $request->fasilitas,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Unit Kerja berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rumahsakit $rumahsakit)
    {
        $rumahsakit->delete();
        return redirect()->route('admin.index')
            ->with('success', 'Unit Kerja berhasil dihapus.');
    }




}
