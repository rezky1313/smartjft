@extends('layouts.users.master')
@section('title','Uji Kompetensi Terhapus')
@section('isi')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Uji Kompetensi — Sampah</h4>
  <a href="{{ route('user.uji.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="table-responsive">
  <table id="trashUji" class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>No</th><th>Nama</th><th>NIP</th><th>Kompetensi</th><th>Tanggal Uji</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($uji as $i => $u)
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $u->sdm->nama_lengkap ?? '-' }}</td>
          <td>{{ $u->sdm->nip ?? '-' }}</td>
          <td>{{ $u->kompetensi }}</td>
          <td>{{ $u->tanggal_uji ? \Illuminate\Support\Carbon::parse($u->tanggal_uji)->format('d-m-Y') : '-' }}</td>
          <td class="d-flex gap-1">
            <form action="{{ route('user.uji.restore',$u->id) }}" method="POST">@csrf @method('PATCH')
              <button class="btn btn-sm btn	success">Restore</button>
            </form>
            <form action="{{ route('user.uji.force-delete',$u->id) }}" method="POST"
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
<script> $(function(){ $('#trashUji').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
