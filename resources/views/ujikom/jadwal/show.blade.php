@extends('layouts.users.master')

@section('title', $jadwal->judul . ' — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    {{-- Header Card --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
          <i class="fas fa-calendar-check mr-2"></i>Detail Jadwal Uji Kompetensi
        </h3>
        <a href="{{ route('ujikom.jadwal.index') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <h4 class="font-weight-bold">{{ $jadwal->judul }}</h4>
            @if ($jadwal->deskripsi)
            <p class="text-muted">{{ $jadwal->deskripsi }}</p>
            @endif
          </div>
          <div class="col-md-4 text-md-right">
            <span class="badge badge-{{ $jadwal->badge_status }} px-3 py-2" style="font-size:0.9rem;">
              {{ $jadwal->status_label }}
            </span>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-3">
            <p class="mb-1 text-muted small font-weight-bold">TANGGAL MULAI</p>
            <p class="mb-0"><i class="fas fa-calendar-day mr-1 text-primary"></i>{{ $jadwal->tanggal_mulai->format('d F Y') }}</p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small font-weight-bold">TANGGAL SELESAI</p>
            <p class="mb-0"><i class="fas fa-calendar-day mr-1 text-primary"></i>{{ $jadwal->tanggal_selesai->format('d F Y') }}</p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small font-weight-bold">TEMPAT</p>
            <p class="mb-0"><i class="fas fa-map-marker-alt mr-1 text-danger"></i>{{ $jadwal->tempat }}</p>
          </div>
          <div class="col-md-3">
            <p class="mb-1 text-muted small font-weight-bold">KUOTA</p>
            <p class="mb-0"><i class="fas fa-users mr-1 text-success"></i>{{ $jadwal->kuota }} Peserta</p>
          </div>
        </div>

        @if ($jadwal->pembuat)
        <div class="row mt-2">
          <div class="col-md-6">
            <p class="mb-1 text-muted small font-weight-bold">DIBUAT OLEH</p>
            <p class="mb-0"><i class="fas fa-user mr-1 text-muted"></i>{{ $jadwal->pembuat->name }}</p>
          </div>
          <div class="col-md-6">
            <p class="mb-1 text-muted small font-weight-bold">TANGGAL DIBUAT</p>
            <p class="mb-0"><i class="fas fa-clock mr-1 text-muted"></i>{{ $jadwal->created_at->format('d F Y, H:i') }} WIB</p>
          </div>
        </div>
        @endif

        {{-- Tombol Aksi Admin --}}
        @role('admin|super_admin')
        <hr>
        <div class="d-flex flex-wrap gap-2">
          @if ($jadwal->status === 'draft')
          <a href="{{ route('ujikom.jadwal.edit', $jadwal->id) }}" class="btn btn-warning btn-sm mr-2">
            <i class="fas fa-pencil-alt mr-1"></i> Edit
          </a>
          <form action="{{ route('ujikom.jadwal.publish', $jadwal->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Publikasikan jadwal ini sekarang?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm mr-2">
              <i class="fas fa-globe mr-1"></i> Publikasikan
            </button>
          </form>
          <form action="{{ route('ujikom.jadwal.destroy', $jadwal->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Hapus jadwal ini secara permanen?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
              <i class="fas fa-trash mr-1"></i> Hapus
            </button>
          </form>
          @endif
          @if ($jadwal->status === 'published')
          <form action="{{ route('ujikom.jadwal.selesaikan', $jadwal->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Tandai jadwal ini sebagai selesai?')">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">
              <i class="fas fa-check-double mr-1"></i> Tandai Selesai
            </button>
          </form>
          @endif
        </div>
        @endrole
      </div>
    </div>

    {{-- Persyaratan --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">
          <i class="fas fa-list-ul mr-2"></i>Persyaratan Peserta
        </h3>
      </div>
      <div class="card-body">
        @if ($jadwal->persyaratan->isEmpty())
          <p class="text-muted mb-0"><i class="fas fa-info-circle mr-1"></i>Belum ada persyaratan yang ditentukan.</p>
        @else
          <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
              <thead class="thead-light">
                <tr>
                  <th width="40">No</th>
                  <th>Nama Syarat</th>
                  <th>Keterangan</th>
                  <th width="150">File Contoh</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($jadwal->persyaratan as $p)
                <tr>
                  <td class="text-center">{{ $p->urutan }}</td>
                  <td>{{ $p->nama_syarat }}</td>
                  <td class="text-muted">{{ $p->keterangan ?: '-' }}</td>
                  <td>
                    @if ($p->file_contoh)
                      <a href="{{ asset('storage/' . $p->file_contoh) }}" target="_blank" class="btn btn-outline-primary btn-xs">
                        <i class="fas fa-download mr-1"></i> Download
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    {{-- Peserta Terdaftar --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
          <i class="fas fa-users mr-2"></i>Peserta Terdaftar
          <span class="badge badge-{{ $totalPeserta > 0 ? 'primary' : 'secondary' }} ml-2">{{ $totalPeserta }}</span>
        </h3>
        @role('operator|admin|super_admin')
        <a href="{{ route('ujikom.permohonan.create') }}?jadwal_id={{ $jadwal->id }}" class="btn btn-primary btn-sm">
          <i class="fas fa-plus mr-1"></i> Daftar Ujikom
        </a>
        @endrole
      </div>
      <div class="card-body">
        @if ($totalPeserta === 0)
          <div class="text-center py-4 text-muted">
            <i class="fas fa-user-clock fa-2x mb-2 d-block"></i>
            <p class="mb-0">Belum ada peserta yang terverifikasi untuk jadwal ini.</p>
          </div>
        @else
          @foreach ($pendaftaranList as $daftar)
          <div class="mb-3">
            <div class="d-flex align-items-center mb-2">
              <strong class="mr-2">{{ $daftar->unitKerja?->nama_rumahsakit ?? '-' }}</strong>
              <span class="badge badge-{{ $daftar->badge_status }} mr-2">{{ $daftar->label_status }}</span>
              <small class="text-muted">{{ $daftar->kode_pendaftaran }}</small>
              <a href="{{ route('ujikom.permohonan.show', $daftar->id) }}" class="btn btn-xs btn-outline-info ml-auto">
                <i class="fas fa-eye mr-1"></i>Lihat
              </a>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead class="thead-light">
                  <tr>
                    <th width="40">No</th>
                    <th>NIP</th>
                    <th>Nama Pegawai</th>
                    <th>Jabatan / Jenjang</th>
                    <th>Status Formasi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($daftar->peserta as $i => $peserta)
                  <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td><small>{{ $peserta->pegawai?->nip ?? '-' }}</small></td>
                    <td>{{ $peserta->pegawai?->nama ?? '-' }}</td>
                    <td><small>{{ $peserta->pegawai?->jabatan?->nama_jabatan ?? '-' }} / {{ $peserta->pegawai?->jenjang?->jenjang ?? '-' }}</small></td>
                    <td>
                      <span class="badge badge-{{ $peserta->status_formasi === 'tersedia' ? 'success' : 'warning' }}">
                        {{ $peserta->status_formasi === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' }}
                      </span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          @endforeach
        @endif
      </div>
    </div>

  </div>
</div>
@endsection
