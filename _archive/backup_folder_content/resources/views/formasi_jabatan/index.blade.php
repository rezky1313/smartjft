@extends('layouts.users.master')
@section('title', 'Data Formasi Jabatan')
@section('isi')

<div class="container-fluid">
    <h4 class="mb-4">Data Formasi Jabatan</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('admin.formasi-jabatan.create') }}" class="btn btn-primary mb-3">+ Tambah Formasi</a>

    <div class="table-responsive">
        <table id="formasiTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Formasi</th>
                    <th>Jenjang</th>
                    <th>Unit Kerja</th>
                    <th>Wilayah</th>
                    <th>Kuota</th>
                    <th>Tahun</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($formasi as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->nama_formasi }}</td>
                    <td>{{ $item->jenjang }}</td>
                    <td>{{ $item->unitkerja->nama_rumahsakit ?? '-' }}</td>
                    <td>{{ $item->unitkerja->kota_kabupaten ?? '-' }}</td>
                    <td>{{ $item->kuota }}</td>
                    <td>{{ $item->tahun_formasi }}</td>
                    <td>
                        <a href="{{ route('admin.formasi-jabatan.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.formasi-jabatan.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin hapus data ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#formasiTable').DataTable();
});
</script>
@endpush

@endsection
