<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px; }
    .kop-surat { text-align: center; margin-bottom: 15px; }
    .kop-surat img { max-height: 80px; }
    .judul { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 5px; }
    .sub-judul { text-align: center; font-size: 11px; margin-bottom: 15px; }
    .info-cetak { text-align: right; margin-bottom: 15px; }
    .filter-info { margin-bottom: 15px; padding: 8px; background-color: #f5f5f5; border: 1px solid #ddd; }
    .filter-info table { width: 100%; }
    .filter-info td { padding: 2px 8px; }
    .filter-info td:first-child { font-weight: bold; width: 120px; }
    .section-title { font-size: 12px; font-weight: bold; margin: 15px 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: center; }
    table th { background-color: #f0f0f0; font-weight: bold; }
    table td:first-child, table th:first-child { text-align: left; }
    .summary-grid { width: 100%; margin-bottom: 10px; }
    .summary-grid td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
    .summary-grid td.label { font-weight: bold; width: 60%; background-color: #f5f5f5; }
  </style>
</head>
<body>
  <div class="kop-surat">
    @if(file_exists(public_path('images/kop_surat.png')))
      <img src="{{ asset('images/kop_surat.png') }}" alt="Kop Surat">
    @endif
  </div>

  <div class="judul">{{ $title }}</div>
  <div class="sub-judul">Sistem Manajemen Jabatan Fungsional Transportasi</div>

  <div class="info-cetak">Tanggal Cetak: {{ $tanggal_cetak }}</div>

  @if(!empty($filter_params))
  <div class="filter-info">
    <strong>Parameter Filter:</strong>
    <table>
      @foreach($filter_params as $key => $value)
        <tr><td>{{ $key }}</td><td>: {{ $value }}</td></tr>
      @endforeach
    </table>
  </div>
  @endif

  @php $s = $pengangkatan['summary']; @endphp
  <div class="section-title">Ringkasan</div>
  <table class="summary-grid">
    <tr><td class="label">Total Permohonan</td><td>{{ number_format($s['total_permohonan']) }}</td></tr>
    <tr><td class="label">Total Pegawai Diangkat</td><td>{{ number_format($s['total_diangkat']) }}</td></tr>
    <tr><td class="label">Rata-rata Waktu Proses</td><td>{{ $s['rata_waktu_proses_hari'] !== null ? $s['rata_waktu_proses_hari'].' hari' : '-' }}</td></tr>
  </table>
  <p style="font-size:9px; color:#666;">
    Catatan: filter/breakdown "Jalur Pengangkatan" tidak tersedia karena kolom tersebut sudah dihapus dari skema sejak penyederhanaan alur Pengangkatan JFT (v1.14.0).
  </p>

  <div class="section-title">Rekap per Unit Kerja</div>
  <table>
    <thead>
      <tr>
        <th style="width: 30px">No</th>
        <th>Unit Kerja</th>
        <th>Jabatan</th>
        <th>Jenjang</th>
        <th>Jumlah Diangkat</th>
      </tr>
    </thead>
    <tbody>
      @php $no = 1; @endphp
      @forelse($pengangkatan['rekap_unit'] as $unit)
        @if(empty($unit['rincian']))
          <tr>
            <td>{{ $no++ }}</td>
            <td>{{ $unit['unit_kerja'] }}</td>
            <td colspan="3">Tidak ada pengangkatan selesai pada periode ini</td>
          </tr>
        @else
          @foreach($unit['rincian'] as $r)
            <tr>
              <td>{{ $no++ }}</td>
              <td>{{ $unit['unit_kerja'] }}</td>
              <td>{{ $r['jabatan'] }}</td>
              <td>{{ $r['jenjang'] }}</td>
              <td>{{ $r['jumlah'] }}</td>
            </tr>
          @endforeach
        @endif
      @empty
        <tr><td colspan="5" style="text-align:center">Tidak ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
