@extends('layouts.users.master')
@section('title','Laporan • Jumlah Pemangku (Simple)')

@section('isi')
<div class="container-fluid py-3">
  <h4 class="mb-3">Jumlah Pemangku — Matriks 22 JFT × Jenjang</h4>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th style="white-space:nowrap">#</th>
            <th style="min-width:280px">Jenis JFT</th>
            @foreach($jenjangOrder as $jj)
              <th class="text-end" style="white-space:nowrap">{{ $jj }}</th>
            @endforeach
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($allJft as $i => $jft)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $jft }}</td>
              @foreach($jenjangOrder as $jj)
                <td class="text-end">{{ number_format($matrix[$jft][$jj]) }}</td>
              @endforeach
              <td class="text-end fw-bold">{{ number_format($rowTotals[$jft]) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="2" class="text-end">Total per Jenjang</th>
            @foreach($jenjangOrder as $jj)
              <th class="text-end">{{ number_format($colTotals[$jj]) }}</th>
            @endforeach
            <th class="text-end">{{ number_format($grand) }}</th>
          </tr>
        </tfoot>
      </table>
      {{-- @if(!empty($outliers))
  <div class="alert alert-warning mt-3" role="alert">
    <strong>Perhatian:</strong> Ada {{ count($outliers) }} entri yang nama JFT-nya tidak cocok dengan 22 nama baku.
    Sementara dimasukkan ke baris <em>Lainnya</em>. Anda bisa koreksi ejaannya di tabel
    <code>formasi_jabatan.nama_formasi</code> / <code>jft_types.nama</code>.
    <details class="mt-2">
      <summary>Lihat daftar outliers</summary>
      <ul class="mb-0 mt-2">
        @foreach($outliers as $o)
          <li>{{ $o['jft_raw'] }} — {{ $o['jenjang'] }}: {{ $o['total'] }}</li>
        @endforeach
      </ul>
    </details>
  </div>
@endif --}}

    </div>
  </div>
</div>
@endsection
