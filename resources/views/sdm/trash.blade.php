@extends('layouts.users.master')
@section('title','SDM Terhapus')
@section('isi')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>SDM Terhapus (Sampah)</h4>
  <a href="{{ route('user.sdm.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="table-responsive">
  <table id="trashTable" class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>No</th><th>NIP</th><th>Nama</th><th>Unit Kerja</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sdm as $i => $row)
        @php $uk = $row->formasi?->unitKerja ?? $row->unitKerja; @endphp
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $row->nip }}</td>
          <td>{{ $row->nama_lengkap }}</td>
          <td>{{ $uk->nama_rumahsakit ?? '-' }}</td>
          <td class="d-flex gap-1">
            <form action="{{ route('user.sdm.restore',$row->id) }}" method="POST">
              @csrf @method('PATCH')
              <button class="btn btn-sm btn-success">Restore</button>
            </form>
            <form action="{{ route('user.sdm.force-delete',$row->id) }}" method="POST"
                  onsubmit="return confirm('Hapus permanen? Tindakan ini tidak bisa dibatalkan!')">
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
<script> $(function(){ $('#trashTable').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
