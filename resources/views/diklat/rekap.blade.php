@extends('layouts.users.master')
@section('title', 'Rekapitulasi Diklat per Unit Kerja')
@section('isi')

<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">Rekapitulasi Diklat per Unit Kerja</span>
    <span class="preview-header-subtitle d-block">Jumlah diklat yang sudah diikuti pegawai, per unit kerja</span>
  </div>
  <div class="preview-body">

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>Unit Kerja</th>
            <th class="text-right">Teknis</th>
            <th class="text-right">Fungsional</th>
            <th class="text-right">Kepemimpinan</th>
            <th class="text-right">Lainnya</th>
            <th class="text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rekap as $r)
            <tr>
              <td>{{ $r['nama_unit_kerja'] }}</td>
              <td class="text-right">{{ number_format($r['per_jenis']['teknis']) }}</td>
              <td class="text-right">{{ number_format($r['per_jenis']['fungsional']) }}</td>
              <td class="text-right">{{ number_format($r['per_jenis']['kepemimpinan']) }}</td>
              <td class="text-right">{{ number_format($r['per_jenis']['lainnya']) }}</td>
              <td class="text-right"><strong>{{ number_format($r['total']) }}</strong></td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">Belum ada data diklat.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      <a href="{{ route('karir.diklat.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Kembali ke Daftar Diklat</a>
    </div>

  </div>
</div>

@endsection
