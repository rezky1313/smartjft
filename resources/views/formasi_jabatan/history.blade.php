@extends('layouts.users.master')
@section('title','Histori Formasi')

@section('isi')
<div class="container-fluid">
  <h4 class="mb-3">Histori Formasi</h4>

  <form method="get" class="d-flex flex-wrap gap-2 mb-3">
    <select name="unit_kerja_id" class="form-select" style="max-width:360px">
      <option value="">Semua Unit Kerja</option>
      @foreach($units as $u)
        <option value="{{ $u->no_rs }}" @selected(($unitId??'')==$u->no_rs)>{{ $u->nama_rumahsakit }}</option>
      @endforeach
    </select>
    <select name="tahun" class="form-select" style="max-width:180px">
      <option value="">Semua Tahun</option>
      @foreach($tahuns as $t)
        <option value="{{ $t }}" @selected(($tahun??'')==$t)>{{ $t }}</option>
      @endforeach
    </select>
    <button class="btn btn-outline-primary">Terapkan</button>
    <a href="{{ route('user.formasi.history') }}" class="btn btn-outline-secondary">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>Snapshot</th>
          <th>Unit Kerja</th>
          <th>Tahun</th>
          <th>Nama Formasi</th>
          <th>Jenjang</th>
          <th>Kuota</th>
          <th>Terisi (saat snapshot)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($hist as $h)
          <tr>
            <td>{{ \Carbon\Carbon::parse($h->snapshot_at)->format('Y-m-d H:i') }}</td>
            <td>{{ $h->unitKerja->nama_rumahsakit ?? '-' }}</td>
            <td>{{ $h->tahun_formasi }}</td>
            <td>{{ $h->nama_formasi }}</td>
            <td>{{ $h->jenjang->nama_jenjang ?? '-' }}</td>
            <td>{{ $h->kuota }}</td>
            <td>{{ $h->terisi }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">Belum ada histori.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $hist->withQueryString()->links() }}
  </div>
</div>
@endsection
