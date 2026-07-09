@extends('layouts.users.master')

@section('title', $pendaftaran->kode_pendaftaran . ' — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    {{-- ── Header Info ─────────────────────────────────────────────────────── --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
          <i class="fas fa-clipboard-list mr-2"></i>Detail Pendaftaran Uji Kompetensi
        </h3>
        <a href="{{ route('ujikom.permohonan.index') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-8">
            <h5 class="font-weight-bold mb-1">{{ $pendaftaran->jadwal?->judul ?? '-' }}</h5>
            <p class="text-muted mb-0" style="font-size:0.9rem;">
              Kode: <strong>{{ $pendaftaran->kode_pendaftaran }}</strong>
              &nbsp;|&nbsp; Jenis: <strong>{{ $pendaftaran->jenis_pendaftaran === 'batch' ? 'Batch' : 'Mandiri' }}</strong>
              &nbsp;|&nbsp; Dibuat oleh: <strong>{{ $pendaftaran->pembuat?->name ?? '-' }}</strong>
            </p>
          </div>
          <div class="col-md-4 text-md-right">
            @php
              $extraStyle = '';
              if ($pendaftaran->status === 'diajukan_pusbin') $extraStyle = 'background:#6f42c1;color:#fff;border:none;';
              elseif ($pendaftaran->status === 'diverifikasi_pusbin') $extraStyle = 'background:#20c997;color:#fff;border:none;';
            @endphp
            <span class="badge badge-{{ $pendaftaran->badge_status }} px-3 py-2" style="{{ $extraStyle }}; font-size:0.9rem;">
              {{ $pendaftaran->label_status }}
            </span>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <p class="mb-1 text-muted small font-weight-bold">JADWAL</p>
            <p class="mb-0"><i class="fas fa-calendar mr-1 text-primary"></i>
              {{ $pendaftaran->jadwal?->tanggal_mulai?->format('d F Y') ?? '-' }}
              @if($pendaftaran->jadwal?->tanggal_selesai)
                — {{ $pendaftaran->jadwal->tanggal_selesai->format('d F Y') }}
              @endif
            </p>
          </div>
          <div class="col-md-4">
            <p class="mb-1 text-muted small font-weight-bold">TEMPAT</p>
            <p class="mb-0"><i class="fas fa-map-marker-alt mr-1 text-danger"></i>{{ $pendaftaran->jadwal?->tempat ?? '-' }}</p>
          </div>
          <div class="col-md-4">
            <p class="mb-1 text-muted small font-weight-bold">UNIT KERJA</p>
            <p class="mb-0"><i class="fas fa-building mr-1 text-success"></i>{{ $pendaftaran->unitKerja?->nama_unit_kerja ?? '-' }}</p>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Timeline Status ──────────────────────────────────────────────────── --}}
    <div class="card">
      <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-stream mr-2"></i>Alur Status</h3></div>
      <div class="card-body py-3">
        @php
          $steps = [
            1 => ['label' => 'Draft',               'sub' => ''],
            2 => ['label' => 'Diajukan',             'sub' => 'Menunggu Admin Unit'],
            3 => ['label' => 'Verifikasi Admin Unit','sub' => 'Menunggu Pusbin'],
            4 => ['label' => 'Verifikasi Pusbin',    'sub' => ''],
            5 => ['label' => 'Selesai',              'sub' => ''],
          ];
          $stepMap = [
            'draft'                   => 1,
            'diajukan_admin_unit'     => 2,
            'diverifikasi_admin_unit' => 3,
            'diajukan_pusbin'         => 3,
            'diverifikasi_pusbin'     => 4,
            'selesai'                 => 5,
            'ditolak_admin_unit'      => 2,
            'ditolak_pusbin'          => 4,
          ];
          $isRejected  = in_array($pendaftaran->status, ['ditolak_admin_unit', 'ditolak_pusbin']);
          $activeStep  = $stepMap[$pendaftaran->status] ?? 1;
          $rejectedStep = $isRejected ? $activeStep : null;
        @endphp

        @if ($isRejected)
        <div class="alert alert-danger py-2 mb-3">
          <i class="fas fa-times-circle mr-1"></i>
          <strong>{{ $pendaftaran->label_status }}</strong>
          @if ($pendaftaran->status === 'ditolak_admin_unit' && $pendaftaran->catatan_admin_unit)
            — {{ $pendaftaran->catatan_admin_unit }}
          @elseif ($pendaftaran->status === 'ditolak_pusbin' && $pendaftaran->catatan_pusbin)
            — {{ $pendaftaran->catatan_pusbin }}
          @endif
        </div>
        @endif

        <div class="d-flex align-items-center justify-content-between">
          @foreach ($steps as $sIdx => $step)
          @php
            $isRejectedStep = ($rejectedStep === $sIdx);
            $isCurrent      = (!$isRejected && $activeStep === $sIdx);
            $isDone         = (!$isRejected && $sIdx < $activeStep) || ($pendaftaran->status === 'selesai' && $sIdx === 5);
            $circleColor    = $isRejectedStep ? '#dc3545' : ($isCurrent ? '#007bff' : ($isDone ? '#28a745' : '#dee2e6'));
            $textColor      = $isRejectedStep ? '#dc3545' : ($isCurrent ? '#007bff' : ($isDone ? '#28a745' : '#aaa'));
            $fontWeight     = ($isCurrent || $isRejectedStep) ? '700' : '400';
          @endphp
          <div class="text-center flex-fill" style="font-size:0.78rem;">
            <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center"
                 style="width:36px;height:36px;background:{{ $circleColor }};color:{{ ($isCurrent||$isDone||$isRejectedStep)?'#fff':'#6c757d' }};">
              @if ($isRejectedStep)
                <i class="fas fa-times" style="font-size:0.85rem;"></i>
              @elseif ($isDone && !$isCurrent)
                <i class="fas fa-check" style="font-size:0.85rem;"></i>
              @else
                {{ $sIdx }}
              @endif
            </div>
            <div class="mt-1" style="line-height:1.2;color:{{ $textColor }};font-weight:{{ $fontWeight }};">
              {{ $step['label'] }}
              @if ($isCurrent && $step['sub'])
              <div style="font-size:0.7rem;font-weight:400;color:#6c757d;margin-top:2px;">{{ $step['sub'] }}</div>
              @endif
            </div>
          </div>
          @if ($sIdx < 5)
          <div class="flex-fill" style="height:2px;background:#dee2e6;max-width:40px;margin-bottom:20px;"></div>
          @endif
          @endforeach
        </div>

        {{-- Catatan verifikasi --}}
        @if ($pendaftaran->catatan_admin_unit && !$isRejected)
        <div class="alert alert-info py-2 mt-3 mb-0" style="font-size:0.85rem;">
          <strong><i class="fas fa-comment-alt mr-1"></i>Catatan Admin Unit:</strong> {{ $pendaftaran->catatan_admin_unit }}
        </div>
        @endif
        @if ($pendaftaran->catatan_pusbin && !$isRejected)
        <div class="alert alert-info py-2 mt-2 mb-0" style="font-size:0.85rem;">
          <strong><i class="fas fa-comment-alt mr-1"></i>Catatan Pusbin:</strong> {{ $pendaftaran->catatan_pusbin }}
        </div>
        @endif
      </div>
    </div>

    {{-- ── Tabel Peserta ────────────────────────────────────────────────────── --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>Daftar Peserta ({{ $pendaftaran->peserta->count() }})</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th width="40">No</th>
                <th>Nama</th>
                <th>NIP</th>
                <th>Jabatan Saat Ini</th>
                <th>Jenjang Saat Ini</th>
                <th>Jabatan Tujuan</th>
                <th>Jenjang Tujuan</th>
                <th>Status Formasi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($pendaftaran->peserta as $i => $p)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $p->pegawai?->nama_lengkap ?? '-' }}</td>
                <td><small>{{ $p->pegawai?->nip ?? '-' }}</small></td>
                <td><small>{{ $p->pegawai?->formasi?->nama_formasi ?? '-' }}</small></td>
                <td><small>{{ $p->pegawai?->formasi?->jenjang?->nama_jenjang ?? '-' }}</small></td>
                <td><small>{{ $p->jabatanTujuan?->nama_formasi ?? '-' }}</small></td>
                <td><small>{{ $p->jenjang_tujuan ?? '-' }}</small></td>
                <td>
                  <span class="badge badge-{{ $p->status_formasi === 'tersedia' ? 'success' : 'danger' }}">
                    {{ $p->status_formasi === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' }}
                    @if (!is_null($p->sisa_formasi))
                      ({{ $p->sisa_formasi }})
                    @endif
                  </span>
                </td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted">Tidak ada peserta.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ── Berkas Per Peserta ────────────────────────────────────────────────── --}}
    @if ($pendaftaran->peserta->isNotEmpty())
    <div class="card">
      <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-paperclip mr-2"></i>Berkas Persyaratan</h3></div>
      <div class="card-body">
        @foreach ($pendaftaran->peserta as $p)
        <h6 class="font-weight-bold">{{ $p->pegawai?->nama_lengkap ?? '-' }} <small class="text-muted">({{ $p->pegawai?->nip ?? '-' }})</small></h6>
        @php $berkasPeserta = $berkasPerPegawai->get($p->pegawai_id, collect()); @endphp
        @if ($berkasPeserta->isEmpty())
          <p class="text-muted small ml-3 mb-3">— Belum ada berkas yang diunggah.</p>
        @else
          <table class="table table-sm table-bordered mb-3" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th>Nama Berkas / Persyaratan</th>
                <th>File</th>
                <th width="130">Status Verifikasi</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($berkasPeserta as $b)
              <tr>
                <td>{{ $b->nama_berkas }}</td>
                <td>
                  <a href="{{ asset('storage/' . $b->file_path) }}" target="_blank" class="btn btn-xs btn-outline-primary">
                    <i class="fas fa-download mr-1"></i>Download
                  </a>
                </td>
                <td>
                  @if ($b->status_verifikasi === 'diterima') <span class="badge badge-success">Diterima</span>
                  @elseif ($b->status_verifikasi === 'ditolak') <span class="badge badge-danger">Ditolak</span>
                  @else <span class="badge badge-secondary">Belum Diverifikasi</span>
                  @endif
                </td>
                <td><small class="text-muted">{{ $b->catatan ?? '-' }}</small></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        @endif
        @endforeach
      </div>
    </div>
    @endif

    {{-- ── Panel Aksi Kontekstual ────────────────────────────────────────────── --}}
    <div class="card">
      <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-bolt mr-2"></i>Aksi</h3></div>
      <div class="card-body">

        {{-- Draft: Pembuat bisa Edit & Ajukan --}}
        @if ($pendaftaran->status === 'draft' && ($pendaftaran->dibuat_oleh === Auth::id() || Auth::user()->hasRole(['admin','super_admin'])))
        <a href="{{ route('ujikom.permohonan.edit', $pendaftaran->id) }}" class="btn btn-warning mr-2">
          <i class="fas fa-pencil-alt mr-1"></i> Edit
        </a>
        <form action="{{ route('ujikom.permohonan.ajukan', $pendaftaran->id) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Ajukan permohonan ini ke Admin Unit?')">
          @csrf
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Ajukan ke Admin Unit</button>
        </form>
        @endif

        {{-- Diajukan Admin Unit: hanya Admin Unit & Super Admin --}}
        @if ($pendaftaran->status === 'diajukan_admin_unit')
        @hasanyrole('super_admin|admin_unit')

        {{-- Form verifikasi berkas --}}
        @if ($pendaftaran->berkas->isNotEmpty())
        <form action="{{ route('ujikom.permohonan.verifikasi.berkas', $pendaftaran->id) }}" method="POST" class="mb-3">
          @csrf
          <h6 class="font-weight-bold">Verifikasi Berkas</h6>
          @php $berkasIndex = 0; @endphp
          @foreach ($pendaftaran->peserta as $p)
          @php $berkasPeserta = $berkasPerPegawai->get($p->pegawai_id, collect()); @endphp
          @if ($berkasPeserta->isNotEmpty())
          <p class="mb-1 font-weight-bold small">{{ $p->pegawai?->nama_lengkap ?? '-' }} <span class="text-muted">({{ $p->pegawai?->nip ?? '-' }})</span></p>
          @foreach ($berkasPeserta as $b)
          <div class="row mb-1 align-items-center ml-2">
            <div class="col-md-4 small">{{ $b->nama_berkas }}</div>
            <input type="hidden" name="berkas[{{ $berkasIndex }}][id]" value="{{ $b->id }}">
            <div class="col-md-3">
              <select name="berkas[{{ $berkasIndex }}][status_verifikasi]" class="form-control form-control-sm">
                <option value="belum"    {{ $b->status_verifikasi === 'belum'    ? 'selected' : '' }}>Belum Dicek</option>
                <option value="diterima" {{ $b->status_verifikasi === 'diterima' ? 'selected' : '' }}>✅ Diterima</option>
                <option value="ditolak"  {{ $b->status_verifikasi === 'ditolak'  ? 'selected' : '' }}>❌ Ditolak</option>
              </select>
            </div>
            <div class="col-md-4">
              <input type="text" name="berkas[{{ $berkasIndex }}][catatan]" class="form-control form-control-sm"
                     placeholder="Catatan..." value="{{ $b->catatan }}">
            </div>
          </div>
          @php $berkasIndex++; @endphp
          @endforeach
          @endif
          @endforeach
          <button type="submit" class="btn btn-sm btn-info mt-2">
            <i class="fas fa-save mr-1"></i> Simpan Status Berkas
          </button>
        </form>
        <hr>
        @endif

        {{-- Catatan verifikasi --}}
        <div class="form-group mb-3">
          <label class="font-weight-bold">Catatan Verifikasi <small class="text-muted">(opsional)</small></label>
          <textarea id="catatanVerifikasiAdmin" class="form-control" rows="2"
                    placeholder="Tuliskan catatan jika ada..."></textarea>
        </div>

        <form action="{{ route('ujikom.permohonan.verifikasi.admin', $pendaftaran->id) }}" method="POST" class="d-inline mr-2"
              id="formVerifikasiAdmin" onsubmit="return confirm('Verifikasi berkas dan kirim permohonan ini ke Pusbin?')">
          @csrf
          <input type="hidden" name="catatan_admin_unit" id="hiddenCatatanAdmin" value="">
          <button type="submit" class="btn btn-success">
            <i class="fas fa-paper-plane mr-1"></i> Verifikasi & Kirim ke Pusbin
          </button>
        </form>

        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalTolakAdmin">
          <i class="fas fa-times mr-1"></i> Tolak Permohonan
        </button>
        @endhasanyrole
        @endif

        {{-- Diajukan Pusbin: Admin Pusbin verifikasi final atau tolak --}}
        @if ($pendaftaran->status === 'diajukan_pusbin')
        @hasanyrole('super_admin|admin')

        {{-- Catatan Pusbin --}}
        <div class="form-group mb-3">
          <label class="font-weight-bold">Catatan Pusbin <small class="text-muted">(opsional)</small></label>
          <textarea id="catatanVerifikasiPusbin" class="form-control" rows="2"
                    placeholder="Tuliskan catatan jika ada..."></textarea>
        </div>

        <form action="{{ route('ujikom.permohonan.verifikasi.pusbin', $pendaftaran->id) }}" method="POST" class="d-inline mr-2"
              id="formVerifikasiPusbin" onsubmit="return confirm('Verifikasi dan selesaikan permohonan ini?')">
          @csrf
          <input type="hidden" name="catatan_pusbin" id="hiddenCatatanPusbin" value="">
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check-double mr-1"></i> Verifikasi & Selesaikan
          </button>
        </form>
        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalTolakPusbin">
          <i class="fas fa-times mr-1"></i> Tolak
        </button>
        @endhasanyrole
        @endif

        @if (in_array($pendaftaran->status, ['selesai', 'diverifikasi_pusbin']))
        <span class="text-success"><i class="fas fa-check-circle mr-1"></i> Permohonan ini telah selesai diproses.</span>
        @endif

      </div>
    </div>

  </div>
</div>

{{-- ── Modal Tolak Admin Unit ────────────────────────────────────────────── --}}
<div class="modal fade" id="modalTolakAdmin" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('ujikom.permohonan.tolak.admin', $pendaftaran->id) }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>Tolak Permohonan</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea name="catatan_admin_unit" class="form-control" rows="3" required
                      placeholder="Tuliskan alasan penolakan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak Permohonan</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- ── Modal Tolak Pusbin ────────────────────────────────────────────────── --}}
<div class="modal fade" id="modalTolakPusbin" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('ujikom.permohonan.tolak.pusbin', $pendaftaran->id) }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>Tolak oleh Pusbin</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea name="catatan_pusbin" class="form-control" rows="3" required
                      placeholder="Tuliskan alasan penolakan Pusbin..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak Permohonan</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
// Sync catatan textarea → hidden input sebelum form submit (Admin Unit)
$('#formVerifikasiAdmin').on('submit', function() {
  $('#hiddenCatatanAdmin').val($('#catatanVerifikasiAdmin').val());
});

// Sync catatan textarea → hidden input sebelum form submit (Pusbin)
$('#formVerifikasiPusbin').on('submit', function() {
  $('#hiddenCatatanPusbin').val($('#catatanVerifikasiPusbin').val());
});
</script>
@endpush
