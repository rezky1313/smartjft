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
            <form action="{{ isset($user) ? route('user.manajemen-user.update', $user) : route('user.manajemen-user.store') }}" method="POST">
                @csrf
                @method(isset($user) ? 'PUT' : 'POST')
                <div class="card-body">

                    {{-- ── Role & Status ── --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('role') is-invalid @enderror" id="role" name="role" required style="width: 100%;">
                                    <option value="">-- Pilih Role --</option>
                                    @foreach($roles as $roleOpt)
                                        <option value="{{ $roleOpt->name }}" {{ old('role', isset($user) ? $user->roles->first()->name ?? '' : '') == $roleOpt->name ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $roleOpt->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('status') is-invalid @enderror" id="status" name="status" required style="width: 100%;">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="active" {{ old('status', isset($user) ? $user->status : '') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status', isset($user) ? $user->status : '') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── Section: Pemangku (muncul hanya jika role = pemangku) ── --}}
                    <div id="sectionPemangku" style="display:none;">
                        <div class="alert alert-info py-2 mb-3" style="font-size:0.88rem;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Untuk role <strong>Pemangku</strong>: username dan password login otomatis menggunakan <strong>NIP</strong> pegawai yang dipilih.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit_kerja_filter">Unit Kerja <span class="text-muted">(untuk filter pegawai)</span></label>
                                    <select class="form-control select2" id="unit_kerja_filter" style="width:100%;">
                                        <option value="">-- Pilih Unit Kerja --</option>
                                        @foreach($unitKerjas as $uk)
                                            <option value="{{ $uk->no_rs }}"
                                                {{ (isset($user) && $user->sdm?->unit_kerja_id == $uk->no_rs) ? 'selected' : '' }}>
                                                {{ $uk->nama_rumahsakit }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sdm_id">Pegawai JFT <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('sdm_id') is-invalid @enderror" id="sdm_id" name="sdm_id" style="width:100%;">
                                        <option value="">-- Pilih unit kerja terlebih dahulu --</option>
                                        @if(isset($user) && $user->sdm)
                                            <option value="{{ $user->sdm->id }}" selected>
                                                {{ $user->sdm->nama_lengkap }} ({{ $user->sdm->nip }})
                                            </option>
                                        @endif
                                    </select>
                                    @error('sdm_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div id="infoPegawaiTerpilih" class="alert alert-success py-2 mb-3" style="display:none; font-size:0.88rem;">
                            <i class="fas fa-user-check mr-1"></i>
                            <span id="infoPegawaiNama">-</span> &mdash; NIP: <strong id="infoPegawaiNip">-</strong>
                            <br><small class="text-muted">Username dan password login: <strong id="infoPegawaiNipLogin">-</strong></small>
                        </div>
                        {{-- Email opsional untuk pemangku --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email_pemangku">Email <span class="text-muted">(opsional)</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email_pemangku" name="email"
                                           value="{{ old('email', isset($user) ? $user->email : '') }}"
                                           placeholder="Kosongkan jika tidak ada email">
                                    @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Section: Non-Pemangku ── --}}
                    <div id="sectionNonPemangku">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name"
                                           value="{{ old('name', isset($user) ? $user->name : '') }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', isset($user) ? $user->email : '') }}" required>
                                    @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @if(!isset($user))
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password" required minlength="6">
                                    @error('password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                           id="password_confirmation" name="password_confirmation" required minlength="6">
                                    @error('password_confirmation')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                </div>
                <div class="card-footer">
                    <a href="{{ route('user.manajemen-user.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ isset($user) ? 'Update' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        dropdownParent: $('.card-body')
    });

    var getPegawaiUrl = "{{ route('ujikom.permohonan.get-pegawai') }}";

    function toggleSections() {
        var role = $('#role').val();
        if (role === 'pemangku') {
            $('#sectionPemangku').show();
            $('#sectionNonPemangku').hide();
            $('#name').prop('required', false);
            $('#email').prop('required', false);
        } else {
            $('#sectionPemangku').hide();
            $('#sectionNonPemangku').show();
            $('#name').prop('required', true);
            $('#email').prop('required', true);
        }
    }

    // Jalankan saat halaman load
    toggleSections();

    // Jalankan saat role berubah
    $('#role').on('change', toggleSections);

    // Filter pegawai berdasarkan unit kerja
    $('#unit_kerja_filter').on('change', function() {
        var unitKerjaId = $(this).val();
        var select = $('#sdm_id');

        if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
        select.empty().append('<option value="">-- Memuat data... --</option>');

        if (!unitKerjaId) {
            select.empty().append('<option value="">-- Pilih unit kerja terlebih dahulu --</option>');
            select.select2({ theme: 'bootstrap4', placeholder: '-- Pilih unit kerja terlebih dahulu --', width: '100%' });
            return;
        }

        $.get(getPegawaiUrl, { unit_kerja_id: unitKerjaId }, function(data) {
            select.empty().append('<option value="">-- Pilih Pegawai JFT --</option>');
            if (data.results && data.results.length) {
                $.each(data.results, function(i, p) {
                    select.append(new Option(p.text, p.id, false, false));
                });
            }
            select.select2({ theme: 'bootstrap4', placeholder: '-- Pilih Pegawai JFT --', width: '100%', allowClear: true });
        });
    });

    // Tampilkan info pegawai terpilih
    $('#sdm_id').on('change', function() {
        var selected = $(this).find('option:selected');
        var val = $(this).val();
        if (val) {
            var text = selected.text();
            // text format: "Nama Lengkap (NIP)"
            var nipMatch = text.match(/\(([^)]+)\)$/);
            var nip = nipMatch ? nipMatch[1] : '-';
            var nama = nipMatch ? text.replace('(' + nip + ')', '').trim() : text;
            $('#infoPegawaiNama').text(nama);
            $('#infoPegawaiNip').text(nip);
            $('#infoPegawaiNipLogin').text(nip);
            $('#infoPegawaiTerpilih').show();
        } else {
            $('#infoPegawaiTerpilih').hide();
        }
    });

    // Jika edit mode dan role pemangku, tampilkan info pegawai
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
