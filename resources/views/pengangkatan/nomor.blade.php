@extends('layouts.users.master')

@section('title', 'Input Nomor Surat')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Input Nomor Surat</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pengangkatan.index') }}">Pertimbangan Pengangkatan</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pengangkatan.show', $permohonan->id) }}">{{ $permohonan->nomor_permohonan }}</a></li>
                    <li class="breadcrumb-item active">Input Nomor Surat</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Input Nomor Surat Pertimbangan</h3>
                    </div>
                    <form method="POST" action="{{ route('pengangkatan.nomor', $permohonan->id) }}">
                        @csrf

                        <div class="card-body">
                            {{-- Info Permohonan --}}
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Nomor Permohonan:</strong><br>
                                        {{ $permohonan->nomor_permohonan }}
                                    </div>
                                    <div class="col-6">
                                        <strong>Jalur:</strong><br>
                                        {{ $permohonan->jalur_label }}
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Unit Kerja:</strong><br>
                                        {{ $permohonan->unitKerja->nama_rumahsakit }}
                                    </div>
                                    <div class="col-6">
                                        <strong>Tanggal:</strong><br>
                                        {{ $permohonan->tanggal_permohonan->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="nomor_surat">Nomor Surat <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="nomor_surat"
                                       id="nomor_surat"
                                       class="form-control"
                                       placeholder="Contoh: AH.102/B.5/DRJD/2026"
                                       required
                                       autofocus>
                                <small class="form-text text-muted">
                                    Masukkan nomor surat sesuai format yang diberikan oleh TU.
                                </small>
                                @error('nomor_surat') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label>Surat Pertimbangan</label>
                                @if($permohonan->surat->first())
                                    <p>
                                        <a href="{{ asset('storage/' . $permohonan->surat->first()->file_path) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-file-pdf"></i> Lihat Draft Surat
                                        </a>
                                    </p>
                                @else
                                    <p class="text-muted">Draft surat belum dibuat.</p>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Simpan Nomor Surat
                            </button>
                            <a href="{{ route('pengangkatan.show', $permohonan->id) }}" class="btn btn-secondary float-right">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>

                <div class="alert alert-success mt-3">
                    <h5><i class="fas fa-info-circle"></i> Informasi</h5>
                    <p>Setelah nomor surat disimpan, status permohonan akan berubah menjadi "Penomoran". Selanjutnya, admin dapat menyelesaikan permohonan untuk mengupdate data pegawai.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#nomor_surat').focus();
    });
</script>
@endpush
