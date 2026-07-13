@extends('layouts.users.master')

@section('title', 'Detail Permohonan Pengangkatan — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">
    <div class="d-flex align-items-center mb-3">
      <a href="{{ route('pengangkatan.index') }}" class="btn btn-sm btn-light mr-3">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h3 class="mb-0">Detail Permohonan Pengangkatan</h3>
    </div>

    {{-- ==================== SECTION 1: INFO PERMOHONAN ==================== --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Informasi Permohonan</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
              <tr><td width="160" class="text-muted">Kode Permohonan</td><td>: <strong>{{ $permohonan->kode_permohonan }}</strong></td></tr>
              <tr><td class="text-muted">Unit Kerja</td><td>: {{ $permohonan->unitKerja?->nama_unit_kerja ?? '-' }}</td></tr>
              <tr><td class="text-muted">Tanggal Permohonan</td><td>: {{ $permohonan->tanggal_permohonan?->format('d M Y') }}</td></tr>
              <tr><td class="text-muted">Diajukan Oleh</td><td>: {{ $permohonan->pengaju?->name ?? '-' }}</td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
              <tr>
                <td width="160" class="text-muted">Status</td>
                <td>: <span class="badge badge-{{ $permohonan->badge_status }}" style="font-size:0.85rem;">{{ $permohonan->label_status }}</span></td>
              </tr>
              <tr><td class="text-muted">Jumlah Peserta</td><td>: {{ $permohonan->kandidat->count() }} orang</td></tr>
              @if ($permohonan->file_surat_permohonan)
              <tr><td class="text-muted">Surat Permohonan</td><td>: <a href="{{ asset('storage/' . $permohonan->file_surat_permohonan) }}" target="_blank" class="btn btn-xs btn-outline-primary"><i class="fas fa-file-pdf mr-1"></i>Lihat PDF</a></td></tr>
              @endif
            </table>
          </div>
        </div>

        @if ($permohonan->catatan_pusbin)
        <div class="alert alert-{{ $permohonan->status === 'ditolak' ? 'danger' : 'info' }} mt-3 mb-0 py-2">
          <strong>Catatan Pusbin:</strong> {{ $permohonan->catatan_pusbin }}
        </div>
        @endif
      </div>
    </div>

    {{-- ==================== TIMELINE STEPPER (4 LANGKAH) ==================== --}}
    <div class="card">
      <div class="card-body py-3">
        @php
          $steps = ['draft' => 'Draft', 'diajukan' => 'Diajukan', 'menunggu_ttd' => 'Menunggu TTD', 'selesai' => 'Selesai'];
          $statusOrder = ['draft', 'diajukan', 'menunggu_ttd', 'selesai'];
          $currentIdx = array_search($permohonan->status, $statusOrder);
          if ($currentIdx === false) $currentIdx = -1;
          if ($permohonan->status === 'ditolak') $currentIdx = -1;
        @endphp
        <div class="d-flex justify-content-between position-relative" style="z-index:1;">
          @foreach ($steps as $key => $label)
            @php
              $idx = array_search($key, $statusOrder);
              $done = $idx <= $currentIdx && $permohonan->status !== 'ditolak';
              $active = $idx === $currentIdx && $permohonan->status !== 'ditolak';
            @endphp
            <div class="text-center" style="flex:1;">
              <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center
                {{ $done ? 'bg-success text-white' : ($permohonan->status === 'ditolak' ? 'bg-danger text-white' : 'bg-light text-muted') }}"
                style="width:36px; height:36px; font-size:0.85rem; {{ $active ? 'box-shadow:0 0 0 3px rgba(40,167,69,.4);' : '' }}">
                @if ($done && !$active)
                  <i class="fas fa-check"></i>
                @elseif ($permohonan->status === 'ditolak' && $key === 'diajukan')
                  <i class="fas fa-times"></i>
                @else
                  {{ $idx + 1 }}
                @endif
              </div>
              <small class="d-block mt-1 {{ $active ? 'font-weight-bold' : 'text-muted' }}">{{ $label }}</small>
            </div>
          @endforeach
        </div>
        @if ($permohonan->status === 'ditolak')
          <div class="text-center mt-2">
            <span class="badge badge-danger" style="font-size:0.85rem;"><i class="fas fa-ban mr-1"></i>Permohonan Ditolak</span>
          </div>
        @endif
      </div>
    </div>

    {{-- ==================== SECTION 2: DAFTAR PESERTA YANG DIUSULKAN ==================== --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>Daftar Peserta yang Diusulkan</h3>
      </div>
      <div class="card-body">
        @if ($kandidatPerJabatan->isEmpty())
          <div class="text-center text-muted py-4">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
            Belum ada peserta yang diusulkan.
          </div>
        @else
          @foreach ($kandidatPerJabatan as $jabatanId => $kandidatList)
            @php
              $formasiInfo = $kandidatList->first()?->jabatanTujuan;
              $namaJabatan = $formasiInfo?->nama_formasi ?? '-';
              $namaJenjang = $formasiInfo?->jenjang?->nama_jenjang ?? '-';
              $formasiTersedia = $kandidatList->first()?->formasi_tersedia ?? 0;
            @endphp
            <div class="mb-4">
              <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0">
                  <i class="fas fa-briefcase text-primary mr-1"></i>
                  {{ $namaJabatan }} / {{ $namaJenjang }}
                </h6>
                <span class="badge badge-info ml-2">Formasi Tersedia saat diusulkan: {{ $formasiTersedia }}</span>
                <span class="badge badge-success ml-1">Diusulkan: {{ $kandidatList->count() }}</span>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered table-sm" style="font-size:0.85rem;">
                  <thead class="thead-light">
                    <tr>
                      <th>Nama Pegawai</th>
                      <th width="110">NIP</th>
                      <th width="120">Jabatan Asal</th>
                      <th width="110">Jenjang Asal</th>
                      <th width="90" class="text-center">Nilai Ujikom</th>
                      <th width="130" class="text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($kandidatList as $k)
                    <tr>
                      <td>{{ $k->pegawai?->nama_lengkap ?? '-' }}</td>
                      <td>{{ $k->pegawai?->nip ?? '-' }}</td>
                      <td>{{ $k->jabatan_asal }}</td>
                      <td>{{ $k->jenjang_asal }}</td>
                      <td class="text-center"><strong>{{ $k->nilai_ujikom !== null ? number_format($k->nilai_ujikom, 1) : '-' }}</strong></td>
                      <td class="text-center">
                        <span class="badge badge-{{ $k->badge_status_kandidat }}">
                          {{ $k->label_status_kandidat }}
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

    {{-- ==================== SECTION 3: PANEL AKSI ==================== --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-cogs mr-2"></i>Aksi</h3>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2">

          {{-- DRAFT: Admin unit bisa ajukan --}}
          @if ($permohonan->status === 'draft')
            @hasanyrole('admin_unit|admin|super_admin')
            <form action="{{ route('pengangkatan.ajukan', $permohonan->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Ajukan permohonan ini ke Pusbin?')">
              @csrf
              <button class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Ajukan ke Pusbin</button>
            </form>
            <a href="{{ route('pengangkatan.edit', $permohonan->id) }}" class="btn btn-warning">
              <i class="fas fa-edit mr-1"></i>Edit
            </a>
            <form action="{{ route('pengangkatan.destroy', $permohonan->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Hapus permohonan ini?')">
              @csrf @method('DELETE')
              <button class="btn btn-danger"><i class="fas fa-trash mr-1"></i>Hapus</button>
            </form>
            @endhasanyrole
          @endif

          {{-- DIAJUKAN: Admin Pusbin generate surat atau tolak --}}
          @if ($permohonan->status === 'diajukan')
            @hasanyrole('admin|super_admin')
            <a href="{{ route('pengangkatan.surat', $permohonan->id) }}" class="btn btn-success" target="_blank">
              <i class="fas fa-file-signature mr-1"></i>Generate Surat Rekomendasi
            </a>
            <button class="btn btn-danger" data-toggle="modal" data-target="#modalTolak">
              <i class="fas fa-times mr-1"></i>Tolak
            </button>
            @endhasanyrole
          @endif

          {{-- MENUNGGU TTD: Konfirmasi TTD + Download ulang surat --}}
          @if ($permohonan->status === 'menunggu_ttd')
            @hasanyrole('admin|super_admin')
            <form action="{{ route('pengangkatan.konfirmasi-ttd', $permohonan->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Konfirmasi surat sudah ditandatangani? Data formasi pegawai akan diperbarui.')">
              @csrf
              <button class="btn btn-success"><i class="fas fa-signature mr-1"></i>Konfirmasi Surat Sudah Ditandatangani</button>
            </form>
            @endhasanyrole
            <a href="{{ route('pengangkatan.surat', $permohonan->id) }}" class="btn btn-outline-danger" target="_blank">
              <i class="fas fa-file-pdf mr-1"></i>Download Surat Rekomendasi
            </a>
          @endif

          {{-- SELESAI: Download surat saja --}}
          @if ($permohonan->status === 'selesai')
            <a href="{{ route('pengangkatan.surat', $permohonan->id) }}" class="btn btn-outline-danger" target="_blank">
              <i class="fas fa-file-pdf mr-1"></i>Download Surat Rekomendasi
            </a>
          @endif

        </div>
      </div>
    </div>

  </div>
</div>

{{-- Modal Tolak --}}
@if ($permohonan->status === 'diajukan')
<div class="modal fade" id="modalTolak" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('pengangkatan.tolak', $permohonan->id) }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fas fa-times-circle mr-1"></i>Tolak Permohonan</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Catatan Penolakan <span class="text-danger">*</span></label>
            <textarea name="catatan_pusbin" class="form-control" rows="4" required placeholder="Alasan penolakan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak Permohonan</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endif
@endsection
