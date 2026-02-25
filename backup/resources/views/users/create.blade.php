@extends('layouts.users.master')
@section('title','SIGPELAKES - ADMIN')
@section('isi')

<div class="container-fluid px-4">

    <div class="card mb-4">
        <div class="card-body">
            <h3>Tambah Data Unit Kerja</h3>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Data Rumah Sakit Malang
        </div>

        @if ($errors->any())
        <div class="alert alert-danger mt-4">
            <strong>Whoops!</strong> Ada masalah dengan inputanmu.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group ms-2">
                        <label for="nama_rumahsakit" class="form-label">Nama Unit Kerja:</label>
                        <input type="text" name="nama_rumahsakit" id="nama_rumahsakit" class="form-control" required>
                    </div>

                    <div class="form-group ms-2">
                        <label for="alamat" class="form-label">Alamat:</label>
                        <input type="text" name="alamat" id="alamat" class="form-control" required>
                    </div>
                    <div class="form-group ms-2">
                        <label for="no_telp" class="form-label">No. Telepon:</label>
                        <input type="text" name="no_telp" id="no_telp" class="form-control" required>
                    </div>
                    <div class="form-group ms-2">
                        <label for="jam_kerja" class="form-label">Jam Kerja:</label>
                        <input type="text" name="jam_kerja" id="jam_kerja" class="form-control" required>
                    </div>
                    <div class="form-group ms-2">
                        <label for="fasilitas" class="form-label">Kabupaten/Kota:</label>
                        <input type="text" name="fasilitas" id="fasilitas" class="form-control" required>
                    </div>
                    <div class="form-group ms-2">
                        <label for="latitude" class="form-label">Latitude:</label>
                        <input type="text" name="latitude" id="latitude" placeholder="-7.3811577" class="form-control">
                    </div>
                    <div class="form-group ms-2">
                        <label for="longitude" class="form-label">Longitude:</label>
                        <input type="text" name="longitude" id="longitude" placeholder="109.2550945" class="form-control">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <div class="col-md-12">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <div id="leafletMap-registration"></div>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-mb-4">
                <div class="row justify-content-center mt-3">
                    <div class="com-md-6">
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
