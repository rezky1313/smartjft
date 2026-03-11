<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      margin: 20px;
    }
    .kop-surat {
      text-align: center;
      margin-bottom: 20px;
    }
    .kop-surat img {
      max-height: 80px;
    }
    .judul {
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .sub-judul {
      text-align: center;
      font-size: 12px;
      margin-bottom: 20px;
    }
    .info-cetak {
      text-align: right;
      margin-bottom: 20px;
    }
    .filter-info {
      margin-bottom: 20px;
      padding: 10px;
      background-color: #f5f5f5;
      border: 1px solid #ddd;
    }
    .filter-info table {
      width: 100%;
    }
    .filter-info td {
      padding: 2px 10px;
    }
    .filter-info td:first-child {
      font-weight: bold;
      width: 150px;
    }
    .summary-cards {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .summary-card {
      flex: 1;
      min-width: 150px;
      padding: 10px;
      border: 1px solid #ddd;
      text-align: center;
    }
    .summary-card h4 {
      margin: 0 0 5px 0;
      font-size: 11px;
      color: #666;
    }
    .summary-card h3 {
      margin: 0;
      font-size: 18px;
      font-weight: bold;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    table th,
    table td {
      border: 1px solid #333;
      padding: 6px 10px;
      text-align: center;
    }
    table th {
      background-color: #f0f0f0;
      font-weight: bold;
    }
    table td:first-child,
    table th:first-child {
      text-align: left;
    }
    .text-danger {
      color: #d32f2f;
      font-weight: bold;
    }
    .text-warning {
      color: #f57c00;
      font-weight: bold;
    }
  </style>
</head>
<body>
  {{-- Kop Surat --}}
  <div class="kop-surat">
    @if(file_exists(public_path('images/kop_surat.png')))
      <img src="{{ asset('images/kop_surat.png') }}" alt="Kop Surat">
    @endif
  </div>

  {{-- Judul --}}
  <div class="judul">{{ $title }}</div>
  <div class="sub-judul">Sistem Manajemen Jabatan Fungsional Transportasi</div>

  {{-- Informasi Cetak --}}
  <div class="info-cetak">
    Tanggal Cetak: {{ $tanggal_cetak }}
  </div>

  {{-- Filter --}}
  @if(!empty($filter_params))
  <div class="filter-info">
    <strong>Parameter Filter:</strong>
    <table>
      @foreach($filter_params as $key => $value)
        <tr>
          <td>{{ $key }}</td>
          <td>: {{ $value }}</td>
        </tr>
      @endforeach
    </table>
  </div>
  @endif

  {{-- Summary Cards --}}
  <div class="summary-cards">
    <div class="summary-card">
      <h4>Total Unit Kerja</h4>
      <h3>{{ number_format($dashboard['summary']['total_unit_kerja'] ?? 0) }}</h3>
    </div>
    <div class="summary-card">
      <h4>Total Kuota</h4>
      <h3>{{ number_format($dashboard['summary']['total_kuota'] ?? 0) }}</h3>
    </div>
    <div class="summary-card">
      <h4>Total Terisi</h4>
      <h3>{{ number_format($dashboard['summary']['total_terisi'] ?? 0) }}</h3>
    </div>
    <div class="summary-card">
      <h4>Total Sisa</h4>
      <h3>{{ number_format($dashboard['summary']['total_sisa'] ?? 0) }}</h3>
    </div>
    <div class="summary-card">
      <h4>Total Pegawai</h4>
      <h3>{{ number_format($dashboard['summary']['total_pegawai'] ?? 0) }}</h3>
    </div>
    <div class="summary-card">
      <h4>Di Luar Formasi</h4>
      <h3>{{ number_format($dashboard['summary']['total_di_luar_formasi'] ?? 0) }}</h3>
    </div>
  </div>

  {{-- Tabel Ringkasan per Provinsi --}}
  <h3 style="margin-top: 30px;">Ringkasan per Provinsi</h3>
  <table>
    <thead>
      <tr>
        <th style="width: 40px">No</th>
        <th>Provinsi</th>
        <th>Jml Unit Kerja</th>
        <th>Kuota</th>
        <th>Terisi</th>
        <th>Sisa</th>
        <th>Jml Pegawai</th>
      </tr>
    </thead>
    <tbody>
      @foreach($dashboard['province_summary'] as $i => $row)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $row['province'] }}</td>
          <td>{{ number_format($row['jml_unit_kerja']) }}</td>
          <td>{{ number_format($row['total_kuota']) }}</td>
          <td>{{ number_format($row['total_terisi']) }}</td>
          <td @if($row['total_sisa'] < 0) class="text-danger" @elseif($row['total_sisa'] == 0) class="text-warning" @endif>
            {{ number_format($row['total_sisa']) }}
          </td>
          <td>{{ number_format($row['jml_pegawai']) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Tabel Distribusi per Jenjang --}}
  <h3>Distribusi Pegawai per Jenjang</h3>
  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Jenjang</th>
        <th>Jumlah Pegawai</th>
      </tr>
    </thead>
    <tbody>
      @foreach($dashboard['jenjang_distribution'] as $jenjang => $jumlah)
        <tr>
          <td>{{ $loop->index + 1 }}</td>
          <td>{{ $jenjang }}</td>
          <td>{{ number_format($jumlah) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
