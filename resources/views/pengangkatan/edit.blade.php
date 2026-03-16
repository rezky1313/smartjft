@extends('layouts.users.master')

@section('title', 'Edit Permohonan Pengangkatan')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit Permohonan Pengangkatan</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pengangkatan.index') }}">Pertimbangan Pengangkatan</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pengangkatan.show', $permohonan->id) }}">{{ $permohonan->nomor_permohonan }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if($permohonan->status !== 'draft')
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Peringatan:</strong> Hanya permohonan dengan status <strong>Draft</strong> yang bisa diedit.
            </div>
        @endif

        <form method="POST" action="{{ route('pengangkatan.update', $permohonan->id) }}" enctype="multipart/form-data" id="form-permohonan">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Kolom Kiri: Informasi Permohonan --}}
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Informasi Permohonan</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nomor Permohonan</label>
                                <input type="text" class="form-control" value="{{ $permohonan->nomor_permohonan }}" readonly>
                            </div>

                            <div class="form-group">
                                <label for="jalur">Jalur Pengangkatan <span class="text-danger">*</span></label>
                                <select name="jalur" id="jalur" class="form-control select2" required>
                                    <option value="">-- Pilih Jalur --</option>
                                    @foreach($jalurs as $key => $label)
                                        <option value="{{ $key }}" {{ old('jalur', $permohonan->jalur) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('jalur') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="unit_kerja_id">Unit Kerja <span class="text-danger">*</span></label>
                                <select name="unit_kerja_id" id="unit_kerja_id" class="form-control select2" required>
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    @foreach($unitKerja as $uk)
                                        <option value="{{ $uk->no_rs }}" {{ old('unit_kerja_id', $permohonan->unit_kerja_id) == $uk->no_rs ? 'selected' : '' }}>{{ $uk->nama_rumahsakit }}</option>
                                    @endforeach
                                </select>
                                @error('unit_kerja_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="tanggal_permohonan">Tanggal Permohonan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_permohonan" id="tanggal_permohonan"
                                       class="form-control" value="{{ old('tanggal_permohonan', $permohonan->tanggal_permohonan->format('Y-m-d')) }}" required>
                                @error('tanggal_permohonan') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="file_surat_permohonan">Upload Surat Permohonan (PDF)</label>
                                <div class="custom-file">
                                    <input type="file" name="file_surat_permohonan" id="file_surat_permohonan"
                                           class="custom-file-input" accept=".pdf">
                                    <label class="custom-file-label" for="file_surat_permohonan">Pilih file PDF...</label>
                                </div>
                                @if($permohonan->file_surat_permohonan)
                                    <small class="form-text text-muted">
                                        File saat ini: <a href="{{ asset('storage/' . $permohonan->file_surat_permohonan) }}" target="_blank">Lihat</a>
                                    </small>
                                @endif
                                <small class="form-text text-muted">Maksimal 2MB. Format: PDF</small>
                                @error('file_surat_permohonan') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Daftar Peserta --}}
                <div class="col-md-8">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Daftar Peserta</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-primary" onclick="tambahPeserta()">
                                    <i class="fas fa-plus"></i> Tambah Peserta
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered mb-0" id="tabel-peserta">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th style="width: 250px;">Pegawai</th>
                                            <th>Jabatan Asal</th>
                                            <th>Jabatan Tujuan</th>
                                            <th>Jenjang Tujuan</th>
                                            <th>Unit Kerja Tujuan</th>
                                            <th style="width: 150px;">Validasi</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="peserta-body">
                                        @foreach($permohonan->peserta as $index => $peserta)
                                        <tr class="peserta-row">
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <select name="peserta[{{ $index }}][pegawai_id]" class="form-control form-control-sm pegawai-select select2"
                                                        data-index="{{ $index }}" required onchange="loadPegawaiData({{ $index }})">
                                                    <option value="">-- Pilih Pegawai --</option>
                                                    <option value="{{ $peserta->pegawai_id }}" selected>{{ $peserta->pegawai->nama_lengkap }} - {{ $peserta->pegawai->nip ?? 'N/A' }}</option>
                                                </select>
                                                <input type="hidden" name="peserta[{{ $index }}][jabatan_asal]" class="jabatan-asal" value="{{ $peserta->jabatan_asal }}">
                                                <input type="hidden" name="peserta[{{ $index }}][jenjang_asal]" class="jenjang-asal" value="{{ $peserta->jenjang_asal }}">
                                                <input type="hidden" name="peserta[{{ $index }}][unit_kerja_asal]" class="unit-kerja-asal" value="{{ $peserta->unit_kerja_asal }}">
                                            </td>
                                            <td>{{ $peserta->jabatan_asal }} ({{ $peserta->jenjang_asal ?? '-' }})</td>
                                            <td>
                                                <select name="peserta[{{ $index }}][jabatan_tujuan_id]" class="form-control form-control-sm jabatan-tujuan-select select2"
                                                        data-index="{{ $index }}" required onchange="loadJabatanData({{ $index }})">
                                                    <option value="">-- Pilih Jabatan --</option>
                                                    @foreach($formasi as $f)
                                                        <option value="{{ $f->id }}" data-jenjang="{{ $f->jenjang?->nama_jenjang }}" data-unit="{{ $f->unit_kerja_id }}"
                                                                {{ $peserta->jabatan_tujuan_id == $f->id ? 'selected' : '' }}>
                                                            {{ $f->nama_formasi }} ({{ $f->jenjang?->nama_jenjang ?? '-' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="peserta[{{ $index }}][jenjang_tujuan]" class="form-control form-control-sm jenjang-tujuan-select select2"
                                                        data-index="{{ $index }}" required>
                                                    <option value="">-- Pilih --</option>
                                                    @foreach(['Pemula', 'Terampil', 'Mahir', 'Penyelia', 'Ahli Pertama', 'Ahli Muda', 'Ahli Madya', 'Ahli Utama'] as $j)
                                                        <option value="{{ $j }}" {{ $peserta->jenjang_tujuan === $j ? 'selected' : '' }}>{{ $j }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="peserta[{{ $index }}][unit_kerja_tujuan_id]" class="form-control form-control-sm unit-kerja-tujuan-select select2"
                                                        data-index="{{ $index }}" required>
                                                    <option value="">-- Pilih Unit Kerja --</option>
                                                    @foreach($unitKerja as $uk)
                                                        <option value="{{ $uk->no_rs }}" {{ $peserta->unit_kerja_tujuan_id == $uk->no_rs ? 'selected' : '' }}>{{ $uk->nama_rumahsakit }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="validasi-badges" data-index="{{ $index }}">
                                                    <span class="badge badge-{{ $peserta->formasi_badge_color }}" title="Formasi">
                                                        {{ $peserta->formasi_label }}
                                                    </span>
                                                    <span class="badge badge-{{ $peserta->ujikom_badge_color }}" title="Ujikom">
                                                        {{ $peserta->ujikom_label }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="hapusPeserta(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('pengangkatan.show', $permohonan->id) }}" class="btn btn-danger float-right">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<template id="template-peserta">
    <tr class="peserta-row">
        <td class="text-center peserta-no"></td>
        <td>
            <select name="peserta[{index}][pegawai_id]" class="form-control form-control-sm pegawai-select select2"
                    data-index="{index}" required onchange="loadPegawaiData({index})">
                <option value="">-- Pilih Pegawai --</option>
            </select>
            <input type="hidden" name="peserta[{index}][jabatan_asal]" class="jabatan-asal">
            <input type="hidden" name="peserta[{index}][jenjang_asal]" class="jenjang-asal">
            <input type="hidden" name="peserta[{index}][unit_kerja_asal]" class="unit-kerja-asal">
        </td>
        <td class="jabatan-asal-display">-</td>
        <td>
            <select name="peserta[{index}][jabatan_tujuan_id]" class="form-control form-control-sm jabatan-tujuan-select select2"
                    data-index="{index}" required onchange="loadJabatanData({index})">
                <option value="">-- Pilih Jabatan --</option>
                @foreach($formasi as $f)
                    <option value="{{ $f->id }}" data-jenjang="{{ $f->jenjang?->nama_jenjang }}" data-unit="{{ $f->unit_kerja_id }}">
                        {{ $f->nama_formasi }} ({{ $f->jenjang?->nama_jenjang ?? '-' }})
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="peserta[{index}][jenjang_tujuan]" class="form-control form-control-sm jenjang-tujuan-select select2"
                    data-index="{index}" required>
                <option value="">-- Pilih --</option>
                <option value="Pemula">Pemula</option>
                <option value="Terampil">Terampil</option>
                <option value="Mahir">Mahir</option>
                <option value="Penyelia">Penyelia</option>
                <option value="Ahli Pertama">Ahli Pertama</option>
                <option value="Ahli Muda">Ahli Muda</option>
                <option value="Ahli Madya">Ahli Madya</option>
                <option value="Ahli Utama">Ahli Utama</option>
            </select>
        </td>
        <td>
            <select name="peserta[{index}][unit_kerja_tujuan_id]" class="form-control form-control-sm unit-kerja-tujuan-select select2"
                    data-index="{index}" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach($unitKerja as $uk)
                    <option value="{{ $uk->no_rs }}">{{ $uk->nama_rumahsakit }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="validasi-badges" data-index="{index}">
                <span class="badge badge-secondary">Pending</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger" onclick="hapusPeserta(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
    let pesertaCount = {{ $permohonan->peserta->count() }};
    let pegawaiList = [];
    let currentUnitKerjaId = '{{ $permohonan->unit_kerja_id }}';

    $(document).ready(function() {
        // Init select2 biasa untuk dropdown non-pegawai
        $('.select2:not(.pegawai-select)').select2({ theme: 'bootstrap4', width: '100%' });

        // Init select2 biasa untuk pegawai select yang sudah ada (sudah terisi dari server)
        $('.pegawai-select').select2({ theme: 'bootstrap4', width: '100%' });

        // Event listener saat unit kerja berubah
        $('#unit_kerja_id').on('change', function() {
            currentUnitKerjaId = $(this).val();

            // Untuk edit, tidak perlu refresh karena pegawai sudah fix
            // User bisa ganti pegawai jika mau, tapi dropdown tidak filter by unit kerja
            console.log('Unit kerja diubah ke:', currentUnitKerjaId);
        });

        // Custom file input
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });
    });

    function initSelect2WithAjax(index) {
        // Fungsi ini hanya untuk baris baru yang ditambahkan
        // Untuk pegawai yang sudah ada, pakai Select2 biasa
        $(`.pegawai-select[data-index="${index}"]`).select2({
            theme: 'bootstrap4',
            width: '100%',
            minimumInputLength: 0,
            allowClear: true,
            placeholder: '-- Pilih Pegawai --',
            ajax: {
                url: '{{ route("pengangkatan.pegawai-list") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        unit_kerja_id: currentUnitKerjaId || '',
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            templateResult: function(result) {
                if (!result.id) return result.text;
                return $('<div>')
                    .text(result.text)
                    .attr('title', result.text);
            },
            templateSelection: function(selection) {
                if (!selection.id) return '-- Pilih Pegawai --';
                return selection.text || selection.nama;
            }
        });
    }

    function loadPegawaiList(unitKerjaId = '') {
        // Fungsi ini tidak lagi diperlukan karena menggunakan AJAX Select2
        // Tapi tetap ada untuk backward compatibility
        $.ajax({
            url: '{{ route("pengangkatan.pegawai-list") }}',
            method: 'GET',
            data: {
                unit_kerja_id: unitKerjaId
            },
            success: function(response) {
                pegawaiList = response.results;
                console.log('Pegawai loaded:', pegawaiList.length, 'items',
                           unitKerjaId ? 'for Unit Kerja: ' + unitKerjaId : '(all units)');
            },
            error: function(xhr, status, error) {
                console.error('Error loading pegawai:', xhr.responseText);
            }
        });
    }

    function refreshAllPegawaiDropdowns() {
        // Untuk halaman edit, tidak perlu refresh dropdown
        console.log('Unit kerja diubah, tapi dropdown pegawai tidak di-refresh (edit mode)');
    }

    function tambahPeserta() {
        pesertaCount++;
        let template = $('#template-peserta').html();
        template = template.replace(/{index}/g, pesertaCount);

        $('#peserta-body').append(template);
        updateNomorPeserta();

        // Init select2 untuk dropdown non-pegawai
        $('#peserta-body tr:last .select2:not(.pegawai-select)').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Init pegawai select dengan AJAX untuk baris baru
        initSelect2WithAjax(pesertaCount);
    }

    function hapusPeserta(btn) {
        $(btn).closest('tr').remove();
        updateNomorPeserta();
    }

    function updateNomorPeserta() {
        $('#peserta-body tr').each(function(index) {
            $(this).find('.peserta-no').text(index + 1);
        });
    }

    function loadPegawaiData(index) {
        let pegawaiId = $(`.pegawai-select[data-index="${index}"]`).val();

        if (!pegawaiId) {
            $(`.jabatan-asal-display[data-parent="${index}"]`).text('-');
            return;
        }

        // Find pegawai data
        let pegawai = pegawaiList.find(p => p.id == pegawaiId);
        if (pegawai) {
            let row = $(`.pegawai-select[data-index="${index}"]`).closest('tr');
            row.find('.jabatan-asal-display').text(`${pegawai.jabatan || '-'} (${pegawai.jenjang || '-'})`);
            row.find('.jabatan-asal').val(pegawai.jabatan || '');
            row.find('.jenjang-asal').val(pegawai.jenjang || '');
            row.find('.unit-kerja-asal').val(pegawai.unit_kerja_id || '');
        }

        validasiPeserta(index);
    }

    function loadJabatanData(index) {
        let jabatanId = $(`.jabatan-tujuan-select[data-index="${index}"]`).val();
        let selectedOption = $(`.jabatan-tujuan-select[data-index="${index}"] option:selected`);

        if (jabatanId) {
            let jenjang = selectedOption.data('jenjang');
            let unitKerja = selectedOption.data('unit');

            $(`.jenjang-tujuan-select[data-index="${index}"]`).val(jenjang).trigger('change');
            $(`.unit-kerja-tujuan-select[data-index="${index}"]`).val(unitKerja).trigger('change');
        }

        validasiPeserta(index);
    }

    function validasiPeserta(index) {
        let pegawaiId = $(`.pegawai-select[data-index="${index}"]`).val();
        let jabatanTujuanId = $(`.jabatan-tujuan-select[data-index="${index}"]`).val();
        let unitKerjaTujuanId = $(`.unit-kerja-tujuan-select[data-index="${index}"]`).val();

        if (!pegawaiId || !jabatanTujuanId || !unitKerjaTujuanId) {
            $(`.validasi-badges[data-index="${index}"]`).html('<span class="badge badge-secondary">Pending</span>');
            return;
        }

        $.ajax({
            url: '{{ route("pengangkatan.validasi-peserta") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pegawai_id: pegawaiId,
                jabatan_tujuan_id: jabatanTujuanId,
                unit_kerja_tujuan_id: unitKerjaTujuanId
            },
            success: function(response) {
                let formasiBadge = response.formasi.tersedia
                    ? `<span class="badge badge-success" title="${response.formasi.pesan}">Formasi: OK</span>`
                    : `<span class="badge badge-danger" title="${response.formasi.pesan}">Formasi: Penuh</span>`;

                let ujikomBadge = response.ujikom.memenuhi
                    ? `<span class="badge badge-success" title="${response.ujikom.pesan}">Ujikom: OK</span>`
                    : `<span class="badge badge-warning" title="${response.ujikom.pesan}">Ujikom: Belum</span>`;

                $(`.validasi-badges[data-index="${index}"]`).html(`
                    <div class="d-flex flex-column gap-1">
                        ${formasiBadge}
                        ${ujikomBadge}
                    </div>
                `);
            },
            error: function() {
                $(`.validasi-badges[data-index="${index}"]`).html('<span class="badge badge-danger">Error Validasi</span>');
            }
        });
    }
</script>
@endpush
