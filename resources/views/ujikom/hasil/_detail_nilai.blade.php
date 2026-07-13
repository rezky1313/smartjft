{{-- Partial rincian nilai berlapis. Butuh variabel: $hasil (UjikomHasil|null), $jadwal (UjikomJadwal) --}}
@php
  $tampilkanRincian = $hasil && $hasil->nilai_teknis !== null;
@endphp

@if ($tampilkanRincian)
  @php
    $bobotTeknisAspek    = $jadwal->getBobotAspek('teknis');
    $bobotMansoskulAspek = $jadwal->getBobotAspek('mansoskul');
  @endphp
  <div class="pl-3" style="font-size:0.83rem; border-left:3px solid #dee2e6;">
    <p class="mb-1">
      <i class="fas fa-toolbox mr-1 text-primary"></i>
      <strong>Kompetensi Teknis</strong> <span class="text-muted">(Bobot {{ $hasil->bobot_teknis }}%)</span>:
      <strong>{{ $hasil->nilai_teknis !== null ? number_format($hasil->nilai_teknis, 1) : '-' }}</strong>
    </p>
    <ul class="mb-2 pl-4">
      <li>Tes CAT: {{ $hasil->nilai_teknis_cat !== null ? number_format($hasil->nilai_teknis_cat, 1) : '-' }}
        <span class="text-muted">(bobot {{ $bobotTeknisAspek['cat'] }}%)</span></li>
      @if ($bobotTeknisAspek['wawancara'] > 0)
      <li>Wawancara: {{ $hasil->nilai_teknis_wawancara !== null ? number_format($hasil->nilai_teknis_wawancara, 0) . '/5' : '— belum dinilai —' }}
        <span class="text-muted">(bobot {{ $bobotTeknisAspek['wawancara'] }}%)</span></li>
      @endif
      @if ($bobotTeknisAspek['presentasi'] > 0)
      <li>Presentasi: {{ $hasil->nilai_teknis_presentasi !== null ? number_format($hasil->nilai_teknis_presentasi, 0) . '/5' : '— belum dinilai —' }}
        <span class="text-muted">(bobot {{ $bobotTeknisAspek['presentasi'] }}%)</span></li>
      @endif
    </ul>

    <p class="mb-1">
      <i class="fas fa-users mr-1 text-success"></i>
      <strong>Kompetensi Mansoskul</strong> <span class="text-muted">(Bobot {{ $hasil->bobot_mansoskul }}%)</span>:
      <strong>{{ $hasil->nilai_mansoskul !== null ? number_format($hasil->nilai_mansoskul, 1) : '-' }}</strong>
    </p>
    <ul class="mb-2 pl-4">
      <li>Tes CAT: {{ $hasil->nilai_mansoskul_cat !== null ? number_format($hasil->nilai_mansoskul_cat, 1) : '-' }}
        <span class="text-muted">(bobot {{ $bobotMansoskulAspek['cat'] }}%)</span></li>
      @if ($bobotMansoskulAspek['wawancara'] > 0)
      <li>Wawancara: {{ $hasil->nilai_mansoskul_wawancara !== null ? number_format($hasil->nilai_mansoskul_wawancara, 0) . '/5' : '— belum dinilai —' }}
        <span class="text-muted">(bobot {{ $bobotMansoskulAspek['wawancara'] }}%)</span></li>
      @endif
      @if ($bobotMansoskulAspek['presentasi'] > 0)
      <li>Presentasi: {{ $hasil->nilai_mansoskul_presentasi !== null ? number_format($hasil->nilai_mansoskul_presentasi, 0) . '/5' : '— belum dinilai —' }}
        <span class="text-muted">(bobot {{ $bobotMansoskulAspek['presentasi'] }}%)</span></li>
      @endif
    </ul>

    <p class="mb-0">
      <strong>Status Kecurangan:</strong>
      <span class="badge badge-{{ $hasil->badge_kecurangan }}">{{ $hasil->label_kecurangan }}</span>
    </p>
  </div>
@else
  <p class="text-muted mb-0 pl-3" style="font-size:0.83rem;">
    @if ($hasil && $hasil->nilai !== null)
      <i class="fas fa-info-circle mr-1"></i>Ujian satu sesi (tanpa rincian Teknis/Mansoskul terpisah). Nilai akhir: <strong>{{ number_format($hasil->nilai, 1) }}</strong>.
      Status Kecurangan: <span class="badge badge-{{ $hasil->badge_kecurangan }}">{{ $hasil->label_kecurangan }}</span>
    @else
      <i class="fas fa-info-circle mr-1"></i>Belum ada rincian nilai untuk peserta ini.
    @endif
  </p>
@endif
