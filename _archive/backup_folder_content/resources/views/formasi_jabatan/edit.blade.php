@extends('layouts.users.master')
@section('title', 'Edit Formasi Jabatan')
@section('isi')

<div class="container">
    <h4 class="mb-4">Edit Formasi Jabatan</h4>

    <form action="{{ route('admin.formasi-jabatan.update', $formasi->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nama_formasi" class="form-label">Nama Formasi</label>
            <input type="text" name="nama_formasi" class="form-control" value="{{ $formasi->nama_formasi }}" required>
        </div>

        <div class="mb-3">
            <label for="jenjang" class="form-label">Jenjang Jabatan</label>
            <input type="text" name="jenjang" class="form-control" value="{{ $formasi->jenjang }}" required>
        </div>

        <div class="mb-3">
            <label for="unit_kerja_id" class="form-label">Unit Kerja</label>
            <select name="unit_kerja_id" class="form-control select2" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitKerja as $unit)
                    <option value="{{ $unit->id }}" {{ $formasi->unit_kerja_id == $unit->id ? 'selected' : '' }}>
                        {{ $unit->nama_rumahsakit }} - {{ $unit->kota_kabupaten }}
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
        <a href="{{ route('admin.formasi-jabatan.index') }}" class="btn btn-secondary">Kembali</a>
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
