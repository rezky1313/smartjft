@extends('layouts.users.master')

@section('title', 'Edit Soal #' . $soal->id . ' — Pusbin JFT')

@section('isi')
<div class="row justify-content-center">
  <div class="col-lg-10">

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-pencil-alt mr-2"></i>Edit Soal <small class="text-muted">#{{ $soal->id }}</small></h3>
        <a href="{{ route('bank-soal.show', $soal->id) }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('bank-soal.update', $soal->id) }}" method="POST" id="formSoal">
      @csrf @method('PUT')

      {{-- SECTION 1: Klasifikasi --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">1</span>Klasifikasi Soal</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Jenis Soal <span class="text-danger">*</span></label>
                <div>
                  <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="jenisMansoskul" name="jenis" value="mansoskul" class="custom-control-input"
                           {{ old('jenis', $soal->jenis) === 'mansoskul' ? 'checked' : '' }} onchange="toggleJenis()">
                    <label class="custom-control-label" for="jenisMansoskul">
                      <strong>Mansoskul</strong> <small class="text-muted">— manajerial &amp; sosio-kultural, nilai skala 1-5</small>
                    </label>
                  </div>
                  <div class="custom-control custom-radio custom-control-inline mt-1">
                    <input type="radio" id="jenisTeknis" name="jenis" value="teknis" class="custom-control-input"
                           {{ old('jenis', $soal->jenis) === 'teknis' ? 'checked' : '' }} onchange="toggleJenis()">
                    <label class="custom-control-label" for="jenisTeknis">
                      <strong>Teknis</strong> <small class="text-muted">— sesuai kategori jabatan, jawaban benar/salah</small>
                    </label>
                  </div>
                </div>
              </div>

              {{-- Kategori — khusus Teknis --}}
              <div class="form-group" id="sectionKategori" style="{{ old('jenis', $soal->jenis) === 'teknis' ? '' : 'display:none;' }}">
                <label class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
                <select name="soal_kategori_id" class="form-control">
                  <option value="">— Pilih Kategori —</option>
                  @foreach ($kategoris as $k)
                  <option value="{{ $k->id }}" {{ old('soal_kategori_id', $soal->soal_kategori_id) == $k->id ? 'selected' : '' }}>
                    {{ $k->nama }}
                  </option>
                  @endforeach
                </select>
              </div>

              {{-- Matra — khusus Mansoskul --}}
              <div class="form-group" id="sectionMatra" style="{{ old('jenis', $soal->jenis) === 'mansoskul' ? '' : 'display:none;' }}">
                <label class="font-weight-bold">Matra <span class="text-danger">*</span></label>
                <select name="matra" class="form-control">
                  <option value="">— Pilih Matra —</option>
                  @foreach (['darat'=>'Darat','laut'=>'Laut','udara'=>'Udara','asdp'=>'ASDP','perkeretaapian'=>'Perkeretaapian'] as $val => $lbl)
                  <option value="{{ $val }}" {{ old('matra', $soal->matra) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Taksonomi Bloom <span class="text-danger">*</span></label>
                <select name="taksonomi_bloom" class="form-control">
                  <option value="">— Pilih —</option>
                  @foreach ([
                    'C1_mengingat'    => 'C1 — Mengingat',
                    'C2_memahami'     => 'C2 — Memahami',
                    'C3_menerapkan'   => 'C3 — Menerapkan',
                    'C4_menganalisis' => 'C4 — Menganalisis',
                    'C5_mengevaluasi' => 'C5 — Mengevaluasi',
                    'C6_mencipta'     => 'C6 — Mencipta',
                  ] as $val => $lbl)
                  <option value="{{ $val }}" {{ old('taksonomi_bloom', $soal->taksonomi_bloom) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="form-group mb-0">
                <label class="font-weight-bold">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control">
                  <option value="draft"    {{ old('status', $soal->status) === 'draft'    ? 'selected' : '' }}>Draft</option>
                  <option value="aktif"    {{ old('status', $soal->status) === 'aktif'    ? 'selected' : '' }}>Aktif</option>
                  <option value="nonaktif" {{ old('status', $soal->status) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- SECTION 2: Pertanyaan --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">2</span>Pertanyaan</h3>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="font-weight-bold">Teks Pertanyaan <span class="text-danger">*</span></label>
            <textarea name="pertanyaan" class="form-control" rows="4">{{ old('pertanyaan', $soal->pertanyaan) }}</textarea>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold">Pembahasan <small class="text-muted font-weight-normal">(opsional)</small></label>
            <textarea name="pembahasan" class="form-control" rows="3">{{ old('pembahasan', $soal->pembahasan) }}</textarea>
          </div>
        </div>
      </div>

      {{-- SECTION 3: Pilihan Jawaban --}}
      <div class="card">
        <div class="card-header bg-light">
          <h3 class="card-title mb-0"><span class="badge badge-primary mr-2">3</span>Pilihan Jawaban</h3>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3" id="petunjukTeknis"><i class="fas fa-info-circle mr-1"></i>Centang radio button di sebelah kiri untuk menandai jawaban yang benar.</p>
          <p class="text-muted small mb-3" id="petunjukMansoskul" style="display:none;"><i class="fas fa-info-circle mr-1"></i>Tiap pilihan diberi nilai skala 1-5 (bukan benar/salah) — 5 = paling tepat, 1 = paling tidak tepat.</p>

          @php $jawabanBenar = old('jawaban_benar', $soal->pilihan->where('is_benar', true)->first()?->kode_pilihan); @endphp
          @foreach ($soal->pilihan as $pilihan)
          @php $isBenar = $jawabanBenar === $pilihan->kode_pilihan; @endphp
          <div class="form-group pilihan-row" id="row{{ $pilihan->kode_pilihan }}"
               style="border:1px solid {{ $isBenar ? '#c3e6cb' : '#dee2e6' }};border-radius:6px;padding:10px 12px;margin-bottom:10px;background:{{ $isBenar ? '#d4edda' : '' }};">
            <div class="d-flex align-items-center">
              <div class="mr-3 pilihan-radio-benar">
                <input type="radio" name="jawaban_benar" value="{{ $pilihan->kode_pilihan }}" id="benar{{ $pilihan->kode_pilihan }}"
                       {{ $isBenar ? 'checked' : '' }}
                       onchange="highlightJawaban('{{ $pilihan->kode_pilihan }}')" style="transform:scale(1.3);">
              </div>
              <div class="mr-3">
                <span class="badge badge-secondary px-2 py-1" style="font-size:0.9rem;min-width:28px;">{{ $pilihan->kode_pilihan }}</span>
              </div>
              <div class="flex-fill">
                <input type="text" name="pilihan[{{ $pilihan->kode_pilihan }}][teks]" class="form-control form-control-sm"
                       value="{{ old('pilihan.'.$pilihan->kode_pilihan.'.teks', $pilihan->teks_pilihan) }}">
              </div>
              <div class="ml-3 pilihan-nilai-skala" style="display:none; width:120px;">
                <select name="pilihan[{{ $pilihan->kode_pilihan }}][nilai_skala]" class="form-control form-control-sm">
                  <option value="">Nilai</option>
                  @for ($n = 1; $n <= 5; $n++)
                  <option value="{{ $n }}" {{ old('pilihan.'.$pilihan->kode_pilihan.'.nilai_skala', $pilihan->nilai_skala) == $n ? 'selected' : '' }}>Nilai {{ $n }}</option>
                  @endfor
                </select>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      {{-- Tombol --}}
      <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center">
          <a href="{{ route('bank-soal.show', $soal->id) }}" class="btn btn-default">Batal</a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan Perubahan
          </button>
        </div>
      </div>

    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function toggleJenis() {
  const isMansoskul = document.getElementById('jenisMansoskul').checked;

  document.getElementById('sectionKategori').style.display = isMansoskul ? 'none' : '';
  document.getElementById('sectionMatra').style.display    = isMansoskul ? '' : 'none';

  document.getElementById('petunjukTeknis').style.display    = isMansoskul ? 'none' : '';
  document.getElementById('petunjukMansoskul').style.display = isMansoskul ? '' : 'none';

  document.querySelectorAll('.pilihan-radio-benar').forEach(function (el) {
    el.style.display = isMansoskul ? 'none' : '';
  });
  document.querySelectorAll('.pilihan-nilai-skala').forEach(function (el) {
    el.style.display = isMansoskul ? '' : 'none';
  });
}

function highlightJawaban(kode) {
  ['A','B','C','D'].forEach(function(k) {
    const row = document.getElementById('row' + k);
    if (k === kode) {
      row.style.background = '#d4edda';
      row.style.borderColor = '#c3e6cb';
    } else {
      row.style.background = '';
      row.style.borderColor = '#dee2e6';
    }
  });
}

document.addEventListener('DOMContentLoaded', toggleJenis);
</script>
@endpush
