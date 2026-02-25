@extends('layouts.users.master')
@section('title', 'Edit Formasi Jabatan')
@section('isi')
@php
  $jenjang   = $jenjang ?? collect();
  $unitkerja = $unitkerja ?? collect();
@endphp

<div class="container">
    <h4 class="mb-4">Edit Formasi Jabatan</h4>

    <form action="{{ route('user.formasi.update', $formasi->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- <div class="mb-3">
            <label for="nama_formasi" class="form-label">Nama Formasi</label>
            <input type="text" name="nama_formasi" id="nama_formasi"
                   class="form-control"
                   value="{{ old('nama_formasi', $formasi->nama_formasi) }}" required>
            @error('nama_formasi') <small class="text-danger">{{ $message }}</small> @enderror
        </div> --}}
        <div class="mb-3">
    <label for="nama_formasi" class="form-label">Nama Formasi</label>
    <select name="nama_formasi" id="nama_formasi" class="form-control select2" required>
        <option value="">-- Pilih Nama Formasi --</option>
        @foreach(($daftarFormasi ?? []) as $f)
            <option value="{{ $f }}"
                {{ old('nama_formasi', $formasi->nama_formasi) === $f ? 'selected' : '' }}>
                {{ $f }}
            </option>
        @endforeach
    </select>
    @error('nama_formasi') <small class="text-danger">{{ $message }}</small> @enderror
</div>

        <div class="mb-3">
            <label for="jenjang_id" class="form-label">Jenjang Jabatan</label>
            <select name="jenjang_id" id="jenjang_id" class="form-control" required>
                <option value="">-- Pilih Jenjang Jabatan --</option>
                @foreach (($jenjang ?? collect())->groupBy('kategori') as $kategori => $items)
                    <optgroup label="{{ $kategori }}">
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}"
                                {{ old('jenjang_id', $formasi->jenjang_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_jenjang ?? ($item->kategori) }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('jenjang_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label for="unit_kerja_id" class="form-label">Unit Kerja</label>
            <select name="unit_kerja_id" id="unit_kerja_id" class="form-control select2" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitkerja ?? [] as $unit)
                    {{-- value pakai no_rs, selected bandingkan ke no_rs juga --}}
                    <option value="{{ $unit->no_rs }}"
                        {{ old('unit_kerja_id', $formasi->unit_kerja_id) == $unit->no_rs ? 'selected' : '' }}>
                        {{ $unit->nama_rumahsakit }}
                    </option>
                @endforeach
            </select>
            @error('unit_kerja_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota</label>
            <input type="number" name="kuota" id="kuota" class="form-control"
                   value="{{ old('kuota', $formasi->kuota) }}" min="0" required>
            @error('kuota') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label for="tahun_formasi" class="form-label">Tahun Formasi</label>
            <input type="text" name="tahun_formasi" id="tahun_formasi" class="form-control"
                   value="{{ old('tahun_formasi', $formasi->tahun_formasi) }}" min="2000" required>
            @error('tahun_formasi') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        {{-- (opsional) tampilkan info terisi/sisa kalau controller sudah withCount --}}
        @isset($formasi->terisi)
            <div class="mb-3">
                <div class="alert alert-info p-2">
                    Terisi: <strong>{{ $formasi->terisi }}</strong> /
                    Kuota: <strong>{{ $formasi->kuota }}</strong> —
                    Sisa: <strong>{{ max($formasi->kuota - $formasi->terisi, 0) }}</strong>
                </div>
            </div>
        @endisset

        <button type="submit" class="btn btn-primary">Perbarui</button>
        <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.$ && $.fn.select2) {
        $('.select2').select2({ placeholder: 'Pilih Unit Kerja', allowClear: true });
    }
});
</script>
@endpush

@endsection




{{-- @extends('layouts.users.master')
@section('title', 'Edit Formasi Jabatan')
@section('isi')

<div class="container">
    <h4 class="mb-4">Edit Formasi Jabatan</h4>

    <form action="{{ route('user.formasi.update', $formasi->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nama_formasi" class="form-label">Nama Formasi</label>
            <input type="text" name="nama_formasi" class="form-control" value="{{ $formasi->nama_formasi }}" required>
        </div> --}}

        {{-- <div class="mb-3">
            <label for="jenjang" class="form-label">Jenjang Jabatan</label>
            <input type="text" name="jenjang" class="form-control" value="{{ $formasi->jenjang }}" required>
        </div> --}}
          {{-- <div class="mb-3">
    <label for="jenjang" class="form-label">Jenjangg Jabatan</label>
    <select name="jenjang_id" id="jenjang_id" class="form-control" required>
        <option value="">-- Pilih Jenjang Jabatan --</option>
        @foreach ($jenjang->groupBy('kategori') as $kategori => $items)
            <optgroup label="{{ $kategori }}">
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->nama_jenjang }} 
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>

        <div class="mb-3">
            <label for="unit_kerja_id" class="form-label">Unit Kerja</label>
            <select name="unit_kerja_id" class="form-control select2" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitkerja as $unit)
                    <option value="{{ $unit->no_rs }}" {{ $formasi->unit_kerja_id == $unit->id ? 'selected' : '' }}>
                        {{ $unit->nama_rumahsakit }} 
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota</label>
            <input type="number" name="kuota" class="form-control" value="{{ $formasi->kuota }}" required>
        </div>

        <div class="mb-3">
            <label for="tahun_formasi" class="form-label">Tahun Formasi</label>
            <input type="number" name="tahun_formasi" class="form-control" value="{{ $formasi->tahun_formasi }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Perbarui</button>
        <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Pilih Unit Kerja",
            allowClear: true
        });
    });
</script>
@endpush

@endsection --}}
