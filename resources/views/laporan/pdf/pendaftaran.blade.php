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
    .keterbatasan { margin-bottom: 12px; padding: 8px; background-color: #fff8e1; border: 1px solid #ffe082; font-size: 9px; }
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

  @php $s = $pendaftaran['summary']; @endphp
  <div class="section-title">Ringkasan</div>
  <table class="summary-grid">
    <tr><td class="label">Total Permohonan</td><td>{{ number_format($s['total_permohonan']) }}</td></tr>
    <tr><td class="label">Total Ditolak</td><td>{{ number_format($s['total_ditolak']) }}</td></tr>
    <tr><td class="label">Tingkat Penolakan</td><td>{{ $s['tingkat_penolakan'] }}%</td></tr>
  </table>

  <div class="section-title">Breakdown per Status</div>
  <table>
    <thead><tr><th>Status</th><th>Jumlah</th></tr></thead>
    <tbody>
      @foreach($pendaftaran['per_status'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['jumlah'] }}</td></tr>
      @endforeach
    </tbody>
  </table>

  <div class="keterbatasan">
    <strong>Keterbatasan data:</strong> Rata-rata waktu verifikasi per tahap (Admin Unit / Admin Pusbin) tidak dapat dihitung akurat
    karena tabel pendaftaran hanya menyimpan status terakhir beserta <em>created_at</em>/<em>updated_at</em>, tanpa timestamp
    pada setiap transisi status. Yang ditampilkan hanya metrik yang valid dihitung dari data yang tersedia.
  </div>

  <div class="section-title">Permohonan Belum Selesai (diurutkan dari paling lama menunggu)</div>
  <table>
    <thead>
      <tr>
        <th style="width: 30px">No</th>
        <th>Kode</th>
        <th>Unit Kerja</th>
        <th>Jadwal</th>
        <th>Status</th>
        <th>Menunggu Sejak</th>
        <th>Jumlah Hari</th>
      </tr>
    </thead>
    <tbody>
      @forelse($pendaftaran['nyangkut'] as $i => $row)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $row['kode'] }}</td>
          <td>{{ $row['unit_kerja'] }}</td>
          <td>{{ $row['jadwal'] }}</td>
          <td>{{ $row['status'] }}</td>
          <td>{{ $row['menunggu_sejak']->format('d-m-Y') }}</td>
          <td>{{ $row['jumlah_hari'] }}</td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center">Tidak ada permohonan yang tertunda</td></tr>
      @endforelse
    </tbody>
  </table>

  @if($pendaftaran['catatan_penolakan']->isNotEmpty())
  <div class="section-title">Catatan Penolakan</div>
  <table>
    <thead><tr><th>Kode</th><th>Unit Kerja</th><th>Status</th><th>Catatan</th></tr></thead>
    <tbody>
      @foreach($pendaftaran['catatan_penolakan'] as $row)
        <tr>
          <td>{{ $row['kode'] }}</td>
          <td>{{ $row['unit_kerja'] }}</td>
          <td>{{ $row['status'] }}</td>
          <td style="text-align:left">{{ $row['catatan'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</body>
</html>
