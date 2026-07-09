@extends('layouts.users.master')
@section('title','Formasi Terhapus')
@section('isi')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Formasi Jabatan — Sampah</h4>
  <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="table-responsive">
  <table id="trashFormasi" class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>No</th><th>Nama Formasi</th><th>Jenjang</th><th>Unit Kerja</th><th>Tahun</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($formasi as $i => $f)
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $f->nama_formasi }}</td>
          <td>{{ $f->jenjang->nama_jenjang ?? '-' }}</td>
          <td>{{ $f->unitKerja->nama_rumahsakit ?? '-' }}</td>
          <td>{{ $f->tahun_formasi }}</td>
          <td class="d-flex gap-1">
            <form action="{{ route('user.formasi.restore',$f->id) }}" method="POST">@csrf @method('PATCH')
              <button class="btn btn-sm btn-success">Restore</button>
            </form>
            <form action="{{ route('user.formasi.force-delete',$f->id) }}" method="POST"
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
<script> $(function(){ $('#trashFormasi').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
