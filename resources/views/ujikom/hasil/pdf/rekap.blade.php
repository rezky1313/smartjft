<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Rekap Hasil Uji Kompetensi — {{ $jadwal->judul }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; margin: 10px; }
    .kop-surat { text-align: center; margin-bottom: 10px; }
    .kop-surat img { width: 100%; max-height: 75px; }
    .judul { text-align: center; font-size: 13px; font-weight: bold; margin: 10px 0 4px; text-transform: uppercase; }
    .sub-judul { text-align: center; font-size: 11px; margin-bottom: 12px; }
    .info-box { border: 1px solid #333; padding: 8px; margin-bottom: 10px; }
    .info-box table { width: 100%; border: none; margin: 0; }
    .info-box td { border: none; padding: 2px 4px; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.data th { background: #1a3c5e; color: #fff; padding: 5px 6px; text-align: center; font-size: 9.5px; }
    table.data td { border: 1px solid #ccc; padding: 4px 6px; font-size: 9.5px; vertical-align: top; }
    table.data tr:nth-child(even) td { background: #f5f5f5; }
    .badge-lulus { color: #155724; background: #d4edda; padding: 2px 6px; border-radius: 4px; }
    .badge-tidak { color: #721c24; background: #f8d7da; padding: 2px 6px; border-radius: 4px; }
    .badge-belum { color: #383d41; background: #e2e3e5; padding: 2px 6px; border-radius: 4px; }
    .text-center { text-align: center; }
    .statistik-box { display: flex; gap: 10px; margin-bottom: 12px; }
    .stat-item { flex: 1; border: 1px solid #ccc; border-radius: 4px; padding: 6px; text-align: center; }
    .stat-angka { font-size: 16px; font-weight: bold; }
    .ttd-area { margin-top: 20px; }
    .ttd-area table { width: 100%; }
    .ttd-area td { width: 33%; text-align: center; padding: 8px; }
    .ttd-line { margin-top: 50px; border-top: 1px solid #333; }
    hr { border-top: 2px solid #333; margin: 8px 0; }
  </style>
</head>
<body>

  {{-- Kop Surat --}}
  <div class="kop-surat">
    @if(file_exists(public_path('images/kop_surat.png')))
    <img src="{{ asset('images/kop_surat.png') }}" alt="Kop Surat">
    @else
    <p><strong>PUSAT PEMBINAAN JABATAN FUNGSIONAL TRANSPORTASI</strong><br>
    Kementerian Perhubungan Republik Indonesia</p>
    @endif
  </div>
  <hr>

  <div class="judul">REKAP HASIL UJI KOMPETENSI</div>
  <div class="sub-judul">{{ strtoupper($jadwal->judul) }}</div>

  {{-- Info Jadwal --}}
  <div class="info-box">
    <table>
      <tr>
        <td width="130"><strong>Tanggal Ujian</strong></td>
        <td width="10">:</td>
        <td>{{ $jadwal->tanggal_mulai?->format('d M Y') }}
            @if ($jadwal->tanggal_selesai && $jadwal->tanggal_selesai != $jadwal->tanggal_mulai)
            s.d. {{ $jadwal->tanggal_selesai->format('d M Y') }}
            @endif
        </td>
        <td width="130"><strong>Jenis Ujian</strong></td>
        <td width="10">:</td>
        <td>{{ $jadwal->jenis_ujian_label }}</td>
      </tr>
      <tr>
        <td><strong>Tempat</strong></td>
        <td>:</td>
        <td>{{ $jadwal->tempat ?? '-' }}</td>
        <td><strong>Tanggal Cetak</strong></td>
        <td>:</td>
        <td>{{ now()->format('d M Y') }}</td>
      </tr>
    </table>
  </div>

  {{-- Statistik --}}
  @php
    $pctLulus = $total > 0 ? round($lulus / $total * 100) : 0;
    $pctTidak = $total > 0 ? round($tidakLulus / $total * 100) : 0;
  @endphp
  <table style="width:100%; margin-bottom:10px; border-collapse:collapse;">
    <tr>
      <td style="width:25%; text-align:center; border:1px solid #ccc; padding:6px;">
        <div style="font-size:16px; font-weight:bold;">{{ $total }}</div>
        <div>Total Peserta</div>
      </td>
      <td style="width:25%; text-align:center; border:1px solid #ccc; padding:6px; color:#155724; background:#d4edda;">
        <div style="font-size:16px; font-weight:bold;">{{ $lulus }}</div>
        <div>Lulus ({{ $pctLulus }}%)</div>
      </td>
      <td style="width:25%; text-align:center; border:1px solid #ccc; padding:6px; color:#721c24; background:#f8d7da;">
        <div style="font-size:16px; font-weight:bold;">{{ $tidakLulus }}</div>
        <div>Tidak Lulus ({{ $pctTidak }}%)</div>
      </td>
      <td style="width:25%; text-align:center; border:1px solid #ccc; padding:6px; color:#383d41; background:#e2e3e5;">
        <div style="font-size:16px; font-weight:bold;">{{ $total - $lulus - $tidakLulus }}</div>
        <div>Belum Dinilai</div>
      </td>
    </tr>
  </table>

  {{-- Tabel Peserta --}}
  <table class="data">
    <thead>
      <tr>
        <th width="25">No</th>
        <th>Nama Peserta</th>
        <th width="95">NIP</th>
        <th width="110">Unit Kerja</th>
        <th width="60">Jabatan Tujuan</th>
        <th width="45" class="text-center">Jenis</th>
        <th width="90">Rincian Teknis</th>
        <th width="90">Rincian Mansoskul</th>
        <th width="40" class="text-center">Nilai</th>
        <th width="60" class="text-center">Status</th>
        <th width="60" class="text-center">Kecurangan</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($pesertaList as $i => $p)
      @php $hasil = $hasilMap->get($p->id); @endphp
      <tr>
        <td class="text-center">{{ $i + 1 }}</td>
        <td>{{ $p->pegawai?->nama_lengkap ?? '-' }}</td>
        <td>{{ $p->pegawai?->nip ?? '-' }}</td>
        <td>{{ $p->pegawai?->unitKerja?->nama_rs ?? '-' }}</td>
        <td>{{ $p->jabatan_tujuan_nama ?? '-' }} / {{ $p->jenjang_tujuan ?? '-' }}</td>
        <td class="text-center">{{ $hasil ? $hasil->label_jenis : '-' }}</td>
        <td style="font-size:8.5px;">
          @if ($hasil && $hasil->nilai_teknis !== null)
            <strong>{{ number_format($hasil->nilai_teknis, 1) }}</strong> (Bobot {{ $hasil->bobot_teknis }}%)<br>
            CAT: {{ $hasil->nilai_teknis_cat !== null ? number_format($hasil->nilai_teknis_cat, 1) : '-' }}
            @if ($hasil->nilai_teknis_wawancara !== null) &bull; Wwc: {{ number_format($hasil->nilai_teknis_wawancara, 0) }}/5 @endif
            @if ($hasil->nilai_teknis_presentasi !== null) &bull; Pres: {{ number_format($hasil->nilai_teknis_presentasi, 0) }}/5 @endif
          @else
            -
          @endif
        </td>
        <td style="font-size:8.5px;">
          @if ($hasil && $hasil->nilai_mansoskul !== null)
            <strong>{{ number_format($hasil->nilai_mansoskul, 1) }}</strong> (Bobot {{ $hasil->bobot_mansoskul }}%)<br>
            CAT: {{ $hasil->nilai_mansoskul_cat !== null ? number_format($hasil->nilai_mansoskul_cat, 1) : '-' }}
            @if ($hasil->nilai_mansoskul_wawancara !== null) &bull; Wwc: {{ number_format($hasil->nilai_mansoskul_wawancara, 0) }}/5 @endif
            @if ($hasil->nilai_mansoskul_presentasi !== null) &bull; Pres: {{ number_format($hasil->nilai_mansoskul_presentasi, 0) }}/5 @endif
          @else
            -
          @endif
        </td>
        <td class="text-center">{{ $hasil?->nilai !== null ? number_format($hasil->nilai, 0) : '-' }}</td>
        <td class="text-center">
          @if ($hasil)
            <span class="badge-{{ $hasil->status_kelulusan === 'lulus' ? 'lulus' : ($hasil->status_kelulusan === 'tidak_lulus' ? 'tidak' : 'belum') }}">
              {{ $hasil->label_status }}
            </span>
          @else
            <span class="badge-belum">Belum Dinilai</span>
          @endif
        </td>
        <td class="text-center">
          @if ($hasil)
            <span class="{{ $hasil->status_kecurangan === 'terindikasi' ? 'badge-tidak' : 'badge-lulus' }}">{{ $hasil->label_kecurangan }}</span>
          @else
            -
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="11" class="text-center">Belum ada data peserta.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- TTD --}}
  <div class="ttd-area">
    <table>
      <tr>
        <td></td>
        <td></td>
        <td>
          Jakarta, {{ now()->format('d M Y') }}<br><br>
          Kepala Pusat Pembinaan JFT<br><br><br><br>
          <div class="ttd-line" style="width:160px; margin:0 auto;"></div>
          <br>NIP. ................................
        </td>
      </tr>
    </table>
  </div>

</body>
</html>
