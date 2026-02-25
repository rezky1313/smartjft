@extends('layouts.users.master')
@section('title','Pusbin JFT')
@section('isi')

@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Data Unit Kerja</h4>
  <a href="{{ route('user.unitkerja.create') }}" class="btn btn-primary">+ Tambah Unit Kerja</a>
  <a href="{{ route('user.unitkerja.trash') }}" class="btn btn-outline-secondary">
    Sampah @if(!empty($trashed) && $trashed) <span class="badge bg-secondary">{{ $trashed }}</span>@endif
  </a>
</div>


<div class="table-responsive">
  <table id="ukTable" class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>No</th>
        {{-- <th>Kode</th> --}}
        <th>Nama Unit Kerja</th>
        {{-- <th>Alamat</th>
        <th>No. Telepon</th> --}}
        <th>Provinsi</th>
        <th>Kabupaten/Kota</th>
        <th>Matra</th>
        <th>Instansi</th>
        <th>Latitude</th>
        <th>Longitude</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rumahsakits as $i => $rumahsakit)
        @php
          $kab = $rumahsakit->regency;
          $prov = $kab?->province;
        @endphp
        <tr>
          <td>{{ $i+1 }}</td>
          {{-- <td>{{ $rumahsakit->no_rs }}</td> --}}
          <td>{{ $rumahsakit->nama_rumahsakit }}</td>
          {{-- <td>{{ $rumahsakit->alamat }}</td>
          <td>{{ $rumahsakit->no_telp }}</td> --}}
          <td>{{ $prov->name ?? '-' }}</td>
          <td>{{ $kab ? ($kab->type.' '.$kab->name) : '-' }}</td>
          <td>{{ $rumahsakit->matra ?? '-' }}</td>
          <td>{{ $rumahsakit->instansi ?? '-' }}</td>
          <td>{{ $rumahsakit->latitude }}</td>
          <td>{{ $rumahsakit->longitude }}</td>
          <td>
            {{-- <a class="btn btn-primary btn-sm" href="{{ route('user.rumahsakit.show', $rumahsakit) }}" onclick="pindah(event)">Show</a> --}}
            <a class="btn btn-warning btn-sm" href="{{ route('user.unitkerja.edit', $rumahsakit) }}" onclick="pindah(event)">Edit</a>
            <form action="{{ route('user.unitkerja.destroy', $rumahsakit) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus data ini?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
  $('#ukTable').DataTable({
    pageLength: 10,
    lengthMenu: [10,25,50,100],
    order: [[2,'asc']], // sort Nama Unit Kerja
  });
});
</script>
@endpush
