@extends('layouts.users.master')
@section('title','Pusbin JFT')
@section('isi')

@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="preview-card mb-4">
  <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="preview-header-title">Data Unit Kerja</span>
      <span class="preview-header-subtitle d-block">Kelola data unit kerja di seluruh Indonesia</span>
    </div>
    <div>
      @can('create unit kerja')
      <a href="{{ route('user.unitkerja.create') }}" class="btn btn-primary btn-sm mr-1">
        <i class="fas fa-plus"></i> Tambah Unit Kerja
      </a>
      <a href="{{ route('user.unitkerja.import') }}" class="btn btn-success btn-sm mr-1">
        <i class="fas fa-file-excel"></i> Import Excel
      </a>
      @endcan
      <a href="{{ route('user.unitkerja.trash') }}" class="btn btn-outline-secondary btn-sm">
        Sampah @if(!empty($trashed) && $trashed) <span class="badge badge-secondary">{{ $trashed }}</span>@endif
      </a>
    </div>
  </div>

  <div class="preview-body p-0">
    <div class="table-responsive">
      <table id="ukTable" class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>No</th>
            <th>Nama Unit Kerja</th>
            <th>Provinsi</th>
            <th>Kabupaten/Kota</th>
            <th>Matra</th>
            <th>Instansi</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($unitKerjas as $i => $unitKerja)
            @php
              $kab = $unitKerja->regency;
              $prov = $kab?->province;
            @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $unitKerja->nama_unit_kerja }}</td>
              <td>{{ $prov->name ?? '-' }}</td>
              <td>{{ $kab ? ($kab->type.' '.$kab->name) : '-' }}</td>
              <td>{{ $unitKerja->matra ?? '-' }}</td>
              <td>{{ $unitKerja->instansi ?? '-' }}</td>
              <td>{{ $unitKerja->latitude }}</td>
              <td>{{ $unitKerja->longitude }}</td>
              <td class="text-center">
                <div class="d-flex justify-content-center">
                  @can('edit unit kerja')
                  <a class="btn btn-sm btn-outline-warning mr-1" href="{{ route('user.unitkerja.edit', $unitKerja) }}" onclick="pindah(event)" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  @endcan
                  @can('delete unit kerja')
                  <form action="{{ route('user.unitkerja.destroy', $unitKerja) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                  @endcan
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
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
