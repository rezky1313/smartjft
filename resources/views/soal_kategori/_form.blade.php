@php $prefix = $prefix ?? ''; @endphp
<div class="row">
  <div class="col-md-8">
    <div class="form-group">
      <label class="font-weight-bold">Nama Kategori <span class="text-danger">*</span></label>
      <input type="text" name="nama" class="form-control" placeholder="contoh: PKB Terampil - Darat"
             value="{{ old($prefix.'nama') }}">
    </div>
  </div>
  <div class="col-md-4">
    <div class="form-group">
      <label class="font-weight-bold">Bidang</label>
      <input type="text" name="bidang" class="form-control" placeholder="Teknis / Hukum / Umum"
             value="{{ old($prefix.'bidang') }}">
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group">
      <label class="font-weight-bold">Jabatan JFT</label>
      <input type="text" name="jabatan" class="form-control" placeholder="contoh: Penguji Kendaraan Bermotor"
             value="{{ old($prefix.'jabatan') }}">
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="font-weight-bold">Jenjang <span class="text-danger">*</span></label>
      <select name="jenjang" class="form-control">
        <option value="">— Pilih —</option>
        @foreach (['pemula'=>'Pemula','terampil'=>'Terampil','mahir'=>'Mahir','penyelia'=>'Penyelia','ahli_pertama'=>'Ahli Pertama','ahli_muda'=>'Ahli Muda','ahli_madya'=>'Ahli Madya','ahli_utama'=>'Ahli Utama','umum'=>'Umum'] as $v => $l)
        <option value="{{ $v }}" {{ old($prefix.'jenjang') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="font-weight-bold">Matra <span class="text-danger">*</span></label>
      <select name="matra" class="form-control">
        <option value="">— Pilih —</option>
        @foreach (['darat'=>'Darat','laut'=>'Laut','udara'=>'Udara','asdp'=>'ASDP','umum'=>'Umum'] as $v => $l)
        <option value="{{ $v }}" {{ old($prefix.'matra') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group mb-0">
      <label class="font-weight-bold">Deskripsi <small class="text-muted font-weight-normal">(opsional)</small></label>
      <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan tambahan...">{{ old($prefix.'deskripsi') }}</textarea>
    </div>
  </div>
</div>
