@extends('layouts.users.master')

@section('title', 'Input Nilai Manual — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-1"><i class="fas fa-star-half-alt mr-2"></i>Input Nilai Manual (Wawancara &amp; Presentasi)</h4>
        <p class="text-muted mb-0">{{ $jadwal->judul }}</p>
      </div>
      <a href="{{ route('ujikom-online.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
    </div>

    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    @php
      $kolomAspek = collect([
        ['kompetensi' => 'teknis',    'aspek' => 'wawancara',  'aktif' => $bobotTeknis['wawancara'] > 0,    'label' => 'Teknis — Wawancara'],
        ['kompetensi' => 'teknis',    'aspek' => 'presentasi', 'aktif' => $bobotTeknis['presentasi'] > 0,   'label' => 'Teknis — Presentasi'],
        ['kompetensi' => 'mansoskul', 'aspek' => 'wawancara',  'aktif' => $bobotMansoskul['wawancara'] > 0, 'label' => 'Mansoskul — Wawancara'],
        ['kompetensi' => 'mansoskul', 'aspek' => 'presentasi', 'aktif' => $bobotMansoskul['presentasi'] > 0,'label' => 'Mansoskul — Presentasi'],
      ])->where('aktif', true);
    @endphp

    @if ($kolomAspek->isEmpty())
    <div class="alert alert-info">
      <i class="fas fa-info-circle mr-1"></i>
      Jadwal ini tidak mengaktifkan aspek Wawancara/Presentasi untuk kompetensi manapun — Tes CAT saja yang menentukan nilai.
      Ubah konfigurasi di halaman Edit Jadwal Ujikom kalau ingin mengaktifkan.
    </div>
    @else
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-users mr-2"></i>Daftar Peserta</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0" style="font-size:0.83rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama Peserta</th>
                @foreach ($kolomAspek as $k)
                <th class="text-center">{{ $k['label'] }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse ($pesertaList as $i => $p)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $p->pegawai?->nama_lengkap ?? '-' }}</strong>
                  <br><small class="text-muted">{{ $p->pegawai?->nip ?? '-' }}</small>
                </td>
                @foreach ($kolomAspek as $k)
                @php
                  $existing = ($nilaiManual->get($p->id) ?? collect())->get($k['kompetensi'] . '_' . $k['aspek']);
                @endphp
                <td>
                  <form action="{{ route('ujikom-online.admin.nilai-manual.store') }}" method="POST" class="d-flex align-items-center" style="gap:4px;">
                    @csrf
                    <input type="hidden" name="ujikom_jadwal_id" value="{{ $jadwal->id }}">
                    <input type="hidden" name="peserta_id" value="{{ $p->id }}">
                    <input type="hidden" name="kompetensi" value="{{ $k['kompetensi'] }}">
                    <input type="hidden" name="aspek" value="{{ $k['aspek'] }}">
                    <select name="nilai" class="form-control form-control-sm" style="width:65px;" required>
                      <option value="">-</option>
                      @for ($n = 1; $n <= 5; $n++)
                      <option value="{{ $n }}" {{ $existing && $existing->nilai == $n ? 'selected' : '' }}>{{ $n }}</option>
                      @endfor
                    </select>
                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Catatan (opsional)" value="{{ $existing->catatan ?? '' }}">
                    <button type="submit" class="btn btn-xs btn-primary" title="Simpan">
                      <i class="fas fa-save"></i>
                    </button>
                  </form>
                  @if ($existing)
                  <small class="text-muted d-block mt-1">Dinilai oleh {{ $existing->penilai?->name ?? '-' }}</small>
                  @endif
                </td>
                @endforeach
              </tr>
              @empty
              <tr><td colspan="{{ 2 + $kolomAspek->count() }}" class="text-center text-muted py-4">Belum ada peserta terverifikasi untuk jadwal ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

  </div>
</div>
@endsection
