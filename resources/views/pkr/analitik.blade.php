@extends('layouts.users.master')
@section('title', 'Analitik Pengembangan Karir JFT')
@section('isi')

<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">Analitik &amp; Tren Pengembangan Karir JFT</span>
    <span class="preview-header-subtitle d-block">Tren pengangkatan per tahun &amp; analisis ketersediaan formasi</span>
  </div>
  <div class="preview-body">

    <span class="subsection-label">Tren Pengangkatan per Tahun</span>
    <div class="alert alert-info small">
      <i class="fas fa-info-circle mr-1"></i>
      Dihitung dari tanggal TMT Pengangkatan pegawai. Data ini baru terisi untuk sebagian kecil pegawai
      ({{ $trenTahunMinData }}–{{ $trenTahunMaxData }}) — grafik akan makin lengkap seiring proses Pengangkatan JFT berjalan lewat sistem.
    </div>

    <form method="GET" action="{{ route('karir.analitik.index') }}" class="form-row align-items-end mb-3">
      <input type="hidden" name="tahun_formasi" value="{{ $tahunFormasiDipilih }}">
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Dari Tahun</label>
        <input type="number" name="dari" class="form-control form-control-sm" value="{{ request('dari', $trenTahunMinData) }}" min="1990" max="2100">
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Sampai Tahun</label>
        <input type="number" name="sampai" class="form-control form-control-sm" value="{{ request('sampai', $trenTahunMaxData) }}" min="1990" max="2100">
      </div>
      <div class="form-group col-md-3">
        <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
        <a href="{{ route('karir.analitik.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>

    <div class="btn-group btn-group-sm mb-3" role="group">
      <button type="button" class="btn btn-outline-primary active" id="btnToggleJenjang" onclick="toggleTren('jenjang')">Per Jenjang</button>
      <button type="button" class="btn btn-outline-primary" id="btnToggleModa" onclick="toggleTren('moda')">Per Moda Transportasi</button>
    </div>

    <div style="max-height:420px;">
      <canvas id="chartTren" height="110"></canvas>
    </div>

    <hr class="my-4">

    <span class="subsection-label">Analisis Formasi</span>

    <form method="GET" action="{{ route('karir.analitik.index') }}" class="form-row align-items-end mb-3">
      <input type="hidden" name="dari" value="{{ request('dari') }}">
      <input type="hidden" name="sampai" value="{{ request('sampai') }}">
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Tahun Formasi</label>
        <select name="tahun_formasi" class="form-control form-control-sm" onchange="this.form.submit()">
          @foreach($daftarTahunFormasi as $th)
            <option value="{{ $th }}" @selected((int)$th === $tahunFormasiDipilih)>{{ $th }}</option>
          @endforeach
        </select>
      </div>
    </form>

    <div class="row mb-4">
      @foreach($formasiNasional as $f)
        @php
          $kelas = $f['over_kuota'] > 0 ? 'text-danger' : ($f['kosong'] === 0 ? 'text-warning' : '');
        @endphp
        <div class="col-md-3 col-6 mb-3">
          <div class="border rounded p-3 h-100">
            <div class="font-weight-bold mb-2">{{ $f['label_jenjang'] }}</div>
            <div class="small text-muted">Tersedia</div>
            <div class="mb-1">{{ number_format($f['tersedia']) }}</div>
            <div class="small text-muted">Terisi</div>
            <div class="mb-1">{{ number_format($f['terisi']) }}</div>
            <div class="small text-muted">Kosong</div>
            <div class="{{ $kelas }} fw-bold">{{ number_format($f['kosong']) }}</div>
            @if($f['over_kuota'] > 0)
              <div class="small text-danger mt-1">Over-kuota: {{ number_format($f['over_kuota']) }}</div>
            @endif
          </div>
        </div>
      @endforeach
      @if(count($formasiNasional) === 0)
        <div class="col-12 text-muted">Tidak ada data formasi untuk tahun {{ $tahunFormasiDipilih }}.</div>
      @endif
    </div>

    <div class="form-group">
      <input type="text" id="cariUnitKerja" class="form-control form-control-sm" style="max-width:320px;" placeholder="Cari Unit Kerja...">
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0" id="tabelFormasiUnit">
        <thead style="background:#f8fafc;">
          <tr>
            <th width="20"></th>
            <th>Unit Kerja</th>
            <th class="text-right">Tersedia</th>
            <th class="text-right">Terisi</th>
            <th class="text-right">Kosong</th>
            <th class="text-right">Over-kuota</th>
          </tr>
        </thead>
        <tbody>
          @php
            $grouped = collect($formasiPerUnit)->groupBy('unit_kerja_id');
            $unitMap = $unitKerjaList->keyBy('id');
          @endphp
          @forelse($grouped as $unitId => $rows)
            @php
              $namaUnit = $unitMap[$unitId]->nama_unit_kerja ?? 'Unit Kerja #' . $unitId;
              $totalTersedia = $rows->sum('tersedia');
              $totalTerisi = $rows->sum('terisi');
              $totalKosong = $rows->sum('kosong');
              $totalOver = $rows->sum('over_kuota');
            @endphp
            <tr class="baris-unit" style="cursor:pointer;" data-toggle="collapse" data-target="#detailUnit{{ $unitId }}" data-nama="{{ strtolower($namaUnit) }}">
              <td class="text-center"><i class="fas fa-chevron-right text-muted"></i></td>
              <td>{{ $namaUnit }}</td>
              <td class="text-right">{{ number_format($totalTersedia) }}</td>
              <td class="text-right">{{ number_format($totalTerisi) }}</td>
              <td class="text-right {{ $totalKosong === 0 ? 'text-warning fw-bold' : '' }}">{{ number_format($totalKosong) }}</td>
              <td class="text-right {{ $totalOver > 0 ? 'text-danger fw-bold' : '' }}">{{ $totalOver > 0 ? number_format($totalOver) : '-' }}</td>
            </tr>
            <tr class="collapse" id="detailUnit{{ $unitId }}">
              <td colspan="6" class="p-0">
                <table class="table table-sm mb-0" style="background:#fbfbfb;">
                  <thead>
                    <tr class="small text-muted">
                      <th class="pl-4">Jenjang</th>
                      <th class="text-right">Tersedia</th>
                      <th class="text-right">Terisi</th>
                      <th class="text-right">Kosong</th>
                      <th class="text-right">Over-kuota</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($rows as $r)
                      <tr>
                        <td class="pl-4">{{ $r['label_jenjang'] }}</td>
                        <td class="text-right">{{ number_format($r['tersedia']) }}</td>
                        <td class="text-right">{{ number_format($r['terisi']) }}</td>
                        <td class="text-right {{ $r['kosong'] === 0 ? 'text-warning fw-bold' : '' }}">{{ number_format($r['kosong']) }}</td>
                        <td class="text-right {{ $r['over_kuota'] > 0 ? 'text-danger fw-bold' : '' }}">{{ $r['over_kuota'] > 0 ? number_format($r['over_kuota']) : '-' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">Tidak ada data formasi untuk tahun {{ $tahunFormasiDipilih }}.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const trenLabels = @json($trenTahunList);
const trenJenjang = @json($trenDatasetJenjang);
const trenModa = @json($trenDatasetModa);
const palet = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#fd7e14','#20c997'];

function buildDatasets(raw) {
  return raw.map((d, i) => ({
    label: d.label, data: d.data,
    borderColor: palet[i % palet.length], backgroundColor: palet[i % palet.length],
    tension: .35, fill: false,
  }));
}

let chartTren;
function renderTren(mode) {
  const raw = mode === 'moda' ? trenModa : trenJenjang;
  const ctx = document.getElementById('chartTren');
  if (chartTren) chartTren.destroy();
  chartTren = new Chart(ctx, {
    type: 'line',
    data: { labels: trenLabels, datasets: buildDatasets(raw) },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'bottom' } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
}

function toggleTren(mode) {
  document.getElementById('btnToggleJenjang').classList.toggle('active', mode === 'jenjang');
  document.getElementById('btnToggleModa').classList.toggle('active', mode === 'moda');
  renderTren(mode);
}

document.addEventListener('DOMContentLoaded', function () {
  renderTren('jenjang');

  document.querySelectorAll('.baris-unit i.fa-chevron-right').forEach(function (icon) {
    icon.closest('tr').addEventListener('click', function () {
      icon.classList.toggle('fa-rotate-90');
    });
  });

  const cari = document.getElementById('cariUnitKerja');
  cari.addEventListener('input', function () {
    const kata = this.value.toLowerCase();
    document.querySelectorAll('#tabelFormasiUnit .baris-unit').forEach(function (tr) {
      const cocok = tr.dataset.nama.includes(kata);
      tr.style.display = cocok ? '' : 'none';
      const detail = document.getElementById(tr.getAttribute('data-target').slice(1));
      if (detail && !cocok) detail.classList.remove('show');
    });
  });
});
</script>
@endpush
