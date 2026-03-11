<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      margin: 15px;
    }
    .kop-surat {
      text-align: center;
      margin-bottom: 15px;
    }
    .kop-surat img {
      max-height: 80px;
    }
    .judul {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .sub-judul {
      text-align: center;
      font-size: 11px;
      margin-bottom: 15px;
    }
    .info-cetak {
      text-align: right;
      margin-bottom: 15px;
    }
    .filter-info {
      margin-bottom: 15px;
      padding: 8px;
      background-color: #f5f5f5;
      border: 1px solid #ddd;
    }
    .filter-info table {
      width: 100%;
    }
    .filter-info td {
      padding: 2px 8px;
    }
    .filter-info td:first-child {
      font-weight: bold;
      width: 120px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    table th,
    table td {
      border: 1px solid #333;
      padding: 4px 6px;
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
    .badge-success {
      background-color: #28a745;
      color: white;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 9px;
    }
    .badge-danger {
      background-color: #dc3545;
      color: white;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 9px;
    }
    .text-muted {
      color: #6c757d;
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
        <th style="width: 30px">No</th>
        <th>Nama Pegawai</th>
        <th>NIP</th>
        <th>Jabatan</th>
        <th>Jenjang</th>
        <th>Unit Kerja</th>
        <th>Provinsi</th>
        <th>Kab/Kota</th>
        <th>TMT Jabatan</th>
        <th>Status Formasi</th>
      </tr>
    </thead>
    <tbody>
      @if(!empty($pegawai) && count($pegawai) > 0)
        @foreach($pegawai as $i => $row)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['nama'] }}</td>
            <td>{{ $row['nip'] }}</td>
            <td>{{ $row['jabatan'] }}</td>
            <td>{{ $row['jenjang'] }}</td>
            <td>{{ $row['unit_kerja'] }}</td>
            <td>{{ $row['provinsi'] }}</td>
            <td>{{ $row['kab_kota'] }}</td>
            <td>{{ $row['tmt_jabatan'] }}</td>
            <td>
              @if($row['status_formasi'] === 'di_luar_formasi')
                <span class="badge-danger">Di Luar Formasi</span>
              @elseif($row['status_formasi'] === 'terpenuhi')
                <span class="badge-success">Terpenuhi</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="10" style="text-align: center;">Tidak ada data</td>
        </tr>
      @endif
    </tbody>
  </table>
</body>
</html>
