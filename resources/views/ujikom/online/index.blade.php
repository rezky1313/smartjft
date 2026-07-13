@extends('layouts.users.master')

@section('title', 'Uji Kompetensi Online — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         TAMPILAN ADMIN
    ══════════════════════════════════════════════════════════════════════════ --}}
    @hasanyrole('admin|super_admin')
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-laptop-code mr-2"></i>Manajemen Sesi Ujian</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Jadwal Ujikom</th>
                <th width="110" class="text-center">Tanggal</th>
                <th width="100" class="text-center">Status Jadwal</th>
                <th width="80" class="text-center">Peserta</th>
                <th width="80" class="text-center">Sedang</th>
                <th width="80" class="text-center">Selesai</th>
                <th width="220" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($jadwals as $i => $j)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $j->judul }}</strong>
                  @if ($j->jenis_ujian)
                  <br><small class="text-muted">{{ $j->jenis_ujian_label }}</small>
                  @endif
                </td>
                <td class="text-center"><small>{{ $j->tanggal_mulai?->format('d M Y') }}</small></td>
                <td class="text-center">
                  <span class="badge badge-{{ $j->badge_status }}">{{ $j->status_label }}</span>
                </td>
                <td class="text-center"><strong>{{ $j->stat_sesi['total'] ?? 0 }}</strong></td>
                <td class="text-center">
                  @if (($j->stat_sesi['berlangsung'] ?? 0) > 0)
                  <span class="badge badge-primary">{{ $j->stat_sesi['berlangsung'] }}</span>
                  @else
                  <span class="text-muted">0</span>
                  @endif
                </td>
                <td class="text-center">
                  @if (($j->stat_sesi['selesai'] ?? 0) > 0)
                  <span class="badge badge-success">{{ $j->stat_sesi['selesai'] }}</span>
                  @else
                  <span class="text-muted">0</span>
                  @endif
                </td>
                <td class="text-center">
                  @if (!$j->sesi_dibuka)
                    <form action="{{ route('ujikom-online.admin.buka-sesi', $j->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Buka sesi ujian untuk semua peserta jadwal ini?')">
                      @csrf
                      <button class="btn btn-xs btn-success"><i class="fas fa-play mr-1"></i> Buka Sesi</button>
                    </form>
                  @else
                    <a href="{{ route('ujikom-online.admin.monitoring', $j->id) }}" class="btn btn-xs btn-outline-info">
                      <i class="fas fa-tv mr-1"></i> Monitoring
                    </a>
                    @if (($j->stat_sesi['berlangsung'] ?? 0) > 0 || ($j->stat_sesi['menunggu'] ?? 0) > 0)
                    <form action="{{ route('ujikom-online.admin.tutup-sesi', $j->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Tutup sesi? Semua sesi aktif akan di-submit otomatis.')">
                      @csrf
                      <button class="btn btn-xs btn-outline-danger"><i class="fas fa-stop mr-1"></i> Tutup Sesi</button>
                    </form>
                    @endif
                  @endif
                  @if ($j->teknis_wawancara_aktif || $j->teknis_presentasi_aktif || $j->mansoskul_wawancara_aktif || $j->mansoskul_presentasi_aktif)
                  <a href="{{ route('ujikom-online.admin.nilai-manual.form', $j->id) }}" class="btn btn-xs btn-outline-warning" title="Input Nilai Wawancara/Presentasi">
                    <i class="fas fa-star-half-alt mr-1"></i> Nilai Manual
                  </a>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jadwal ujikom.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endhasanyrole

    {{-- ══════════════════════════════════════════════════════════════════════
         TAMPILAN PESERTA
    ══════════════════════════════════════════════════════════════════════════ --}}
    @hasanyrole('pemangku|admin_unit|operator')
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-laptop-code mr-2"></i>Ujian Kompetensi Saya</h3>
      </div>
      <div class="card-body">
        @if ($jadwals->isEmpty())
          <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
            <p>Belum ada jadwal ujian yang tersedia untuk Anda.</p>
            <small>Pastikan pendaftaran ujikom Anda sudah diverifikasi oleh Pusbin.</small>
          </div>
        @else
          <div class="row">
            @foreach ($jadwals as $j)
            <div class="col-md-6 mb-3">
              <div class="card border mb-0">
                <div class="card-body">
                  <h5 class="mb-1">{{ $j->judul }}</h5>
                  <p class="text-muted small mb-2">
                    <i class="far fa-calendar mr-1"></i>{{ $j->tanggal_mulai?->format('d M Y') }}
                    @if ($j->tempat)
                    &nbsp;&bull;&nbsp;<i class="fas fa-map-marker-alt mr-1"></i>{{ $j->tempat }}
                    @endif
                  </p>

                  @if ($j->mode_sesi_taksonomi)
                    {{-- Mode 2 Sesi CAT: Teknis lalu Mansoskul --}}
                    @php
                      $keduaSelesai = $j->sesi_mansoskul && in_array($j->sesi_mansoskul->status_sesi, ['selesai', 'timeout']);
                      $sesiAktif = null;
                      if ($j->sesi_teknis && $j->sesi_teknis->status_sesi === 'berlangsung') $sesiAktif = $j->sesi_teknis;
                      elseif ($j->sesi_mansoskul && $j->sesi_mansoskul->status_sesi === 'berlangsung') $sesiAktif = $j->sesi_mansoskul;
                    @endphp
                    <div class="mb-2" style="font-size:0.78rem;">
                      <span class="badge badge-{{ $j->sesi_teknis?->badge_status_sesi ?? 'secondary' }} mr-1">
                        Sesi 1 Teknis: {{ $j->sesi_teknis?->label_status_sesi ?? 'Belum Mulai' }}
                      </span>
                      <span class="badge badge-{{ $j->sesi_mansoskul?->badge_status_sesi ?? 'secondary' }}">
                        Sesi 2 Mansoskul: {{ $j->sesi_mansoskul?->label_status_sesi ?? 'Belum Mulai' }}
                      </span>
                    </div>

                    @if ($keduaSelesai)
                      <a href="{{ route('ujikom-online.hasil-gabungan', ['jadwalId' => $j->id, 'pesertaId' => $j->peserta_record->id]) }}" class="btn btn-sm btn-outline-info btn-block">
                        <i class="fas fa-eye mr-1"></i> Lihat Hasil Gabungan
                      </a>
                    @elseif ($sesiAktif)
                      <div class="alert alert-primary py-2 mb-2">
                        <i class="fas fa-spinner fa-spin mr-1"></i> {{ $sesiAktif->label_jenis_sesi }} sedang berlangsung.
                      </div>
                      <a href="{{ route('ujikom-online.ujian', $sesiAktif->id) }}" class="btn btn-primary btn-block">
                        <i class="fas fa-arrow-right mr-1"></i> Lanjutkan Ujian
                      </a>
                    @else
                      <form action="{{ route('ujikom-online.mulai', $j->id) }}" method="POST"
                            onsubmit="return confirm('Mulai/lanjutkan ujian sekarang? Timer akan langsung berjalan setelah Anda klik OK.')">
                        @csrf
                        <button class="btn btn-success btn-block">
                          <i class="fas fa-play mr-1"></i> {{ !$j->sesi_teknis ? 'Mulai Ujian (Sesi 1: Teknis)' : 'Mulai Sesi 2: Mansoskul' }}
                        </button>
                      </form>
                    @endif

                  @else
                  @php $sesi = $j->sesi_peserta; @endphp

                  @if (!$j->sesi_dibuka)
                    {{-- Sesi belum dibuka admin --}}
                    <span class="badge badge-secondary"><i class="fas fa-clock mr-1"></i> Menunggu Admin Membuka Sesi</span>

                  @elseif (!$sesi || $sesi->status_sesi === 'menunggu')
                    {{-- Sesi dibuka, peserta belum mulai --}}
                    <div class="alert alert-success py-2 mb-2">
                      <i class="fas fa-check-circle mr-1"></i> Sesi ujian sudah dibuka. Anda dapat memulai ujian.
                    </div>
                    <form action="{{ route('ujikom-online.mulai', $j->id) }}" method="POST"
                          onsubmit="return confirm('Mulai ujian sekarang? Timer akan langsung berjalan setelah Anda klik OK.')">
                      @csrf
                      <button class="btn btn-success btn-block">
                        <i class="fas fa-play mr-1"></i> Mulai Ujian
                      </button>
                    </form>

                  @elseif ($sesi->status_sesi === 'berlangsung')
                    {{-- Sedang berlangsung --}}
                    <div class="alert alert-primary py-2 mb-2">
                      <i class="fas fa-spinner fa-spin mr-1"></i> Ujian sedang berlangsung.
                    </div>
                    <a href="{{ route('ujikom-online.ujian', $sesi->id) }}" class="btn btn-primary btn-block">
                      <i class="fas fa-arrow-right mr-1"></i> Lanjutkan Ujian
                    </a>

                  @else
                    {{-- Selesai / timeout --}}
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <span class="badge badge-{{ $sesi->status_lulus === 'lulus' ? 'success' : 'danger' }} mr-1">
                          {{ $sesi->status_lulus === 'lulus' ? 'LULUS' : 'TIDAK LULUS' }}
                        </span>
                        <span class="text-muted small">Nilai: <strong>{{ $sesi->nilai_akhir }}</strong></span>
                      </div>
                      <a href="{{ route('ujikom-online.hasil', $sesi->id) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-eye mr-1"></i> Lihat Hasil
                      </a>
                    </div>
                  @endif
                  @endif
                </div>
              </div>
            </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
    @endhasanyrole

  </div>
</div>
@endsection
