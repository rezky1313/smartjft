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
    .badge-dark { background-color: #343a40; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
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

  @php $s = $ujikom['summary']; @endphp
  <div class="section-title">Ringkasan</div>
  <table class="summary-grid">
    <tr><td class="label">Total Jadwal</td><td>{{ number_format($s['total_jadwal']) }}</td></tr>
    <tr><td class="label">Total Peserta</td><td>{{ number_format($s['total_peserta']) }}</td></tr>
    <tr><td class="label">Lulus</td><td>{{ number_format($s['lulus']) }}</td></tr>
    <tr><td class="label">Tidak Lulus</td><td>{{ number_format($s['tidak_lulus']) }}</td></tr>
    <tr><td class="label">Belum Dinilai</td><td>{{ number_format($s['belum_dinilai']) }}</td></tr>
    <tr><td class="label">Tingkat Kelulusan</td><td>{{ $s['tingkat_kelulusan'] }}%</td></tr>
    <tr><td class="label">Terindikasi Kecurangan</td><td>{{ number_format($s['terindikasi_kecurangan']) }} sesi</td></tr>
  </table>

  <div class="section-title">Rata-rata Nilai per Aspek</div>
  <table>
    <thead><tr><th>Teknis - CAT</th><th>Teknis - Wawancara</th><th>Teknis - Presentasi</th><th>Mansoskul - CAT</th><th>Mansoskul - Wawancara</th><th>Mansoskul - Presentasi</th></tr></thead>
    <tbody>
      <tr>
        <td>{{ $ujikom['aspek']['teknis_cat'] }}</td>
        <td>{{ $ujikom['aspek']['teknis_wawancara'] }}</td>
        <td>{{ $ujikom['aspek']['teknis_presentasi'] }}</td>
        <td>{{ $ujikom['aspek']['mansoskul_cat'] }}</td>
        <td>{{ $ujikom['aspek']['mansoskul_wawancara'] }}</td>
        <td>{{ $ujikom['aspek']['mansoskul_presentasi'] }}</td>
      </tr>
    </tbody>
  </table>

  <div class="section-title">Rata-rata Nilai per Kompetensi</div>
  <table>
    <thead><tr><th>Teknis</th><th>Mansoskul</th></tr></thead>
    <tbody><tr><td>{{ $ujikom['kompetensi']['teknis'] }}</td><td>{{ $ujikom['kompetensi']['mansoskul'] }}</td></tr></tbody>
  </table>

  <div class="section-title">Rekap per Jadwal</div>
  <table>
    <thead>
      <tr>
        <th style="width: 30px">No</th>
        <th>Nama Jadwal</th>
        <th>Jenjang</th>
        <th>Jml Peserta</th>
        <th>Lulus</th>
        <th>Tidak Lulus</th>
        <th>Belum Dinilai</th>
        <th>Rata-rata Nilai</th>
        <th>Kecurangan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ujikom['per_jadwal'] as $i => $row)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $row['jadwal'] }}</td>
          <td>{{ $row['jenjang'] }}</td>
          <td>{{ $row['jumlah_peserta'] }}</td>
          <td>{{ $row['lulus'] }}</td>
          <td>{{ $row['tidak_lulus'] }}</td>
          <td>{{ $row['belum_dinilai'] }}</td>
          <td>{{ $row['rata_nilai'] }}</td>
          <td>{{ $row['kecurangan'] > 0 ? $row['kecurangan'] : '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center">Tidak ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
