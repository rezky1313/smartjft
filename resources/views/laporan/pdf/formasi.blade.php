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

  {{-- Tabel Data --}}
  <table>
    <thead>
      <tr>
        <th rowspan="2" style="width: 30px; vertical-align: middle;">No</th>
        <th rowspan="2" style="vertical-align: middle;">Unit Kerja</th>
        <th rowspan="2" style="vertical-align: middle;">Nama Jabatan</th>
        <th rowspan="2" style="vertical-align: middle;">Tahun</th>
        <th colspan="9">Kuota</th>
        <th colspan="9">Terisi</th>
        <th colspan="9">Sisa</th>
      </tr>
      <tr>
        @foreach($formasi['cols'] as $c)
          <th style="font-size: 8px; padding: 2px;">{{ $c }}</th>
        @endforeach
        <th style="font-size: 8px; padding: 2px;">TOTAL</th>
        @foreach($formasi['cols'] as $c)
          <th style="font-size: 8px; padding: 2px;">{{ $c }}</th>
        @endforeach
        <th style="font-size: 8px; padding: 2px;">TOTAL</th>
        @foreach($formasi['cols'] as $c)
          <th style="font-size: 8px; padding: 2px;">{{ $c }}</th>
        @endforeach
        <th style="font-size: 8px; padding: 2px;">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      @if(!empty($formasi['data']) && count($formasi['data']) > 0)
        @foreach($formasi['data'] as $i => $row)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['unit_kerja'] }}</td>
            <td>{{ $row['nama_jabatan'] }}</td>
            <td>{{ $row['tahun'] }}</td>

            {{-- Kuota --}}
            @foreach($formasi['cols'] as $c)
              <td>{{ $row['kuota'][$c] }}</td>
            @endforeach
            <td><b>{{ array_sum($row['kuota']) }}</b></td>

            {{-- Terisi --}}
            @foreach($formasi['cols'] as $c)
              <td>{{ $row['terisi'][$c] }}</td>
            @endforeach
            <td><b>{{ array_sum($row['terisi']) }}</b></td>

            {{-- Sisa --}}
            @foreach($formasi['cols'] as $c)
              <td @if($row['sisa'][$c] < 0) class="text-danger" @elseif($row['sisa'][$c] == 0) class="text-warning" @endif>
                {{ $row['sisa'][$c] }}
              </td>
            @endforeach
            <td @if(array_sum($row['sisa']) < 0) class="text-danger" @elseif(array_sum($row['sisa']) == 0) class="text-warning" @endif>
              <b>{{ array_sum($row['sisa']) }}</b>
            </td>
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="38" style="text-align: center;">Tidak ada data</td>
        </tr>
      @endif
    </tbody>
  </table>
</body>
</html>
