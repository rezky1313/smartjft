@extends('layouts.users.master')
@section('title','Unit Kerja Terhapus')
@section('isi')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Unit Kerja — Sampah</h4>
  <a href="{{ route('user.unitkerja.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="table-responsive">
  <table id="trashUk" class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>No</th><th>Kode</th><th>Nama</th><th>Provinsi</th><th>Kab/Kota</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rumahsakits as $i => $rs)
        @php $kab = $rs->regency; $prov = $kab?->province; @endphp
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $rs->no_rs }}</td>
          <td>{{ $rs->nama_rumahsakit }}</td>
          <td>{{ $prov->name ?? '-' }}</td>
          <td>{{ $kab ? ($kab->type.' '.$kab->name) : '-' }}</td>
          <td class="d-flex gap-1">
            <form action="{{ route('user.unitkerja.restore',$rs->no_rs) }}" method="POST">@csrf @method('PATCH')
              <button class="btn btn-sm btn-success">Restore</button>
            </form>
            <form action="{{ route('user.unitkerja.force-delete',$rs->no_rs) }}" method="POST"
                  onsubmit="return confirm('Hapus permanen? Tidak bisa dibatalkan!')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Hapus Permanen</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script> $(function(){ $('#trashUk').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
