@php
    $item = $item ?? ($sdm ?? null);
@endphp

<span class="subsection-label">Informasi Dasar</span>
<div class="form-row">
    <div class="form-group col-md-3">
        <label class="font-weight-bold">NIP</label>
        <input type="text" name="nip" value="{{ old('nip', $item->nip ?? '') }}" class="form-control">
        @error('nip') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-3">
        <label class="font-weight-bold">NIK</label>
        <input type="text" name="nik" value="{{ old('nik', $item->nik ?? '') }}" class="form-control">
        @error('nik') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-6">
        <label class="font-weight-bold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $item->nama_lengkap ?? '') }}" class="form-control" required>
        @error('nama_lengkap') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-3">
        <label class="font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
        <select name="jenis_kelamin" class="form-control" required>
            <option value="">-- Pilih Jenis Kelamin --</option>
            <option value="L" @selected(old('jenis_kelamin', $item->jenis_kelamin ?? '') === 'L')>L</option>
            <option value="P" @selected(old('jenis_kelamin', $item->jenis_kelamin ?? '') === 'P')>P</option>
        </select>
        @error('jenis_kelamin') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
</div>

<hr class="my-4">

<span class="subsection-label">Kepegawaian</span>
<div class="form-row">
    <div class="form-group col-md-4">
        <label class="font-weight-bold">Pendidikan Terakhir</label>
        <input type="text" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir', $item->pendidikan_terakhir ?? '') }}" class="form-control">
        @error('pendidikan_terakhir') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-4">
        <label class="font-weight-bold">Pangkat/Golongan</label>
        <input type="text" name="pangkat_golongan" value="{{ old('pangkat_golongan', $item->pangkat_golongan ?? '') }}" class="form-control">
        @error('pangkat_golongan') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-4">
        <label class="font-weight-bold">Status Kepegawaian <span class="text-danger">*</span></label>
        <select name="status_kepegawaian" class="form-control" required>
            @php $status = old('status_kepegawaian', $item->status_kepegawaian ?? 'PNS'); @endphp
            <option value="PNS" @selected($status==='PNS')>PNS</option>
            <option value="PPPK" @selected($status==='PPPK')>PPPK</option>
            <option value="CPNS" @selected($status==='CPNS')>CPNS</option>
            <option value="Non ASN" @selected($status==='Non ASN')>Non ASN</option>
        </select>
        @error('status_kepegawaian') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
</div>

<hr class="my-4">

<span class="subsection-label">Penempatan</span>
<div class="form-row">
    <div class="form-group col-md-6">
        <label class="font-weight-bold">Formasi (opsional)</label>
        <select name="formasi_jabatan_id" class="form-control">
            <option value="">-- Belum ditempatkan --</option>

            @php
              $selectedFormasi = old('formasi_jabatan_id', $item->formasi_jabatan_id ?? '');
              $grouped = ($formasi ?? collect())->groupBy('unit_kerja_id');
            @endphp

            @foreach($grouped as $unitId => $items)
              @php
                $unitName = optional($items->first()->unitKerja)->nama_unit_kerja ?? 'Unit Kerja tidak diketahui';
              @endphp
              <optgroup label="{{ $unitName }}">
                @foreach($items as $f)
                  @php
                    $terisi = (int)($f->terisi ?? 0);
                    $kuota  = (int)($f->kuota ?? 0);
                    $sisa   = $kuota - $terisi; // Bisa minus (over kuota diizinkan)
                    $jenjang = $f->jenjang->nama_jenjang ?? '—';
                  @endphp
                  <option value="{{ $f->id }}"
                          @selected($selectedFormasi == $f->id)
                          @if($sisa < 0) class="text-danger" @endif
                          @if($sisa === 0) class="text-warning" @endif>
                    {{ $jenjang }} — (Kuota: {{ $kuota }} / Terisi: {{ $terisi }} / Sisa: {{ $sisa }})
                  </option>
                @endforeach
              </optgroup>
            @endforeach
        </select>
        @error('formasi_jabatan_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-6">
        <label class="font-weight-bold">Unit Kerja (jika tanpa formasi)</label>
        <select name="unit_kerja_id" id="unitKerjaSelect" class="form-control">
            <option value="">-- Pilih Unit Kerja --</option>
            @foreach($unitkerja as $u)
              <option value="{{ $u->id }}"
                @selected(old('unit_kerja_id', $item->unit_kerja_id ?? null) == $u->id)>
                {{ $u->nama_unit_kerja }}
              </option>
            @endforeach
        </select>
        <small class="text-muted">Kosongkan jika memilih Formasi.</small>
        @error('unit_kerja_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-3">
        <label class="font-weight-bold">TMT Pengangkatan</label>
        <input type="date" name="tmt_pengangkatan"
               value="{{ old('tmt_pengangkatan', optional($item->tmt_pengangkatan ?? null)->format('Y-m-d')) }}"
               class="form-control">
        @error('tmt_pengangkatan') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="aktifCheck" name="aktif" value="1"
                   @checked(old('aktif', $item->aktif ?? true))>
            <label class="form-check-label" for="aktifCheck">Aktif</label>
        </div>
    </div>
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

<div class="d-flex justify-content-end mt-4">
    <a href="{{ route('user.sdm.index') }}" class="btn btn-outline-secondary mr-2">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> {{ ($mode ?? 'create') === 'edit' ? 'Update' : 'Simpan' }}
    </button>
</div>
