@extends('layouts.users.master')
@section('title','Uji Kompetensi')
@section('isi')

<div class="container-fluid mt-3">  {{-- <— was .container --}}
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Data Uji Kompetensi</h4>
    <a href="{{ route('user.uji.create') }}" class="btn btn-primary">+ Tambah Uji Kompetensi</a>
    <a href="{{ route('user.uji.trash') }}" class="btn btn-outline-secondary">
    Sampah @if(!empty($trashed) && $trashed) <span class="badge bg-secondary">{{ $trashed }}</span>@endif
  </a>
  </div>

  <div class="table-responsive">
    <table id="ujiTable" class="table table-bordered table-striped align-middle w-100"> {{-- + w-100 --}}
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIP</th>
          <th>Unit Kerja</th>
          <th>Jenjang</th>
          <th>Kab/Kota</th>
          <th>Instansi</th>
          <th>Kompetensi</th>
          <th>Nilai</th>
          <th>Tanggal Uji</th>
          <th>No. Sertifikat</th>
          <th>Keterangan</th>
          <th width="150">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($uji as $i => $row)
          @php
            $sdm = $row->sdm;
            $uk  = $sdm?->formasi?->unitKerja ?? $sdm?->unitKerja;
            $kab = $uk?->regency;
          @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $sdm->nama_lengkap ?? '-' }}</td>
            <td>{{ $sdm->nip ?? '-' }}</td>
            <td>{{ $uk->nama_rumahsakit ?? '-' }}</td>
            <td>{{ $sdm?->formasi?->jenjang?->nama_jenjang ?? '-' }}</td>
            <td>{{ $kab ? ($kab->type.' '.$kab->name) : '-' }}</td>
            <td>{{ $uk->instansi ?? '-' }}</td>
            <td>{{ $row->kompetensi }}</td>
            <td>{{ is_null($row->nilai) ? '-' : $row->nilai }}</td>
            <td>{{ $row->tanggal_uji ? \Illuminate\Support\Carbon::parse($row->tanggal_uji)->format('d-m-Y') : '-' }}</td>
            <td>{{ $row->nomor_sertifikat ?? '-' }}</td>
            <td>{{ $row->keterangan ?? '-' }}</td>
            <td>
              <a href="{{ route('user.uji.edit', $row->id) }}" class="btn btn-sm btn-warning">Edit</a>
              <form action="{{ route('user.uji.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Hapus</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  $('#ujiTable').DataTable({
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'asc']],   // sort Nama
    autoWidth: false       // hindari tabel jadi sempit
    // responsive: true,    // opsional kalau mau
    // scrollX: true        // aktifkan kalau kolom sangat banyak
  });
});
</script>
@endpush
