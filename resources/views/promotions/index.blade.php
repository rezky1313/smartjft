@extends('layouts.users.master')

@section('isi')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Kenaikan Jenjang</h4>
  {{-- @if($isAdmin) --}}
    <a href="{{ route('user.promotions.create') }}" class="btn btn-primary">Buat Usulan</a>
  {{-- @endif --}}
</div>

<table class="table table-sm table-bordered">
  <thead>
    <tr>
      <th>#</th><th>Pegawai</th><th>Asal → Target</th><th>Status</th><th>Diajukan</th><th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $i => $p)
      <tr>
        <td>{{ $rows->firstItem() + $i }}</td>
       <td>{{ $p->sdm->nama_lengkap ?? '—' }}</td>
<td>{{ $p->jenjangAsal->nama_jenjang ?? '—' }} → {{ $p->jenjangTarget->nama_jenjang ?? '—' }}</td>

        <td><span class="badge text-bg-secondary">{{ $p->status }}</span></td>
        <td>{{ optional($p->submitted_at)->format('d/m/Y H:i') ?? '—' }}</td>
        <td><a class="btn btn-link btn-sm" href="{{ route('user.promotions.show',$p->id) }}">Detail</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="text-center">Belum ada usulan</td></tr>
    @endforelse
  </tbody>
</table>

{{ $rows->links() }}
@endsection
