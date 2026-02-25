@extends('layouts.users.master')
@section('title','Uji Kompetensi - Edit')
@section('isi')

@php
  $opsiKompetensi = $opsiKompetensi ?? ['PT1','PT2','PT3','PT4','PT5','Perpanjangan'];
@endphp

<div class="container mt-3">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <h4 class="mb-3">Edit Uji Kompetensi</h4>

  <form action="{{ route('user.uji.update', $uji->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-3">

      {{-- Dropdown SDM (searchable) --}}
      <div class="col-md-12">
        <label class="form-label">Cari SDM (Nama / NIP / Unit / Jenjang)</label>
        <select id="sdmSelect" class="form-select" style="width:100%"></select>
        <input type="hidden" name="sdm_id" id="sdm_id" value="{{ old('sdm_id', $uji->sdm_id) }}">
        @error('sdm_id') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      {{-- Otomatis terisi setelah pilih SDM --}}
      @php
        $sdm = $uji->sdm;
        $uk  = $sdm?->formasi?->unitKerja ?? $sdm?->unitKerja;
        $kab = $uk?->regency;
      @endphp

      <div class="col-md-4">
        <label class="form-label">Nama</label>
        <input type="text" id="f_nama" class="form-control" value="{{ $sdm->nama_lengkap ?? '' }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">NIP</label>
        <input type="text" id="f_nip" class="form-control" value="{{ $sdm->nip ?? '' }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Jenjang Jabatan</label>
        <input type="text" id="f_jenjang" class="form-control" value="{{ $sdm?->formasi?->jenjang?->nama_jenjang ?? '' }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nama Unit Kerja</label>
        <input type="text" id="f_unit" class="form-control" value="{{ $uk->nama_rumahsakit ?? '' }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Kab/Kota</label>
        <input type="text" id="f_kabkota" class="form-control" value="{{ $kab ? ($kab->type.' '.$kab->name) : '' }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Instansi</label>
        <input type="text" id="f_instansi" class="form-control" value="{{ $uk->instansi ?? '' }}" readonly>
      </div>

      {{-- Input uji kompetensi --}}
      <div class="col-md-4">
        <label class="form-label">Kompetensi</label>
        <select name="kompetensi" class="form-select" required>
          <option value="">-- Pilih --</option>
          @foreach($opsiKompetensi as $opt)
            <option value="{{ $opt }}" @selected(old('kompetensi', $uji->kompetensi)===$opt)>{{ $opt }}</option>
          @endforeach
        </select>
        @error('kompetensi') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Nilai</label>
        <input type="number" step="0.01" name="nilai" value="{{ old('nilai', $uji->nilai) }}" class="form-control">
        @error('nilai') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Tanggal Uji Kompetensi</label>
        <input type="date" name="tanggal_uji" value="{{ old('tanggal_uji', $uji->tanggal_uji) }}" class="form-control">
        @error('tanggal_uji') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Nomor Sertifikat</label>
        <input type="text" name="nomor_sertifikat" value="{{ old('nomor_sertifikat', $uji->nomor_sertifikat) }}" class="form-control">
        @error('nomor_sertifikat') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Keterangan</label>
        <input type="text" name="keterangan" value="{{ old('keterangan', $uji->keterangan) }}" class="form-control">
        @error('keterangan') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      <div class="col-12 mt-2">
        <button class="btn btn-primary">Update</button>
        <a href="{{ route('user.uji.index') }}" class="btn btn-secondary">Batal</a>
      </div>
    </div>
  </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!(window.$ && $.fn.select2)) {
    console.error('Select2 belum ter-load.');
    return;
  }

  const ajaxUrl = '{{ route("user.uji.sdm-search") }}';

  $('#sdmSelect').select2({
    placeholder: 'Ketik nama / NIP / unit / jenjang…',
    allowClear: true,
    minimumInputLength: 0,      // boleh tanpa ketik langsung tampil 20 teratas
    ajax: {
      url: ajaxUrl,
      delay: 250,
      dataType: 'json',
      data: params => ({ q: params.term ?? '' }),
      processResults: function (data) {
        // pastikan format { results: [...] }
        return { results: data.results || [] };
      },
      cache: true
    },
    width: '100%',
    templateResult: function (item) {
      if (!item.id) return item.text || '—';
      return $('<span>'+ (item.text || '') +'</span>');
    },
    templateSelection: function (item) {
      return item.text || item.id || '';
    }
  });

  // Tampilkan 20 data pertama saat dropdown dibuka (tanpa perlu ketik)
  $('#sdmSelect').on('select2:open', function() {
    // trigger pencarian kosong
    const $search = $('.select2-container--open .select2-search__field');
    $search.val('').trigger('input');
  });

  // Saat memilih SDM → isi hidden & field info
  $('#sdmSelect').on('select2:select', function (e) {
    const d = e.params?.data || {};
    $('#sdm_id').val(d.id || '');
    $('#f_nama').val(d.nama || '');
    $('#f_nip').val(d.nip || '');
    $('#f_jenjang').val(d.jenjang || '');
    $('#f_unit').val(d.unit || '');
    $('#f_kabkota').val(d.kabkota || '');
    $('#f_instansi').val(d.instansi || '');
  });

  // Saat dibersihkan
  $('#sdmSelect').on('select2:clear', function () {
    $('#sdm_id').val('');
    for (const id of ['f_nama','f_nip','f_jenjang','f_unit','f_kabkota','f_instansi']) {
      const el = document.getElementById(id);
      if (el) el.value = '';
    }
  });

  // === PRELOAD untuk EDIT atau saat validasi gagal (old('sdm_id')) ===
  @php
    // di create: old('sdm_id'), di edit: $uji->sdm_id
    $preId = old('sdm_id') ?? (isset($uji) ? $uji->sdm_id : null);
  @endphp
  @if(!empty($preId))
    $.getJSON('{{ route("user.uji.sdm-mini", $preId) }}', function(d){
      if (d && d.id) {
        const label = `${d.nama ?? '-'} — ${d.nip ?? '-'} — ${d.unit ?? '-'} — ${d.jenjang ?? '-'}`;
        const opt = new Option(label, d.id, true, true);
        $('#sdmSelect').append(opt).trigger('change');
        $('#sdm_id').val(d.id);
        $('#f_nama').val(d.nama || '');
        $('#f_nip').val(d.nip || '');
        $('#f_jenjang').val(d.jenjang || '');
        $('#f_unit').val(d.unit || '');
        $('#f_kabkota').val(d.kabkota || '');
        $('#f_instansi').val(d.instansi || '');
      }
    });
  @endif
});
</script>
@endpush


{{-- @push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.$ && $.fn.select2) {
    $('#sdmSelect').select2({
      placeholder: 'Ketik nama / NIP / unit / jenjang…',
      allowClear: true,
      ajax: {
        url: '{{ route("user.uji.sdm-search") }}',
        delay: 250,
        dataType: 'json',
        data: params => ({ q: params.term || '' }),
        processResults: data => data,
      },
      minimumInputLength: 1,
      templateResult: function (item) {
        if (!item.id) return item.text || '—';
        return $('<span>'+ item.text +'</span>');
      },
      templateSelection: function (item) {
        return item.text || item.id;
      }
    });

    // preload pilihan saat edit (sdm sekarang)
    @if($uji->sdm_id)
    $.getJSON('{{ route("user.uji.sdm-mini", $uji->sdm_id) }}', function(d){
      if (d && d.id) {
        const opt = new Option(`${d.nama} — ${d.nip ?? '-'} — ${d.unit ?? '-'} — ${d.jenjang ?? '-'}`, d.id, true, true);
        $('#sdmSelect').append(opt).trigger('change');
        // set field readonly
        $('#sdm_id').val(d.id);
        $('#f_nama').val(d.nama || '');
        $('#f_nip').val(d.nip || '');
        $('#f_jenjang').val(d.jenjang || '');
        $('#f_unit').val(d.unit || '');
        $('#f_kabkota').val(d.kabkota || '');
        $('#f_instansi').val(d.instansi || '');
      }
    });
    @endif

    // saat memilih SDM baru
    $('#sdmSelect').on('select2:select', function (e) {
      const d = e.params.data || {};
      $('#sdm_id').val(d.id);
      $('#f_nama').val(d.nama || '');
      $('#f_nip').val(d.nip || '');
      $('#f_jenjang').val(d.jenjang || '');
      $('#f_unit').val(d.unit || '');
      $('#f_kabkota').val(d.kabkota || '');
      $('#f_instansi').val(d.instansi || '');
    });

    // bersihkan
    $('#sdmSelect').on('select2:clear', function () {
      $('#sdm_id').val('');
      ['f_nama','f_nip','f_jenjang','f_unit','f_kabkota','f_instansi'].forEach(id => document.getElementById(id).value = '');
    });
  }
});
</script>
@endpush --}}

@endsection
