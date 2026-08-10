@extends('layouts.users.master')

@section('title', isset($user) ? 'Edit User' : 'Tambah User')

@section('isi')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ isset($user) ? 'Edit User' : 'Tambah User' }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('user.peta') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('user.manajemen-user.index') }}">Manajemen User</a></li>
                    <li class="breadcrumb-item active">{{ isset($user) ? 'Edit' : 'Tambah' }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ isset($user) ? 'Form Edit User' : 'Form Tambah User' }}</h3>
            </div>
            <form action="{{ isset($user) ? route('user.manajemen-user.update', $user) : route('user.manajemen-user.store') }}"
                  method="POST">
                @csrf
                @method(isset($user) ? 'PUT' : 'POST')

                <div class="card-body">

                    {{-- BARIS 1: Role & Status (selalu tampil) --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                @php
                                    $rolePenilaiNames = ['pewawancara', 'penguji'];
                                    $userRoleNames    = isset($user) ? $user->roles->pluck('name') : collect();
                                    $isPenilaiUser    = isset($user) && $userRoleNames->intersect($rolePenilaiNames)->isNotEmpty();
                                @endphp
                                <select class="form-control select2 @error('role') is-invalid @enderror"
                                        id="role" name="role" required style="width:100%;">
                                    <option value="">-- Pilih Role --</option>
                                    <option value="penilai_uj" {{ old('role', $isPenilaiUser ? 'penilai_uj' : '') == 'penilai_uj' ? 'selected' : '' }}>
                                        Pewawancara / Penguji
                                    </option>
                                    @foreach($roles as $roleOpt)
                                        @continue(in_array($roleOpt->name, $rolePenilaiNames))
                                        <option value="{{ $roleOpt->name }}"
                                            {{ old('role', isset($user) && !$isPenilaiUser ? $user->roles->first()?->name ?? '' : '') == $roleOpt->name ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $roleOpt->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('status') is-invalid @enderror"
                                        id="status" name="status" required style="width:100%;">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="active"   {{ old('status', isset($user) ? $user->status : 'active') == 'active'   ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status', isset($user) ? $user->status : '')        == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                                @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ══════════════════════════════════════════════════════════
                         SECTION STANDARD — tampil untuk semua role kecuali pemangku
                         ══════════════════════════════════════════════════════════ --}}
                    <div id="sectionStandard">
                        {{-- BARIS 2: Nama & Email --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name"
                                           value="{{ old('name', isset($user) ? $user->name : '') }}">
                                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', isset($user) ? $user->email : '') }}">
                                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- BARIS 3: Password (hanya saat tambah) --}}
                        @if(!isset($user))
                        <div class="row" id="sectionPassword">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password <span class="text-danger">*</span></label>
                                    <input type="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password" minlength="6">
                                    @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation" minlength="6">
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- ══════════════════════════════════════════════════════════
                         SECTION ADMIN UNIT — tampil hanya jika role = admin_unit
                         ══════════════════════════════════════════════════════════ --}}
                    <div id="sectionAdminUnit" style="display:none;">
                        <div class="alert alert-warning py-2 mb-3" style="font-size:0.88rem;">
                            <i class="fas fa-building mr-1"></i>
                            Role <strong>Admin Unit</strong>: wajib memilih unit kerja yang diampunya.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit_kerja_id">Unit Kerja <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('unit_kerja_id') is-invalid @enderror"
                                            id="unit_kerja_id" name="unit_kerja_id" style="width:100%;">
                                        <option value="">-- Pilih Unit Kerja --</option>
                                        @foreach($unitKerjas as $uk)
                                            <option value="{{ $uk->id }}"
                                                {{ old('unit_kerja_id', isset($user) ? $user->unit_kerja_id : '') == $uk->id ? 'selected' : '' }}>
                                                {{ $uk->nama_unit_kerja }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unit_kerja_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ══════════════════════════════════════════════════════════
                         SECTION PEWAWANCARA/PENGUJI — tampil hanya jika role = penilai_uj
                         ══════════════════════════════════════════════════════════ --}}
                    <div id="sectionPenilai" style="display:none;">
                        <div class="alert alert-warning py-2 mb-3" style="font-size:0.88rem;">
                            <i class="fas fa-user-tie mr-1"></i>
                            Akun <strong>Pewawancara/Penguji</strong>: tidak terikat unit kerja, hanya bisa akses input nilai Wawancara/Presentasi Ujikom Online. Boleh pilih kedua gelar sekaligus.
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Gelar <span class="text-danger">*</span></label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input @error('roles') is-invalid @enderror" type="checkbox"
                                               id="roles_pewawancara" name="roles[]" value="pewawancara"
                                               {{ in_array('pewawancara', old('roles', $userRoleNames->toArray())) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="roles_pewawancara">Pewawancara</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                               id="roles_penguji" name="roles[]" value="penguji"
                                               {{ in_array('penguji', old('roles', $userRoleNames->toArray())) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="roles_penguji">Penguji</label>
                                    </div>
                                    @error('roles') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ══════════════════════════════════════════════════════════
                         SECTION PEMANGKU — tampil hanya jika role = pemangku
                         ══════════════════════════════════════════════════════════ --}}
                    <div id="sectionPemangku" style="display:none;">
                        <div class="alert alert-info py-2 mb-3" style="font-size:0.88rem;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Role <strong>Pemangku JFT</strong>: username &amp; password login otomatis menggunakan <strong>NIP</strong> pegawai yang dipilih.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Unit Kerja <span class="text-muted">(untuk filter pegawai)</span></label>
                                    <select class="form-control select2" id="unit_kerja_filter_pemangku" style="width:100%;">
                                        <option value="">-- Pilih Unit Kerja --</option>
                                        @foreach($unitKerjas as $uk)
                                            <option value="{{ $uk->id }}"
                                                {{ (isset($user) && $user->sdm?->unit_kerja_id == $uk->id) ? 'selected' : '' }}>
                                                {{ $uk->nama_unit_kerja }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sdm_id">Pegawai JFT <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('sdm_id') is-invalid @enderror"
                                            id="sdm_id" name="sdm_id" style="width:100%;">
                                        <option value="">-- Pilih unit kerja terlebih dahulu --</option>
                                        @if(isset($user) && $user->sdm)
                                            <option value="{{ $user->sdm->id }}" selected>
                                                {{ $user->sdm->nama_lengkap }} ({{ $user->sdm->nip }})
                                            </option>
                                        @endif
                                    </select>
                                    @error('sdm_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div id="infoPegawaiTerpilih" class="alert alert-success py-2 mb-3" style="display:none; font-size:0.88rem;">
                            <i class="fas fa-user-check mr-1"></i>
                            <span id="infoPegawaiNama">-</span> &mdash; NIP: <strong id="infoPegawaiNip">-</strong><br>
                            <small class="text-muted">Username &amp; password login: <strong id="infoPegawaiNipLogin">-</strong></small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email_pemangku">Email <span class="text-muted">(opsional)</span></label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           id="email_pemangku" name="email"
                                           value="{{ old('email', isset($user) ? $user->email : '') }}"
                                           placeholder="Kosongkan jika tidak ada email"
                                           disabled>
                                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- end card-body --}}

                <div class="card-footer">
                    <a href="{{ route('user.manajemen-user.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> {{ isset($user) ? 'Update' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    var getPegawaiUrl = "{{ route('ujikom.permohonan.get-pegawai') }}";

    // Init Select2
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

    // ── Toggle section berdasarkan role ─────────────────────────────────────
    function toggleSections() {
        var role = $('#role').val();

        // Sembunyikan semua section kondisional dulu
        $('#sectionAdminUnit').hide();
        $('#sectionPemangku').hide();
        $('#sectionPenilai').hide();

        // Reset required
        $('#name, #email').prop('required', false);
        $('#unit_kerja_id, #sdm_id').prop('required', false);
        @if(!isset($user))
        $('#password, #password_confirmation').prop('required', false);
        @endif

        if (role === 'penilai_uj') {
            // Pewawancara/Penguji: tampilkan standard (name/email/password) + section gelar
            $('#sectionStandard').show();
            $('#email').prop('disabled', false);
            $('#email_pemangku').prop('disabled', true);
            $('#sectionPenilai').show();
            $('#name, #email').prop('required', true);
            @if(!isset($user))
            $('#password, #password_confirmation').prop('required', true);
            @endif

        } else if (role === 'pemangku') {
            // Pemangku: sembunyikan standard, tampilkan section pemangku
            $('#sectionStandard').hide();
            $('#email').prop('disabled', true);   // nonaktifkan email standard agar tidak terkirim
            $('#sectionPemangku').show();
            $('#email_pemangku').prop('disabled', false); // aktifkan email pemangku
            $('#sdm_id').prop('required', true);

        } else if (role === 'admin_unit') {
            // Admin Unit: tampilkan standard + section unit kerja
            $('#sectionStandard').show();
            $('#email').prop('disabled', false);
            $('#email_pemangku').prop('disabled', true);
            $('#sectionAdminUnit').show();
            $('#name, #email').prop('required', true);
            $('#unit_kerja_id').prop('required', true);
            @if(!isset($user))
            $('#password, #password_confirmation').prop('required', true);
            @endif

        } else {
            // Super Admin, Admin, Viewer, atau belum dipilih: tampilkan standard saja
            $('#sectionStandard').show();
            $('#email').prop('disabled', false);
            $('#email_pemangku').prop('disabled', true);
            if (role !== '') {
                $('#name, #email').prop('required', true);
                @if(!isset($user))
                $('#password, #password_confirmation').prop('required', true);
                @endif
            }
        }
    }

    // Jalankan saat halaman load (penting untuk mode edit)
    toggleSections();

    // Jalankan saat role berubah
    $('#role').on('change', toggleSections);

    // ── Load pegawai saat unit kerja dipilih (khusus pemangku) ──────────────
    $('#unit_kerja_filter_pemangku').on('change', function() {
        var unitKerjaId = $(this).val();
        var select      = $('#sdm_id');

        if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
        select.empty().append('<option value="">-- Memuat data pegawai... --</option>');

        if (!unitKerjaId) {
            select.empty().append('<option value="">-- Pilih unit kerja terlebih dahulu --</option>');
            select.select2({ theme: 'bootstrap4', width: '100%' });
            $('#infoPegawaiTerpilih').hide();
            return;
        }

        $.get(getPegawaiUrl, { unit_kerja_id: unitKerjaId }, function(data) {
            select.empty().append('<option value="">-- Pilih Pegawai JFT --</option>');
            if (data.results && data.results.length) {
                $.each(data.results, function(i, p) {
                    select.append(new Option(p.text, p.id, false, false));
                });
            }
            select.select2({
                theme: 'bootstrap4',
                placeholder: '-- Pilih Pegawai JFT --',
                width: '100%',
                allowClear: true
            });
        });
    });

    // ── Tampilkan info NIP saat pegawai dipilih ──────────────────────────────
    $('#sdm_id').on('change', function() {
        var val  = $(this).val();
        var text = $(this).find('option:selected').text();
        if (val) {
            // Format text dari getPegawai: "Nama Lengkap — NIP"
            var parts = text.split('—');
            var nama  = parts[0] ? parts[0].trim() : text;
            var nip   = parts[1] ? parts[1].trim() : '-';
            $('#infoPegawaiNama').text(nama);
            $('#infoPegawaiNip').text(nip);
            $('#infoPegawaiNipLogin').text(nip);
            $('#infoPegawaiTerpilih').show();
        } else {
            $('#infoPegawaiTerpilih').hide();
        }
    });

    // Mode edit: tampilkan info jika pemangku sudah punya SDM
    @if(isset($user) && $user->sdm)
    $('#infoPegawaiNama').text('{{ $user->sdm->nama_lengkap }}');
    $('#infoPegawaiNip').text('{{ $user->sdm->nip }}');
    $('#infoPegawaiNipLogin').text('{{ $user->sdm->nip }}');
    $('#infoPegawaiTerpilih').show();
    @endif
});
</script>
@endpush
@endsection
