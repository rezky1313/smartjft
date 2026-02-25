@extends('layouts.users.master')

@section('isi')
<h4>Buat Usulan Kenaikan Jenjang (Admin)</h4>
<form action="{{ route('user.promotions.store') }}" method="post" enctype="multipart/form-data" class="mt-3">
  @csrf

  <div class="mb-2">
    <label>Pilih Pegawai</label>
    <select id="sdm_id" name="sdm_id" class="form-select select2-sdm" required style="width:100%"></select>
    <small class="text-muted">Cari nama, NIP, atau NIK.</small>
  </div>

  <div class="mb-2">
    <label>Jenjang Saat Ini (otomatis)</label>
    <input id="jenjang_asal" class="form-control" value="— pilih SDM —" readonly>
  </div>

  <div class="mb-2">
    <label>Jenjang Target</label>
    <select name="jenjang_target_id" class="form-select" required>
      <option value="">-- pilih --</option>
     @foreach($jenjangList as $j)
  <option value="{{ $j->id }}">{{ $j->nama_jenjang }}</option>
@endforeach

    </select>
  </div>

  <div class="row">
    <div class="col-md-4 mb-2">
      <label>SK Terakhir (pdf/jpg)</label>
      <input type="file" name="sk_terakhir" class="form-control" required>
    </div>
    <div class="col-md-4 mb-2">
      <label>SKP / Penilaian Kinerja</label>
      <input type="file" name="skp" class="form-control" required>
    </div>
    <div class="col-md-4 mb-2">
      <label>Hasil Uji Kompetensi</label>
      <input type="file" name="sertifikat" class="form-control" required>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Simpan Draft</button>
    <a class="btn btn-light" href="{{ route('user.promotions.index') }}">Batal</a>
  </div>
</form>

{{-- ==== Assets Select2 (pakai salah satu: stack @push atau langsung include) ==== --}}
@once
  @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  @endpush
  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  @endpush
@endonce

{{-- Fallback kalau layout tidak punya @stack --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const $sdm = $('.select2-sdm');

  function formatSdm (item) {
    if (!item.id) return item.text;
    // item.nama, item.nip, item.unit dari server
    const nama = item.nama || item.text;
    const nip  = item.nip  || '-';
    const unit = item.unit || '-';
    const $tpl = $(
      '<div class="select2-result-sdm">' +
        '<div><strong>' + nama + '</strong></div>' +
        '<div style="font-size:12px">NIP: ' + nip + ' • Unit: ' + unit + '</div>' +
      '</div>'
    );
    return $tpl;
  }

  function formatSdmSelection (item) {
    // tampilan setelah dipilih
    if (!item.id) return item.text;
    const nip  = item.nip  || '-';
    const unit = item.unit || '-';
    return (item.nama || item.text) + ' — ' + nip + ' — ' + unit;
  }

  $sdm.select2({
    placeholder: '— cari Pegawai (nama/NIP/NIK) —',
    allowClear: true,
    width: 'resolve',
   ajax: {
    url: '{{ route('user.promotions.sdm-search', [], false) }}',
    dataType: 'json',
    delay: 250,
   data: function (params) {
    return { q: params.term || '' };
  },
  processResults: function (data) { return data; },
  cache: true
},
    templateResult: formatSdm,
    templateSelection: formatSdmSelection,
    minimumInputLength: 1,
    escapeMarkup: function (m) { return m; } // biar HTML templateResult render
  });

  // Isi otomatis "Jenjang Saat Ini" saat pilih SDM
  $sdm.on('select2:select', function (e) {
    const d = e.params.data || {};
    $('#jenjang_asal').val(d.jenjang || '-');
  });
  $sdm.on('select2:clear', function () {
    $('#jenjang_asal').val('— pilih SDM —');
  });
});
</script>
@endsection
