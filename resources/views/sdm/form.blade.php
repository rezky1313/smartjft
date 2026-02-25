{{-- @php
    $item = $item ?? ($sdm ?? null);
@endphp

<div class="row g-3">

    <div class="col-md-3">
        <label class="form-label">NIP</label>
        <input type="text" name="nip" value="{{ old('nip', $item->nip ?? '') }}" class="form-control">
        @error('nip') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">NIK</label>
        <input type="text" name="nik" value="{{ old('nik', $item->nik ?? '') }}" class="form-control">
        @error('nik') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $item->nama_lengkap ?? '') }}" class="form-control" required>
        @error('nama_lengkap') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
        <select name="jenis_kelamin" class="form-select" required>
            <option value="">--Pilih--</option>
            <option value="L" @selected(old('jenis_kelamin', $item->jenis_kelamin ?? '') === 'L')>L</option>
            <option value="P" @selected(old('jenis_kelamin', $item->jenis_kelamin ?? '') === 'P')>P</option>
        </select>
        @error('jenis_kelamin') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Pendidikan Terakhir</label>
        <input type="text" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir', $item->pendidikan_terakhir ?? '') }}" class="form-control">
        @error('pendidikan_terakhir') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

       <div class="col-md-4">
        <label class="form-label">Pangkat/Golongan</label>
        <input type="text" name="pangkat_golongan" value="{{ old('pangkat_golongan', $item->pangkat_golongan ?? '') }}" class="form-control">
        @error('pangkat_golongan') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
        <select name="status_kepegawaian" class="form-select" required>
            @php $status = old('status_kepegawaian', $item->status_kepegawaian ?? 'PNS'); @endphp
            <option value="PNS" @selected($status==='PNS')>PNS</option>
            <option value="PPPK" @selected($status==='PPPK')>PPPK</option>
            <option value="CPNS" @selected($status==='CPNS')>CPNS</option>
            <option value="Non ASN" @selected($status==='Non ASN')>Non ASN</option>
        </select>
        @error('status_kepegawaian') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Formasi (opsional)</label>
        <select name="formasi_jabatan_id" class="form-select">
            <option value="">-- Belum ditempatkan --</option>
            @foreach($formasi as $f)
                <option value="{{ $f->id }}" @selected(old('formasi_jabatan_id', $item->formasi_jabatan_id ?? '') == $f->id)>
                    {{ $f->jenjang->nama_jenjang }}
                </option>
            @endforeach
        </select>
        @error('formasi_jabatan_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">TMT Pengangkatan</label>
        <input type="date" name="tmt_pengangkatan" value="{{ old('tmt_pengangkatan', optional($item->tmt_pengangkatan ?? null)->format('Y-m-d')) }}" class="form-control">
        @error('tmt_pengangkatan') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="aktif" value="1"
                   @checked(old('aktif', $item->aktif ?? true))>
            <label class="form-check-label">Aktif</label>
        </div>
    </div>

    <div class="col-12 d-flex gap-2 mt-3">
        <button class="btn btn-primary">{{ ($mode ?? 'create') === 'edit' ? 'Update' : 'Simpan' }}</button>
        <a href="{{ route('user.sdm.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</div> --}}


@php
    $item = $item ?? ($sdm ?? null);
@endphp

<div class="mb-3">
    <label class="form-label">NIP</label>
    <input type="text" name="nip" value="{{ old('nip', $item->nip ?? '') }}" class="form-control">
    @error('nip') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3">
    <label class="form-label">NIK</label>
    <input type="text" name="nik" value="{{ old('nik', $item->nik ?? '') }}" class="form-control">
    @error('nik') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
    <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $item->nama_lengkap ?? '') }}" class="form-control" required>
    @error('nama_lengkap') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
    <select name="jenis_kelamin" class="form-select" required>
        <option value="">-- Pilih Jenis Kelamin --</option>
        <option value="L" @selected(old('jenis_kelamin', $item->jenis_kelamin ?? '') === 'L')>L</option>
        <option value="P" @selected(old('jenis_kelamin', $item->jenis_kelamin ?? '') === 'P')>P</option>
    </select>
    @error('jenis_kelamin') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Pendidikan Terakhir</label>
    <input type="text" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir', $item->pendidikan_terakhir ?? '') }}" class="form-control">
    @error('pendidikan_terakhir') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Pangkat/Golongan</label>
    <input type="text" name="pangkat_golongan" value="{{ old('pangkat_golongan', $item->pangkat_golongan ?? '') }}" class="form-control">
    @error('pangkat_golongan') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
    <select name="status_kepegawaian" class="form-select" required>
        @php $status = old('status_kepegawaian', $item->status_kepegawaian ?? 'PNS'); @endphp
        <option value="PNS" @selected($status==='PNS')>PNS</option>
        <option value="PPPK" @selected($status==='PPPK')>PPPK</option>
        <option value="CPNS" @selected($status==='CPNS')>CPNS</option>
        <option value="Non ASN" @selected($status==='Non ASN')>Non ASN</option>
    </select>
    @error('status_kepegawaian') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="col-md-6">
  <label class="form-label">Formasi (opsional)</label>
  <select name="formasi_jabatan_id" class="form-select">
    <option value="">-- Belum ditempatkan --</option>

    @php
      $selectedFormasi = old('formasi_jabatan_id', $item->formasi_jabatan_id ?? '');
      $grouped = ($formasi ?? collect())->groupBy('unit_kerja_id');
    @endphp

    @foreach($grouped as $unitId => $items)
      @php
        $unitName = optional($items->first()->unitKerja)->nama_rumahsakit ?? 'Unit Kerja tidak diketahui';
      @endphp
      <optgroup label="{{ $unitName }}">
        @foreach($items as $f)
          @php
            $terisi = (int)($f->terisi ?? 0);
            $kuota  = (int)($f->kuota ?? 0);
            $sisa   = max($kuota - $terisi, 0);
            $disabled = $sisa <= 0 && $selectedFormasi != $f->id; // saat edit, tetap boleh pilih yg sudah terpakai
            $jenjang = $f->jenjang->nama_jenjang ?? '—';
          @endphp
          <option value="{{ $f->id }}"
                  @selected($selectedFormasi == $f->id)
                  @if($disabled) disabled @endif>
            {{ $jenjang }} — (Kuota: {{ $kuota }} / Terisi: {{ $terisi }} / Sisa: {{ $sisa }})
          </option>
        @endforeach
      </optgroup>
    @endforeach
  </select>
  @error('formasi_jabatan_id') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="col-md-6">
  <label class="form-label">Unit Kerja (jika tanpa formasi)</label>
  <select name="unit_kerja_id" id="unitKerjaSelect" class="form-select">
    <option value="">-- Pilih Unit Kerja --</option>
    @foreach($unitkerja as $u)
      <option value="{{ $u->no_rs }}"
        @selected(old('unit_kerja_id', $item->unit_kerja_id ?? null) == $u->no_rs)>
        {{ $u->nama_rumahsakit }}
      </option>
    @endforeach
  </select>
  <small class="text-muted">Kosongkan jika memilih Formasi.</small>
  @error('unit_kerja_id') <small class="text-danger">{{ $message }}</small> @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const formasi = document.querySelector('select[name="formasi_jabatan_id"]');
  const unit    = document.getElementById('unitKerjaSelect');

  function toggleUnit() {
    const hasFormasi = !!(formasi && formasi.value);
    unit.disabled = hasFormasi;
    if (hasFormasi) unit.value = ''; // paksa kosong saat formasi dipilih
  }
  formasi?.addEventListener('change', toggleUnit);
  toggleUnit();
});
</script>
@endpush


<div class="mb-3">
    <label class="form-label">TMT Pengangkatan</label>
    <input type="date" name="tmt_pengangkatan"
           value="{{ old('tmt_pengangkatan', optional($item->tmt_pengangkatan ?? null)->format('Y-m-d')) }}"
           class="form-control">
    @error('tmt_pengangkatan') <small class="text-danger">{{ $message }}</small> @enderror
</div>

<div class="mb-3 form-check">
    <input class="form-check-input" type="checkbox" id="aktifCheck" name="aktif" value="1"
           @checked(old('aktif', $item->aktif ?? true))>
    <label class="form-check-label" for="aktifCheck">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary">{{ ($mode ?? 'create') === 'edit' ? 'Update' : 'Simpan' }}</button>
    <a href="{{ route('user.sdm.index') }}" class="btn btn-secondary">Kembali</a>
</div>
