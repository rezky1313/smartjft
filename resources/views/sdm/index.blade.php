@extends('layouts.users.master')
@section('title', 'Data Pegawai')
@section('isi')

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Data Pemangku JFT</h4>
  <a href="{{ route('user.sdm.create') }}" class="btn btn-primary">+ Tambah Pemangku JFT</a>
  <a href="{{ route('user.sdm.import.form') }}" class="btn btn-primary">+ Import Excel</a>
</div>

<div class="table-responsive">
  <table id="sdmTable" class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>No</th>
        <th>NIP</th>
        <th>Nama</th>
        <th>JK</th>
        <th>Status</th>
        <th>Pangkat/Gol</th>
        <th>Jenjang</th>
        <th>Unit Kerja</th>
        <th>Provinsi</th>
        <th>TMT</th>
        <th>Masa Jabatan</th>
        <th>Aktif</th>
        <th width="150">Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sdm as $i => $row)
        @php
          $uk   = $row->formasi?->unitKerja ?? $row->unitKerja;
          $kab  = $uk?->regency;
          $prov = $kab?->province;
        @endphp
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $row->nip ?? '-' }}</td>
          <td>{{ $row->nama_lengkap }}</td>
          <td>{{ $row->jenis_kelamin }}</td>
          <td>{{ $row->status_kepegawaian }}</td>
          <td>{{ $row->pangkat_golongan }}</td>
          <td>{{ $row->formasi?->jenjang?->nama_jenjang ?? '-' }}</td>
          <td>{{ $uk->nama_rumahsakit ?? '-' }}</td>
          <td>{{ $prov->name ?? '-' }}</td>
          <td>{{ $row->tmt_pengangkatan?->format('d-m-Y') ?? '-' }}</td>
          <td>{{ $row->masa_jabatan ?? '-' }}</td>
          <td>
            @if($row->aktif)
              <span class="badge bg-success">Aktif</span>
            @else
              <span class="badge bg-secondary">Nonaktif</span>
            @endif
          </td>
          <td>
            <a href="{{ route('user.sdm.edit', $row->id) }}" class="btn btn-sm btn-warning">Edit</a>
            <form action="{{ route('user.sdm.destroy', $row->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Hapus SDM ini?')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@endsection

@push('scripts')
<script>
$(function () {
  $('#sdmTable').DataTable({
    pageLength: 10,
    lengthMenu: [10,25,50,100],
    order: [[2,'asc']], // sort Nama
  });
});
</script>
@endpush
