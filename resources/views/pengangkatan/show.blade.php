@extends('layouts.users.master')

@section('title', 'Detail Permohonan Pengangkatan')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Detail Permohonan Pengangkatan</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pengangkatan.index') }}">Pertimbangan Pengangkatan</a></li>
                    <li class="breadcrumb-item active">{{ $permohonan->nomor_permohonan }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        {{-- Header Info --}}
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">{{ $permohonan->nomor_permohonan }}</h3>
                <div class="card-tools">
                    @if($permohonan->bisaDiedit())
                        <a href="{{ route('pengangkatan.edit', $permohonan->id) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endif
                    @can('delete pengangkatan')
                    @if($permohonan->bisaDihapus())
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete({{ $permohonan->id }})">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    @endif
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Jalur</label>
                        <p>
                            <span class="badge badge-{{ $permohonan->jalur_badge_color }} badge-lg">
                                {{ $permohonan->jalur_label }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label>Unit Kerja</label>
                        <p>{{ $permohonan->unitKerja->nama_rumahsakit }}</p>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal Permohonan</label>
                        <p>{{ $permohonan->tanggal_permohonan->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <p>
                            <span class="badge badge-{{ $permohonan->status_badge_color }} badge-lg">
                                {{ $permohonan->status_label }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline Stepper --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alur Proses</h3>
            </div>
            <div class="card-body">
                <div class="stepper stepper-vertical">
                    @foreach($statusOrder as $index => $status)
                    <div class="step {{ $index <= $currentStatusIndex ? 'active' : '' }} {{ $index < $currentStatusIndex ? 'completed' : '' }}">
                        <div class="step-circle">
                            @if($index < $currentStatusIndex)
                                <i class="fas fa-check"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <div class="step-label">
                            @match($status)
                                @case('draft')
                                    Draft
                                @case('diajukan')
                                    Diajukan
                                @case('diverifikasi')
                                    Diverifikasi
                                @case('draft_surat')
                                    Draft Surat Pertimbangan
                                @case('paraf_katim')
                                    Paraf Katim
                                @case('paraf_kabid')
                                    Paraf Kabid
                                @case('tanda_tangan')
                                    Tanda Tangan Kapus
                                @case('penomoran')
                                    Penomoran
                                @case('selesai')
                                    Selesai
                            @endmatch
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tabel Peserta --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Peserta ({{ $permohonan->peserta->count() }})</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama / NIP</th>
                                <th>Jabatan Asal → Tujuan</th>
                                <th>Jenjang</th>
                                <th>Unit Kerja Tujuan</th>
                                <th>Status Formasi</th>
                                <th>Status Ujikom</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permohonan->peserta as $index => $peserta)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $peserta->pegawai->nama_lengkap }}</strong><br>
                                    <small class="text-muted">{{ $peserta->pegawai->nip ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    {{ $peserta->jabatan_asal ?? '-' }}<br>
                                    <i class="fas fa-arrow-down text-primary"></i><br>
                                    {{ $peserta->jabatanTujuan?->nama_formasi ?? '-' }}
                                </td>
                                <td>
                                    {{ $peserta->jenjang_asal ?? '-' }} →<br>
                                    <strong>{{ $peserta->jenjang_tujuan ?? '-' }}</strong>
                                </td>
                                <td>{{ $peserta->unitKerjaTujuan?->nama_rumahsakit ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $peserta->formasi_badge_color }}">
                                        {{ $peserta->formasi_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $peserta->ujikom_badge_color }}">
                                        {{ $peserta->ujikom_label }}
                                    </span>
                                    @if($peserta->ujikomPeserta)
                                        <br><small class="text-muted">Hasil: {{ $peserta->ujikomPeserta->hasil }}</small>
                                    @endif
                                </td>
                                <td>{{ $peserta->catatan ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Catatan Verifikator --}}
        @if($permohonan->catatan_verifikator)
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">Catatan Verifikator</h3>
            </div>
            <div class="card-body">
                <p>{{ $permohonan->catatan_verifikator }}</p>
            </div>
        </div>
        @endif

        {{-- Panel Aksi Kontekstual --}}
        @can('create pengangkatan')
        @if($permohonan->bisaDiajukan())
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Ajukan Permohonan</h3>
            </div>
            <div class="card-body">
                <p>Permohonan siap diajukan untuk verifikasi.</p>
                <form method="POST" action="{{ route('pengangkatan.ajukan', $permohonan->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Ajukan Permohonan
                    </button>
                </form>
            </div>
        </div>
        @endif
        @endcan

        @can('verifikasi pengangkatan')
        @if($permohonan->bisaDiverifikasi())
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Verifikasi Permohonan</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pengangkatan.verifikasi', $permohonan->id) }}">
                    @csrf
                    <div class="form-group">
                        <label for="catatan">Catatan Verifikator</label>
                        <textarea name="catatan" id="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check"></i> Verifikasi & Lanjutkan
                    </button>
                    <button type="button" class="btn btn-danger" onclick="showTolakModal()">
                        <i class="fas fa-times"></i> Tolak & Kembalikan ke Draft
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($permohonan->bisaBuatDraftSurat())
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Buat Draft Surat Pertimbangan</h3>
            </div>
            <div class="card-body">
                <p>Permohonan sudah diverifikasi. Silakan buat draft Surat Pertimbangan Pengangkatan.</p>
                <form method="POST" action="{{ route('pengangkatan.draft-surat', $permohonan->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-file-pdf"></i> Buat Draft Surat
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($permohonan->status === 'draft_surat')
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Konfirmasi Paraf Katim</h3>
            </div>
            <div class="card-body">
                <p>Draft surat pertimbangan sudah dibuat. Konfirmasi paraf Kepala Tim?</p>
                <form method="POST" action="{{ route('pengangkatan.paraf-katim', $permohonan->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> Konfirmasi Paraf Katim
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($permohonan->status === 'paraf_katim')
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Konfirmasi Paraf Kabid</h3>
            </div>
            <div class="card-body">
                <p>Paraf Katim sudah dikonfirmasi. Konfirmasi paraf Kepala Bidang?</p>
                <form method="POST" action="{{ route('pengangkatan.paraf-kabid', $permohonan->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> Konfirmasi Paraf Kabid
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($permohonan->status === 'paraf_kabid')
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Konfirmasi Tanda Tangan</h3>
            </div>
            <div class="card-body">
                <p>Paraf Kabid sudah dikonfirmasi. Konfirmasi tanda tangan Kepala Pusat?</p>
                <form method="POST" action="{{ route('pengangkatan.ttd', $permohonan->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-signature"></i> Konfirmasi Tanda Tangan
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($permohonan->bisaDinomorikan())
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Input Nomor Surat</h3>
            </div>
            <div class="card-body">
                <p>Surat sudah ditandatangani. Silakan input nomor surat dari TU.</p>
                <a href="{{ route('pengangkatan.nomor', $permohonan->id) }}" class="btn btn-warning">
                    <i class="fas fa-hashtag"></i> Input Nomor Surat
                </a>
            </div>
        </div>
        @endif

        @if($permohonan->bisaDiselesaikan())
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Selesaikan Permohonan</h3>
            </div>
            <div class="card-body">
                <p>Nomor surat sudah diinput. Selesaikan permohonan untuk update data pegawai?</p>
                <form method="POST" action="{{ route('pengangkatan.selesaikan', $permohonan->id) }}" class="d-inline"
                      onsubmit="return confirm('Aksi ini akan mengupdate data jabatan dan unit kerja pegawai. Lanjutkan?');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Selesaikan Permohonan
                    </button>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- Tombol Kembali --}}
        <div class="row mt-3">
            <div class="col-12">
                <a href="{{ route('pengangkatan.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
                <a href="{{ route('pengangkatan.export', $permohonan->id) }}" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Modal Tolak --}}
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h4 class="modal-title">Tolak Permohonan</h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="{{ route('pengangkatan.tolak', $permohonan->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="catatan_tolak">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" id="catatan_tolak" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Tolak & Kembalikan ke Draft
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stepper {
        display: flex;
        flex-direction: column;
    }

    .step {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 15px;
        border: 2px solid #dee2e6;
    }

    .step.active .step-circle {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .step.completed .step-circle {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }

    .step-label {
        font-weight: 500;
    }

    .step.active .step-label {
        color: #007bff;
    }

    .step.completed .step-label {
        color: #28a745;
    }
</style>
@endpush

@push('scripts')
<script>
    function confirmDelete(id) {
        swal({
            title: 'Hapus Permohonan?',
            text: 'Permohonan yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
            buttons: ['Batal', 'Ya, Hapus!']
        })
        .then((willDelete) => {
            if (willDelete) {
                swal({
                    title: 'Menghapus...',
                    text: 'Mohon tunggu...',
                    icon: 'info',
                    buttons: false,
                    closeOnClickOutside: false
                });

                $.ajax({
                    url: '{{ route("pengangkatan.index") }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        swal('Berhasil!', 'Permohonan berhasil dihapus.', 'success')
                            .then(() => {
                                window.location.href = '{{ route("pengangkatan.index") }}';
                            });
                    },
                    error: function(xhr) {
                        swal('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    }

    function showTolakModal() {
        $('#modalTolak').modal('show');
    }
</script>
@endpush
