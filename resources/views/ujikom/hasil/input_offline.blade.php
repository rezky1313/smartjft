@extends('layouts.users.master')

@section('title', 'Input Hasil Ujian Offline — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-1">Input Hasil Ujian Offline</h4>
        <p class="text-muted mb-0 small">{{ $jadwal->judul }}</p>
      </div>
      <a href="{{ route('ujikom.hasil.show', $jadwal->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
    </div>

    @if ($paket)
    <div class="alert alert-info py-2">
      <i class="fas fa-info-circle mr-1"></i>
      <strong>Passing Grade Paket Ujian:</strong> {{ $paket->passing_grade }}%
      &nbsp;&mdash;&nbsp; Nilai &ge; {{ $paket->passing_grade }} = Lulus
    </div>
    @endif

    @if ($pesertaBelumDinilai->isEmpty())
    <div class="card">
      <div class="card-body text-center text-muted py-5">
        <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
        Semua peserta sudah memiliki hasil ujian.
      </div>
    </div>
    @else
    <form method="POST" action="{{ route('ujikom.hasil.simpan-offline', $jadwal->id) }}">
      @csrf
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Peserta Belum Dinilai ({{ $pesertaBelumDinilai->count() }} orang)</h3></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="font-size:0.83rem;">
              <thead class="thead-light">
                <tr>
                  <th width="40" class="text-center">No</th>
                  <th>Nama / NIP</th>
                  <th width="140">Jabatan Tujuan / Jenjang</th>
                  <th width="100" class="text-center">Nilai (0-100)</th>
                  <th width="140" class="text-center">Status</th>
                  <th width="130" class="text-center">Tanggal Ujian</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($pesertaBelumDinilai->values() as $i => $p)
                <input type="hidden" name="hasil[{{ $i }}][peserta_id]" value="{{ $p->id }}">
                <tr>
                  <td class="text-center">{{ $i + 1 }}</td>
                  <td>
                    <strong>{{ $p->pegawai?->nama_lengkap ?? '-' }}</strong>
                    <br><small class="text-muted">{{ $p->pegawai?->nip ?? '-' }}</small>
                  </td>
                  <td>
                    <small>{{ $p->jabatan_tujuan_nama ?? '-' }}</small>
                    <br><small class="text-muted">{{ $p->jenjang_tujuan ?? '-' }}</small>
                  </td>
                  <td class="text-center">
                    <input type="number" name="hasil[{{ $i }}][nilai]"
                           class="form-control form-control-sm text-center nilai-input"
                           min="0" max="100" step="0.01"
                           placeholder="0-100"
                           data-index="{{ $i }}"
                           data-passing="{{ $paket?->passing_grade ?? 70 }}">
                  </td>
                  <td class="text-center">
                    <select name="hasil[{{ $i }}][status_kelulusan]"
                            class="form-control form-control-sm status-select"
                            id="status_{{ $i }}">
                      <option value="belum_dinilai">— Belum Dinilai —</option>
                      <option value="lulus">Lulus</option>
                      <option value="tidak_lulus">Tidak Lulus</option>
                    </select>
                  </td>
                  <td>
                    <input type="date" name="hasil[{{ $i }}][tanggal_ujian]"
                           class="form-control form-control-sm"
                           value="{{ date('Y-m-d') }}">
                  </td>
                  <td>
                    <input type="text" name="hasil[{{ $i }}][catatan_admin]"
                           class="form-control form-control-sm"
                           placeholder="Catatan (opsional)">
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-right">
          <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan semua hasil offline ini?')">
            <i class="fas fa-save mr-1"></i> Simpan Semua
          </button>
        </div>
      </div>
    </form>
    @endif

  </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-set status berdasar nilai dan passing grade
$(document).on('input', '.nilai-input', function() {
    const nilai       = parseFloat($(this).val());
    const passing     = parseFloat($(this).data('passing'));
    const index       = $(this).data('index');
    const statusSelect = $(`#status_${index}`);

    if (!isNaN(nilai) && !isNaN(passing)) {
        statusSelect.val(nilai >= passing ? 'lulus' : 'tidak_lulus');
    }
});
</script>
@endpush
