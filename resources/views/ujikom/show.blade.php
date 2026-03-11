@extends('layouts.users.master')
@section('title', 'Detail Permohonan Uji Kompetensi')
@section('isi')

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Detail Permohonan Uji Kompetensi</h4>
    <div>
      <a href="{{ route('ujikom.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <a href="{{ route('ujikom.export', $permohonan->id) }}" class="btn btn-info">
        <i class="fas fa-file-pdf"></i> Export PDF
      </a>
    </div>
  </div>

  {{-- Alert Messages --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif
  @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">
      {{ session('warning') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  @endif

  {{-- Info Permohonan --}}
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-file-alt"></i> Informasi Permohonan</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-borderless">
            <tr>
              <td width="200"><strong>Nomor Permohonan</strong></td>
              <td>: {{ $permohonan->nomor_permohonan }}</td>
            </tr>
            <tr>
              <td><strong>Unit Kerja</strong></td>
              <td>: {{ $permohonan->unitKerja->nama_rumahsakit ?? '-' }}</td>
            </tr>
            <tr>
              <td><strong>Provinsi</strong></td>
              <td>: {{ $permohonan->unitKerja->regency->province->name ?? '-' }}</td>
            </tr>
            <tr>
              <td><strong>Kab/Kota</strong></td>
              <td>: {{ $permohonan->unitKerja->regency->type }} {{ $permohonan->unitKerja->regency->name ?? '-' }}</td>
            </tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-borderless">
            <tr>
              <td width="200"><strong>Tanggal Permohonan</strong></td>
              <td>: {{ $permohonan->tanggal_permohonan->format('d/m/Y') }}</td>
            </tr>
            <tr>
              <td><strong>Status</strong></td>
              <td>: <span class="badge bg-{{ $permohonan->status_badge_color }} badge-lg">{{ $permohonan->status_label }}</span></td>
            </tr>
            @if($permohonan->tanggal_jadwal)
              <tr>
                <td><strong>Tanggal Jadwal</strong></td>
                <td>: {{ $permohonan->tanggal_jadwal->format('d/m/Y') }}</td>
              </tr>
            @endif
            @if($permohonan->tempat_ujikom)
              <tr>
                <td><strong>Tempat Uji Kompetensi</strong></td>
                <td>: {{ $permohonan->tempat_ujikom }}</td>
              </tr>
            @endif
            @if($permohonan->file_surat_permohonan)
              <tr>
                <td><strong>Surat Permohonan</strong></td>
                <td>: <a href="{{ asset('storage/' . $permohonan->file_surat_permohonan) }}" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download"></i> Download</a></td>
              </tr>
            @endif
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Timeline Status --}}
  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-tasks"></i> Status Permohonan</h5>
    </div>
    <div class="card-body">
      <div class="stepper stepper-horizontal">
        @foreach($statusOrder as $index => $status)
          @php
            $isCompleted = $index < $currentStatusIndex;
            $isActive = $index == $currentStatusIndex;
            $isPending = $index > $currentStatusIndex;

            $label = match($status) {
              'draft' => 'Draft',
              'diajukan' => 'Diajukan',
              'diverifikasi' => 'Diverifikasi',
              'terjadwal' => 'Terjadwal',
              'selesai_uji' => 'Selesai Uji',
              'hasil_diinput' => 'Hasil Diinput',
              'selesai' => 'Selesai',
              default => 'Unknown',
            };
          @endphp

          <div class="step @if($isCompleted) completed @elseif($isActive) active @else pending @endif">
            <div class="step-circle">
              @if($isCompleted)
                <i class="fas fa-check-circle"></i>
              @elseif($isActive)
                <i class="fas fa-circle"></i>
              @else
                <i class="far fa-circle"></i>
              @endif
            </div>
            <div class="step-title">{{ $label }}</div>
            @if($isActive || ($isCompleted && $index == $currentStatusIndex - 1))
              <div class="step-lead">
                @if($status === 'draft' && $permohonan->catatan_verifikator)
                  <small class="text-muted">{{ $permohonan->catatan_verifikator }}</small>
                @endif
              </div>
            @endif
          </div>
        @endforeach
      </div>

      <style>
        .stepper-horizontal {
          display: flex;
          justify-content: space-between;
          position: relative;
          padding: 20px 0;
        }
        .stepper-horizontal::before {
          content: '';
          position: absolute;
          top: 40px;
          left: 50px;
          right: 50px;
          height: 2px;
          background: #e9ecef;
          z-index: 0;
        }
        .step {
          flex: 1;
          text-align: center;
          position: relative;
          z-index: 1;
        }
        .step-circle {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          background: white;
          border: 2px solid #dee2e6;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 10px;
          font-size: 16px;
        }
        .step.completed .step-circle {
          border-color: #28a745;
          color: #28a745;
        }
        .step.active .step-circle {
          border-color: #007bff;
          background: #007bff;
          color: white;
        }
        .step.pending .step-circle {
          border-color: #dee2e6;
          color: #6c757d;
        }
        .step-title {
          font-weight: 600;
          font-size: 12px;
        }
        .step.completed .step-title {
          color: #28a745;
        }
        .step.active .step-title {
          color: #007bff;
        }
        .step.pending .step-title {
          color: #6c757d;
        }
        .step-lead {
          margin-top: 5px;
        }
        .badge-lg {
          font-size: 14px;
          padding: 8px 12px;
        }
      </style>
    </div>
  </div>

  {{-- Catatan Verifikator --}}
  @if($permohonan->catatan_verifikator)
    <div class="card mb-3">
      <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Catatan Verifikator</h5>
      </div>
      <div class="card-body">
        {{ $permohonan->catatan_verifikator }}
      </div>
    </div>
  @endif

  {{-- Panel Aksi sesuai Status --}}
  <div class="card mb-3">
    <div class="card-header bg-info text-white">
      <h5 class="mb-0"><i class="fas fa-cogs"></i> Aksi</h5>
    </div>
    <div class="card-body">
      {{-- Draft --}}
      @if($permohonan->status === 'draft')
        @can('create ujikom')
          @if($permohonan->bisaDiajukan())
            <form method="POST" action="{{ route('ujikom.ajukan', $permohonan->id) }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-primary" onclick="return confirm('Ajukan permohonan ini?')">
                <i class="fas fa-paper-plane"></i> Ajukan Permohonan
              </button>
            </form>
          @endif
        @endcan
        @can('edit ujikom')
          <a href="{{ route('ujikom.edit', $permohonan->id) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit Permohonan
          </a>
        @endcan
        @can('delete ujikom')
          <button type="button" class="btn btn-danger" onclick="confirmDelete({{ $permohonan->id }})">
            <i class="fas fa-trash"></i> Hapus Permohonan
          </button>
        @endcan
      @endif

      {{-- Diajukan --}}
      @if($permohonan->status === 'diajukan')
        @can('verifikasi ujikom')
          <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalVerifikasi">
            <i class="fas fa-check"></i> Verifikasi
          </button>
          <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalTolak">
            <i class="fas fa-times"></i> Tolak
          </button>
        @endcan
      @endif

      {{-- Diverifikasi --}}
      @if($permohonan->status === 'diverifikasi')
        @can('verifikasi ujikom')
          <a href="{{ route('ujikom.jadwal', $permohonan->id) }}" class="btn btn-primary">
            <i class="fas fa-calendar-plus"></i> Input Jadwal
          </a>
          @if($permohonan->beritaAcara->where('jenis', 'verifikasi')->first())
            <a href="{{ asset('storage/' . $permohonan->beritaAcara->where('jenis', 'verifikasi')->first()->file_path) }}"
               target="_blank" class="btn btn-info">
              <i class="fas fa-file-download"></i> Download BA Verifikasi
            </a>
          @endif
        @endcan
      @endif

      {{-- Terjadwal --}}
      @if($permohonan->status === 'terjadwal')
        @can('verifikasi ujikom')
          <form method="POST" action="{{ route('ujikom.konfirmasi', $permohonan->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('Konfirmasi bahwa uji kompetensi telah dilaksanakan?')">
              <i class="fas fa-check-double"></i> Konfirmasi Selesai Uji
            </button>
          </form>
        @endcan
      @endif

      {{-- Selesai Uji --}}
      @if($permohonan->status === 'selesai_uji')
        @can('input hasil ujikom')
          <a href="{{ route('ujikom.hasil', $permohonan->id) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Input Hasil
          </a>
        @endcan
      @endif

      {{-- Hasil Diinput --}}
      @if($permohonan->status === 'hasil_diinput')
        @can('verifikasi ujikom')
          <a href="{{ route('ujikom.ba', [$permohonan->id, 'hasil']) }}"
             class="btn btn-success"
             onclick="return confirm('Generate Berita Acara Hasil dan menyelesaikan permohonan?')">
            <i class="fas fa-file-pdf"></i> Generate Berita Acara Hasil & Selesai
          </a>
        @endcan
      @endif

      {{-- Selesai --}}
      @if($permohonan->status === 'selesai')
        @foreach($permohonan->beritaAcara as $ba)
          <a href="{{ asset('storage/' . $ba->file_path) }}" target="_blank" class="btn btn-info">
            <i class="fas fa-download"></i> Download {{ $ba->jenis_label }}
          </a>
        @endforeach
      @endif
    </div>
  </div>

  {{-- Daftar Peserta --}}
  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-users"></i> Daftar Peserta ({{ $permohonan->peserta->count() }})</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th width="50">No</th>
              <th>Nama Pegawai</th>
              <th>NIP</th>
              <th>Jabatan</th>
              <th>Jenjang</th>
              <th>Hasil</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            @foreach($permohonan->peserta as $i => $peserta)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $peserta->pegawai->nama_lengkap }}</td>
                <td>{{ $peserta->pegawai->nip ?? '-' }}</td>
                <td>{{ $peserta->pegawai->formasi->nama_formasi ?? '-' }}</td>
                <td>{{ $peserta->pegawai->formasi->jenjang->nama_jenjang ?? '-' }}</td>
                <td>
                  @if($permohonan->status === 'selesai' || $permohonan->status === 'hasil_diinput')
                    <span class="badge bg-{{ $peserta->hasil_badge_color }}">
                      {{ $peserta->hasil_label }}
                    </span>
                  @else
                    <span class="badge bg-secondary">Belum</span>
                  @endif
                </td>
                <td>{{ $peserta->catatan_hasil ?? '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Modal Verifikasi --}}
<div class="modal fade" id="modalVerifikasi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('ujikom.verifikasi', $permohonan->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Verifikasi Permohonan</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Catatan (Opsional)</label>
            <textarea name="catatan" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Verifikasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Tolak --}}
<div class="modal fade" id="modalTolak" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('ujikom.tolak', $permohonan->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tolak Permohonan</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            Permohonan akan dikembalikan ke status draft dan unit kerja dapat melakukan perbaikan.
          </div>
          <div class="form-group">
            <label>Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea name="catatan" class="form-control" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
function confirmDelete(id) {
  swal({
    title: 'Apakah Anda yakin?',
    text: 'Permohonan akan dihapus secara permanen!',
    icon: 'warning',
    buttons: true,
    dangerMode: true,
  })
  .then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: '{{ route('ujikom.index') }}/' + id,
        type: 'DELETE',
        data: {
          _token: '{{ csrf_token() }}'
        },
        success: function(response) {
          window.location.href = '{{ route('ujikom.index') }}';
        },
        error: function(xhr) {
          var error = xhr.responseJSON?.error || 'Terjadi kesalahan.';
          swal('Error!', error, 'error');
        }
      });
    }
  });
}
</script>
@endpush
@endsection
