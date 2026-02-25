@extends('layouts.users.master')
@section('title', 'Tambah Formasi Jabatan')
@section('isi')

<div class="container">
    <h4 class="mb-4">Tambah Formasi Jabatan</h4>

    <form action="{{ route('user.formasi.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
        @csrf

        {{-- <div class="mb-3">
            <label for="nama_formasi" class="form-label">Nama Formasi</label>
            <input type="text"  name="nama_formasi" id="nama_formasi" class="form-control" required>
        </div> --}}
        <div class="mb-3">
    <label for="nama_formasi" class="form-label">Nama Formasi</label>
    <select name="nama_formasi" id="nama_formasi" class="form-control select2" required>
        <option value="">-- Pilih Nama Formasi --</option>
        @foreach(($daftarFormasi ?? []) as $f)
            <option value="{{ $f }}" {{ old('nama_formasi') === $f ? 'selected' : '' }}>
                {{ $f }}
            </option>
        @endforeach
    </select>
    @error('nama_formasi') <small class="text-danger">{{ $message }}</small> @enderror
</div>

        <!-- <div class="mb-3">
            <label for="jenjang" class="form-label">Jenjang Jabatan</label>
            <input type="text" name="jenjang" class="form-control" required>
        </div> -->

        <div class="mb-3">
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
            <select name="unit_kerja_id" id="unit_kerja_id" class="form-control select2" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitkerja as $unit)
                    <option value="{{ $unit->no_rs }}">{{ $unit->nama_rumahsakit }} </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="kuota" class="form-label">Kuota</label>
            <input type="number" name="kuota" id="kuota" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="tahun_formasi" class="form-label">Tahun Formasi</label>
            <input type="text" name="tahun_formasi" id="tahun_formasi"class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
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

@endsection
