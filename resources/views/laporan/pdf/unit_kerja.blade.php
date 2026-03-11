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

  {{-- Tabel Data --}}
  <table>
    <thead>
      <tr>
        <th style="width: 40px">No</th>
        <th>Nama Unit Kerja</th>
        <th>Jenis UPT</th>
        <th>Provinsi</th>
        <th>Kab/Kota</th>
        <th>Jumlah Jabatan Formasi</th>
        <th>Jumlah Pegawai</th>
      </tr>
    </thead>
    <tbody>
      @if(!empty($unit_kerja) && count($unit_kerja) > 0)
        @foreach($unit_kerja as $i => $row)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['nama_unit_kerja'] }}</td>
            <td>{{ $row['jenis_upt'] }}</td>
            <td>{{ $row['provinsi'] }}</td>
            <td>{{ $row['kab_kota'] }}</td>
            <td>{{ number_format($row['jumlah_jabatan_formasi']) }}</td>
            <td>{{ number_format($row['jumlah_pegawai']) }}</td>
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="7" style="text-align: center;">Tidak ada data</td>
        </tr>
      @endif
    </tbody>
  </table>
</body>
</html>
