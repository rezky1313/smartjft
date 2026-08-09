@extends('layouts.users.master')
@section('title', 'Pegawai Belum Pernah Diklat')
@section('isi')

<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">Pegawai Belum Pernah Diklat</span>
    <span class="preview-header-subtitle d-block">Pegawai yang belum memiliki satupun entri riwayat diklat</span>
  </div>
  <div class="preview-body">

    <form method="GET" action="{{ route('karir.diklat.belum') }}" class="form-row align-items-end mb-3">
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Unit Kerja</label>
        <select name="unit_kerja_id" class="form-control form-control-sm" onchange="this.form.submit()" {{ $unitKerjaList->count() <= 1 ? 'disabled' : '' }}>
          <option value="">Semua Unit Kerja</option>
          @foreach($unitKerjaList as $u)
            <option value="{{ $u->id }}" @selected(request('unit_kerja_id') == $u->id)>{{ $u->nama_unit_kerja }}</option>
          @endforeach
        </select>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>Nama</th>
            <th>NIP</th>
            <th>Unit Kerja</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pegawai as $p)
            <tr>
              <td>{{ $p->nama_lengkap }}</td>
              <td>{{ $p->nip ?? '-' }}</td>
              <td>{{ $p->unitKerja->nama_unit_kerja ?? '-' }}</td>
              <td class="text-center">
                @can('create pegawai')
                <a href="{{ route('karir.diklat.create', ['sdm_id' => $p->id]) }}" class="btn btn-sm btn-primary">+ Tambah Diklat</a>
                @endcan
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted">Semua pegawai sudah pernah mengikuti diklat.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3 d-flex justify-content-between align-items-center">
      <a href="{{ route('karir.diklat.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Kembali ke Daftar Diklat</a>
      {{ $pegawai->links('pagination::bootstrap-4') }}
    </div>

  </div>
</div>

@endsection
