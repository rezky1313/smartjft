@extends('layouts.users.master')
@section('title', 'Pusbin JFT')
@section('isi')

<div class="container-fluid">
    <h4 class="mb-4">Data Formasi Jabatan</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('user.formasi.create') }}" class="btn btn-primary mb-3">+ Tambah Formasi</a>
<a href="{{ route('user.formasi.trash') }}" class="btn btn-outline-secondary">
    Sampah @if(!empty($trashed) && $trashed) <span class="badge bg-secondary">{{ $trashed }}</span>@endif
  </a>
    <div class="table-responsive">
        <table id="formasiTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama Formasi</th>
                    <th>Jenjang</th>
                    <th>Unit Kerja</th>
                    <th>Provinsi</th>
                    <th>Kabupaten/Kota</th>
                    <th>Kuota</th>
                    <th>Terisi</th>
                    <th>Sisa</th>
                    <th>Tahun</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($formasi as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->nama_formasi }}</td>
                    <td>{{ $item->jenjang->nama_jenjang ?? '-' }}</td>
                    {{-- <td>{{ $item->unitkerja->nama_rumahsakit ?? '-' }}</td> --}}
                    {{-- pakai nama relasi yang konsisten: unitKerja (camelCase) --}}
<td>{{ $item->unitKerja->nama_rumahsakit ?? '-' }}</td>

<td>{{ optional($item->unitKerja->regency->province)->name ?? '-' }}</td>

<td>
    @php $r = $item->unitKerja->regency ?? null; @endphp
    {{ $r ? ($r->type.' '.$r->name) : '-' }}
</td>

                    <td>{{ $item->kuota }}</td>
                    <td>{{ $item->terisi }}</td>
                    <td>{{ $item->sisa }}</td> {{-- pakai accessor --}}
                    <td>{{ $item->tahun_formasi }}</td>
                    <td>
                        <a href="{{ route('user.formasi.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('user.formasi.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin hapus data ini?')">
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
