@extends('layouts.users.master')

@section('title', 'Buat Permohonan Pengangkatan')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Buat Permohonan Pengangkatan</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pengangkatan.index') }}">Pertimbangan Pengangkatan</a></li>
                    <li class="breadcrumb-item active">Buat Permohonan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form method="POST" action="{{ route('pengangkatan.store') }}" enctype="multipart/form-data" id="form-permohonan">
            @csrf

            <div class="row">
                {{-- Kolom Kiri: Informasi Permohonan --}}
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Informasi Permohonan</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="jalur">Jalur Pengangkatan <span class="text-danger">*</span></label>
                                <select name="jalur" id="jalur" class="form-control select2" required>
                                    <option value="">-- Pilih Jalur --</option>
                                    @foreach($jalurs as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('jalur') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="unit_kerja_id">Unit Kerja <span class="text-danger">*</span></label>
                                <select name="unit_kerja_id" id="unit_kerja_id" class="form-control select2" required>
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    @foreach($unitKerja as $uk)
                                        <option value="{{ $uk->no_rs }}">{{ $uk->nama_rumahsakit }}</option>
                                    @endforeach
                                </select>
                                @error('unit_kerja_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="tanggal_permohonan">Tanggal Permohonan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_permohonan" id="tanggal_permohonan"
                                       class="form-control" value="{{ old('tanggal_permohonan', now()->format('Y-m-d')) }}" required>
                                @error('tanggal_permohonan') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="file_surat_permohonan">Upload Surat Permohonan (PDF) <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" name="file_surat_permohonan" id="file_surat_permohonan"
                                           class="custom-file-input" accept=".pdf" required>
                                    <label class="custom-file-label" for="file_surat_permohonan">Pilih file PDF...</label>
                                </div>
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
                        </div>
                        <div class="card-body">
                            {{-- Dropdown Pilih Pegawai di atas tabel --}}
                            <div class="row mb-3">
                                <div class="col-md-10">
                                    <label class="form-label">Pilih Pegawai</label>
                                    <select id="dropdown-pilih-pegawai" class="form-control select2" style="width: 100%;">
                                        <option value="">-- Pilih Unit Kerja Dahulu --</option>
                                    </select>
                                    <small class="text-muted">Pilih unit kerja terlebih dahulu, lalu pilih karyawan</small>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" id="btn-tambah-peserta" class="btn btn-primary btn-block">
                                        <i class="fas fa-plus"></i> Tambah
                                    </button>
                                </div>
                            </div>

                            {{-- Tabel Peserta --}}
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="tabel-peserta">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Nama Pegawai</th>
                                            <th>NIP</th>
                                            <th>Jabatan Asal</th>
                                            <th>Jenjang Asal</th>
                                            <th>Jabatan Tujuan</th>
                                            <th>Jenjang Tujuan</th>
                                            <th>Unit Kerja Tujuan</th>
                                            <th style="width: 120px;">Validasi</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="peserta-body">
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">Belum ada peserta ditambahkan</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="ajukan_sekarang" value="0" class="btn btn-secondary">
                                <i class="fas fa-save"></i> Simpan Draft
                            </button>
                            <button type="submit" name="ajukan_sekarang" value="1" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Simpan & Ajukan
                            </button>
                            <a href="{{ route('pengangkatan.index') }}" class="btn btn-danger float-right">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
    let pesertaCount = 0;
    let currentUnitKerjaId = '';
    let cachedPegawaiData = {};
    let pesertaList = [];

    $(document).ready(function() {
        // Init select2 (kecuali dropdown-pilih-pegawai yang diinit tersendiri)
        $('.select2').not('#dropdown-pilih-pegawai').select2({ theme: 'bootstrap4', width: '100%' });

        // Init dropdown pilih Pegawai (di atas tabel)
        initDropdownPegawai();

        // Initialize currentUnitKerjaId dari dropdown
        currentUnitKerjaId = $('#unit_kerja_id').val();

        // Load employees jika unit kerja sudah dipilih
        if (currentUnitKerjaId) {
            loadPegawaiByUnitKerja(currentUnitKerjaId);
        }

        // Event listener saat unit kerja berubah
        $('#unit_kerja_id').on('change', function() {
            currentUnitKerjaId = $(this).val();
            console.log('Unit kerja berubah:', currentUnitKerjaId);

            if (currentUnitKerjaId) {
                loadPegawaiByUnitKerja(currentUnitKerjaId);
            } else {
                cachedPegawaiData = {};
                $('#dropdown-pilih-pegawai').prop('disabled', true).val(null).trigger('change');
            }
        });

        // Custom file input
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Event click tombol Tambah Peserta
        $('#btn-tambah-peserta').on('click', function(e) {
            e.preventDefault();
            tambahPeserta();
        });
    });

    // Init dropdown pilih Pegawai di atas tabel
    function initDropdownPegawai() {
        let $dropdown = $('#dropdown-pilih-pegawai');

        $dropdown.select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '-- Pilih Unit Kerja Dahulu --',
            allowClear: true
        });

        $dropdown.on('select2:select', function(e) {
            letPegawaiId = e.params.data.id;
            console.log('Pegawai dipilih:',letPegawaiId);

            // Langsung tambahkan ke tabel
            if (letPegawaiId) {
                // Ambil data dari option yang dipilih
                let selectedOption = $(this).find('option:selected');
                letpegawai = {
                    id: pesertaCount + 1,
                   pegawai_id: letPegawaiId,
                    nama: selectedOption.data('nama'),
                    nip: selectedOption.data('nip'),
                    jabatan_asal: selectedOption.data('jabatan'),
                    jenjang_asal: selectedOption.data('jenjang'),
                    unit_kerja_asal: selectedOption.data('unit_kerja_id')
                };

                // Cek duplikasi
                if (pesertaList.find(p => p.pegawai_id == letPegawaiId)) {
                    alert('Pegawai ini sudah ditambahkan!');
                    $(this).val(null).trigger('change');
                    return;
                }

                pesertaList.push(pegawai);
                renderTabelPeserta();
                console.log('Peserta ditambahkan:',pegawai);
            }

            // Reset dropdown
            $(this).val(null).trigger('change');
        });
    }

    // Load employees by unit_kerja_id via AJAX
    function loadPegawaiByUnitKerja(unitKerjaId) {
        console.log('Loading employees for unit kerja:', unitKerjaId);
        $.ajax({
            url: '{{ route("pengangkatan.get-pegawai") }}',
            method: 'GET',
            data: { unit_kerja_id: unitKerjaId },
            success: function(response) {
                console.log('Response:', response);
                cachedPegawaiData[unitKerjaId] = response.results;
                console.log('Pegawai loaded:', response.results.length);
                populateDropdownPegawai(response.results);
            },
            error: function(xhr, status, error) {
                console.error('Error loadingpegawai:', xhr.responseText);
                alert('Error: ' + error);
            }
        });
    }

    // Populate dropdown Pilih Pegawai di atas tabel
    function populateDropdownPegawai(dataList) {
        let $dropdown = $('#dropdown-pilih-pegawai');

        // Enable dropdown dulu
        $dropdown.prop('disabled', false);

        // Destroy Select2 lama jika ada
        if ($dropdown.data('select2')) {
            $dropdown.select2('destroy');
        }

        // Reset dan populate options
        $dropdown.empty();
        $dropdown.append('<option value="">-- Pilih Pegawai --</option>');

        if (dataList && dataList.length > 0) {
            $.each(dataList, function(i, item) {
                var option = new Option(item.text, item.id, false, false);
                $(option).data('nama', item.nama);
                $(option).data('nip', item.nip);
                $(option).data('jabatan', item.jabatan);
                $(option).data('jenjang', item.jenjang);
                $(option).data('unit_kerja_id', item.unit_kerja_id);
                $dropdown.append(option);
            });
        }

        // Init Select2
        $dropdown.select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '-- Pilih Pegawai --',
            allowClear: true,
            dropdownParent: $dropdown.parent()
        });
    }

    // Tambah peserta ke tabel
    function tambahPeserta() {
        let $dropdown = $('#dropdown-pilih-pegawai');
        letPegawaiId = $dropdown.val();

        if (!letPegawaiId) {
            alert('Pilih terlebih dahulu!');
            return;
        }

        if (pesertaList.find(p => p.pegawai_id ==letPegawaiId)) {
            alert('Pegawai ini sudah ditambahkan!');
            return;
        }

        let selectedOption = $dropdown.find('option:selected');
        letPegawai = {
            id: pesertaCount + 1,
          pegawai_id:letPegawaiId,
            nama: selectedOption.data('nama'),
            nip: selectedOption.data('nip'),
            jabatan_asal: selectedOption.data('jabatan'),
            jenjang_asal: selectedOption.data('jenjang'),
            unit_kerja_asal: selectedOption.data('unit_kerja_id')
        };

        pesertaList.push(pegawai);
        renderTabelPeserta();
        $dropdown.val(null).trigger('change');
        console.log('Peserta ditambahkan:',pegawai);
    }

    // Render tabel peserta
    function renderTabelPeserta() {
        let tbody = $('#peserta-body');
        tbody.empty();

        if (pesertaList.length === 0) {
            tbody.append('<tr><td colspan="10" class="text-center text-muted">Belum ada peserta ditambahkan</td></tr>');
            return;
        }

        pesertaList.forEach(function(peserta, index) {
            let tr = $('<tr>');
            tr.attr('data-index', index);

            // Kolom # (No)
            tr.append('<td class="text-center">' + (index + 1) + '</td>');
            // Nama Pegawai
            tr.append('<td>' + (peserta.nama || '-') + '</td>');
            // NIP
            tr.append('<td>' + (peserta.nip || '-') + '</td>');
            // Jabatan Asal
            tr.append('<td>' + (peserta.jabatan_asal || '-') + '</td>');
            // Jenjang Asal
            tr.append('<td>' + (peserta.jenjang_asal || '-') + '</td>');

            // Jabatan Tujuan (dropdown)
            let optionsJabatan = '<option value="">-- Pilih --</option>';
            @foreach($formasi as $f)
                optionsJabatan += '<option value="{{ $f->id }}" data-jenjang="{{ $f->jenjang?->nama_jenjang }}" data-unit="{{ $f->unit_kerja_id }}">{{ $f->nama_formasi }} ({{ $f->jenjang?->nama_jenjang ?? '-' }})</option>';
            @endforeach
            tr.append('<td><select name="peserta[' + index + '][jabatan_tujuan_id]" class="form-control form-control-sm select2" onchange="loadJabatanData(' + index + ')">' + optionsJabatan + '</select></td>');

            // Jenjang Tujuan (dropdown)
            let optionsJenjang = '<option value="">-- Pilih --</option>';
            @foreach(['Pemula','Terampil','Mahir','Penyelia','Ahli Pertama','Ahli Muda','Ahli Madya','Ahli Utama'] as $j)
                optionsJenjang += '<option value="{{ $j }}">{{ $j }}</option>';
            @endforeach
            tr.append('<td><select name="peserta[' + index + '][jenjang_tujuan]" class="form-control form-control-sm select2" onchange="validasiPeserta(' + index + ')">' + optionsJenjang + '</select></td>');

            // Unit Kerja Tujuan (dropdown)
            let optionsUnitKerja = '<option value="">-- Pilih --</option>';
            @foreach($unitKerja as $uk)
                optionsUnitKerja += '<option value="{{ $uk->no_rs }}">{{ $uk->nama_rumahsakit }}</option>';
            @endforeach
            tr.append('<td><select name="peserta[' + index + '][unit_kerja_tujuan_id]" class="form-control form-control-sm select2" onchange="validasiPeserta(' + index + ')">' + optionsUnitKerja + '</select></td>');

            // Validasi
            tr.append('<td><div class="validasi-badges" data-index="' + index + '"><span class="badge badge-secondary">Pending</span></div></td>');

            // Aksi
            tr.append('<td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="hapusPeserta(' + index + ')"><i class="fas fa-trash"></i></button></td>');

            // Hidden inputs
            tr.append('<input type="hidden" name="peserta[' + index + '][pegawai_id]" value="' +peserta.pegawai_id + '">');
            tr.append('<input type="hidden" name="peserta[' + index + '][jabatan_asal]" value="' + (peserta.jabatan_asal || '') + '">');
            tr.append('<input type="hidden" name="peserta[' + index + '][jenjang_asal]" value="' + (peserta.jenjang_asal || '') + '">');
            tr.append('<input type="hidden" name="peserta[' + index + '][unit_kerja_asal]" value="' + (peserta.unit_kerja_asal || '') + '">');

            tbody.append(tr);
            tr.find('.select2').select2({ theme: 'bootstrap4', width: '100%' });
        });
    }

    // Hapus peserta
    function hapusPeserta(index) {
        pesertaList.splice(index, 1);
        renderTabelPeserta();
    }

    // Load Jabatan Data (auto-fill Jenjang & Unit Kerja Tujuan)
    function loadJabatanData(index) {
        let $tr = $('#peserta-body tr[data-index="' + index + '"]');
        letJabatanId = $tr.find('select[name="peserta[' + index + '][jabatan_tujuan_id]"]').val();
        let selectedOption = $tr.find('select[name="peserta[' + index + '][jabatan_tujuan_id]"] option:selected');

        if (jabatanId) {
            letJenjang = selectedOption.data('jenjang');
            letUnitKerja = selectedOption.data('unit');

            $tr.find('select[name="peserta[' + index + '][jenjang_tujuan]"]').val(letJenjang).trigger('change');
            $tr.find('select[name="peserta[' + index + '][unit_kerja_tujuan_id]"]').val(letUnitKerja).trigger('change');
        }

        validasiPeserta(index);
    }

    // Validasi Peserta
    function validasiPeserta(index) {
        let $tr = $('#peserta-body tr[data-index="' + index + '"]');
        letPegawaiId = $tr.find('input[name="peserta[' + index + '][pegawai_id]"]').val();
        letJabatanTujuanId = $tr.find('select[name="peserta[' + index + '][jabatan_tujuan_id]"]').val();
        letUnitKerjaTujuanId = $tr.find('select[name="peserta[' + index + '][unit_kerja_tujuan_id]"]').val();

        if (!letPegawaiId || !letJabatanTujuanId || !letUnitKerjaTujuanId) {
            $tr.find('.validasi-badges').html('<span class="badge badge-secondary">Pending</span>');
            return;
        }

        $.ajax({
            url: '{{ route("pengangkatan.validasi-peserta") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
             pegawai_id:letPegawaiId,
                jabatan_tujuan_id: jabatanTujuanId,
                unit_kerja_tujuan_id: letUnitKerjaTujuanId
            },
            success: function(response) {
                let formasiBadge = response.formasi.tersedia
                    ? `<span class="badge badge-success" title="${response.formasi.pesan}">Formasi: OK</span>`
                    : `<span class="badge badge-danger" title="${response.formasi.pesan}">Formasi: Penuh</span>`;

                let ujikomBadge = response.ujikom.memenuhi
                    ? `<span class="badge badge-success" title="${response.ujikom.pesan}">Ujikom: OK</span>`
                    : `<span class="badge badge-warning" title="${response.ujikom.pesan}">Ujikom: Belum</span>`;

                $tr.find('.validasi-badges').html(`
                    <div class="d-flex flex-column gap-1">
                        ${formasiBadge}
                        ${ujikomBadge}
                    </div>
                `);
            },
            error: function() {
                $tr.find('.validasi-badges').html('<span class="badge badge-danger">Error</span>');
            }
        });
    }
</script>
@endpush
@endsection
