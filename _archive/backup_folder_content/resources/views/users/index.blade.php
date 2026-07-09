@extends('layouts.users.master')
@section('title','Pusbin JFT')
@section('isi')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Unit Kerja</th>
                <th>Alamat</th>
                <th>No. Telepon</th>
                <th>Jam Kerja</th>
                <th>Fasilitas</th>
                @if (Auth::user()->role =='admin')
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rumahsakits as $rumahsakit)
                <tr>
                    <td>{{ $rumahsakit->no_rs }}</td>
                    <td>{{ $rumahsakit->nama_rumahsakit }}</td>
                    <td>{{ $rumahsakit->alamat }}</td>
                    <td>{{ $rumahsakit->no_telp }}</td>
                    <td>{{ $rumahsakit->jam_kerja }}</td>
                    <td>{{ $rumahsakit->fasilitas }}</td>
                    @if (Auth::user()->role =='admin')
                    <td>{{ $rumahsakit->latitude }}</td>
                    <td>{{ $rumahsakit->longitude }}</td>
                    <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('admin.rumahsakit.show', $rumahsakit) }}" onclick="pindah(event)">Show</a>
                            <a class="btn btn-warning btn-sm" href="{{ route('admin.rumahsakit.edit', $rumahsakit) }}" onclick="pindah(event)">Edit</a>
                            <form action="{{ route('admin.rumahsakit.destroy', $rumahsakit) }}" method="POST" style="display: inline-block;" onclick="confirmDelete(this)">

                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
