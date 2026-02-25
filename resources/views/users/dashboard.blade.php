@extends('layouts.users.master')

@section('title')
@if (Auth::user()->role =='user') Pusbin JFT - ADMIN @else Pusbin JFT - USER @endif
@endsection

@section('isi')
<div class="container-fluid">

  {{-- ============ HERO ============ --}}

      <h3 class="mb-4 fw-bold">Halaman Dashboard</h3>

{{-- RINGKASAN NASIONAL --}}
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <h5 class="fw-semibold mb-3">Ringkasan Nasional</h5>
    <p class="text-muted small mb-4">
      Data Pemangku JFT aktif berdasarkan data terkini.
    </p>
    <div class="row g-3">


{{-- Total JFT Aktif --}}
<div class="col-md-3 col-6">
  <div class="p-3 rounded-3 bg-light h-100 d-flex flex-column">
    <div class="d-flex flex-column align-items-center text-center">
      <div class="stat-ico-circle bg-info text-white mb-2">
        <i class="far fa-user"></i>
      </div>
      <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalJftAktif) }}</h4>
      <small class="text-muted">Total Pemangku JFT Aktif</small>
    </div>
  </div>
</div>


      {{-- Rekap Jenjang --}}
      <div class="col-md-9 col-12">
        <div class="p-3 rounded-3 bg-light h-100">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fas fa-layer-group text-primary"></i>
            <span class="fw-semibold">Rekap JFT per Jenjang</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  @foreach($levels as $lvl) <th>{{ $lvl }}</th> @endforeach
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  @php $sum = array_sum($perJenjang ?? []); @endphp
                  @foreach($levels as $lvl)
                    <td>{{ number_format($perJenjang[$lvl] ?? 0) }}</td>
                  @endforeach
                  <td class="fw-bold">{{ number_format($sum) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


  {{-- ============ FILTER (Collapsible) ============ --}}
  <div class="glass rounded-4 mb-4">
    <div class="p-3 border-bottom d-flex align-items-center">
      <h6 class="mb-0"><i class="fas fa-filter me-2 text-primary"></i>Filter Rekap</h6>
      <button class="btn btn-link ms-auto text-decoration-none" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
        Tampilkan/Sembunyikan
      </button>
    </div>
    <div class="collapse show" id="filterCollapse">
      <div class="p-3">
        <form method="GET" action="{{ url('user/dashboard/peta') }}">
          <div class="row gy-3 gx-3">
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Moda</label>
              <select name="matra" class="form-select select2">
                <option value="">Semua Moda</option>
                @foreach($matras as $m)
                  <option value="{{ $m }}" @selected(($fMatra ?? null) === $m)>{{ $m }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Nama Formasi</label>
              <select name="formasi" class="form-select select2">
                <option value="">Semua Nama Formasi</option>
                @foreach($daftarFormasi as $nm)
                  <option value="{{ $nm }}" @selected(($fFormasi ?? null) === $nm)>{{ $nm }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Provinsi</label>
              <select name="province_id" id="provFilter" class="form-select select2">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" @selected(($fProvinceId ?? null) == $p->id)>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Kabupaten/Kota</label>
              <select name="regency_id" id="regFilter" class="form-select select2">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" @selected(($fRegencyId ?? null) == $r->id)>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="d-flex gap-2 justify-content-end mt-3">
            <a href="{{ url('user/dashboard/peta') }}" class="btn btn-light">Reset</a>
            <button class="btn btn-primary px-4">Terapkan</button>
          </div>
          <div class="text-muted small mt-2">
            Default menampilkan agregat nasional (Semua Moda, Semua Formasi, Semua Provinsi, Semua Kab/Kota).
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ============ REKAP TERFILTER ============ --}}
<div class="glass rounded-4 mb-4">
  
  <div class="p-3 border-bottom d-flex align-items-center">
  <h6 class="mb-0">
    <i class="fas fa-chart-bar me-2 text-primary"></i>Rekap Terfilter
    ... {{-- (teks filter yang sudah ada) --}}
  </h6>

 <div class="ms-auto d-flex gap-2">
  <a class="btn btn-sm btn-outline-success"
     href="{{ route('user.dashboard.peta.export-excel', request()->query()) }}">
     Export Excel
  </a>
  <a class="btn btn-sm btn-outline-danger"
     href="{{ route('user.dashboard.peta.export-pdf', request()->query()) }}">
     Export PDF
  </a>
</div>
</div>

  <div class="p-3 pt-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          {{-- BEFORE (header 1 baris) --}}
          {{-- AFTER (header 2 baris + kolom Jenis JFT) --}}
          <tr>
            <th rowspan="2" style="width:36px">#</th>
            <th rowspan="2" style="min-width:260px">Nama Jabatan</th>
            <th colspan="{{ count($levels) }}" class="text-center">Jenjang</th>
            <th rowspan="2" class="text-end">Total</th>
          </tr>
          <tr>
            @foreach($levels as $lvl)
              <th class="text-end text-nowrap">{{ $lvl }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>

          {{-- RINCIAN 22 JFT (hanya baris yang punya data) --}}
@php
  $J  = $matrixJft['jenjangOrder'] ?? $levels ?? [];
  $N  = $matrixJft['allJft']      ?? [];
  $M  = $matrixJft['matrix']      ?? [];
  $RT = $matrixJft['rowTotals']   ?? [];

  // Ambil hanya JFT dengan total > 0 (setelah filter diterapkan)
  $visibleJft = array_values(array_filter($N, function($nm) use ($RT) {
      return ((int)($RT[$nm] ?? 0)) > 0;
  }));
@endphp

@if(empty($visibleJft))
  <tr>
    <td colspan="{{ 3 + count($J) }}" class="text-center text-muted">
      Tidak ada data untuk kombinasi filter ini.
    </td>
  </tr>
@else
  @php $no = 1; @endphp
  @foreach($visibleJft as $jft)
    <tr>
      <td>{{ $no++ }}</td>
      <td>{{ $jft }}</td>
      @foreach($J as $jj)
        <td class="text-end">{{ number_format((int)($M[$jft][$jj] ?? 0)) }}</td>
      @endforeach
      <td class="text-end fw-semibold">{{ number_format((int)($RT[$jft] ?? 0)) }}</td>
    </tr>
  @endforeach
@endif

        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="2">Total per Jenjang</th>
            @php $CT = $matrixJft['colTotals'] ?? []; @endphp
            @foreach($levels as $lvl)
              <th class="text-end">{{ number_format($CT[$lvl] ?? 0) }}</th>
            @endforeach
            <th class="text-end">{{ number_format($matrixJft['grand'] ?? 0) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>


  {{-- ------------------------- --}}


<div class="col-14 col-lg-12">
  <div class="glass rounded-4 p-3 mb-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0"><i class="fas fa-sort-amount-down me-2 text-primary"></i>Piramida Pemangku JFT</h6>
    </div>
    <div id="pyramidOneSide" style="height:240px;"></div>

    <div class="text-muted small mt-2">Urutan kecil → besar (atas ke bawah). Mengikuti filter yang dipilih.</div>
  </div>
</div>



  {{-- ============ MAP ============ --}}
  <div class="glass rounded-4 mb-4">
    <div class="p-3 border-bottom">
      <h6 class="mb-0"><i class="fas fa-map me-2 text-primary"></i>Peta Persebaran</h6>
    </div>
    <div id="leafletMap-dashboard" style="height: 650px;"></div>

    

  </div>

</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .stat-ico-circle{
    width:56px;height:56px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:22px;
  }

  /* Theme bits */
  .bg-gradient-primary{
    background: linear-gradient(135deg,#4e73df 0%, #1cc88a 100%);
  }
  .glass{
    background: rgba(255,255,255,.9);
    box-shadow: 0 8px 24px rgba(16,24,40,.08);
    backdrop-filter: blur(6px);
  }
  .stat-card{ border:1px solid rgba(16,24,40,.06); }
  .stat-icon{
    width:48px;height:48px; display:grid; place-items:center; font-size:20px;
    box-shadow: 0 6px 16px rgba(16,24,40,.12);
  }
  .stat-value{ font-size:1.35rem; font-weight:700; line-height:1; }
  .stat-label{ font-size:.85rem; color:#667085; }

  /* Select2 height match */
  .select2-container .select2-selection--single{
    height: 42px!important; padding: 6px 12px; border: 1px solid #ced4da; border-radius: .5rem;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height: 28px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
{{-- <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-funnel"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>




<script>
document.addEventListener('DOMContentLoaded', function () {
  // INIT Select2
  $('.select2').select2({ width: '100%', placeholder: 'Pilih…', allowClear: true });

  // --- Dependent Prov -> Regency
  const baseUrl = @json(url('/user/wilayah/regencies'));
  const $prov = $('#provFilter'); const $reg = $('#regFilter');
  const initialProv = @json($fProvinceId ?? ''); const initialReg = @json($fRegencyId ?? '');

  function normalizeItem(raw){
    const id = raw.id ?? raw.value ?? '';
    const name = raw.name ?? raw.nama ?? raw.text ?? '';
    const type = raw.type ?? raw.tipe ?? '';
    const label = [type, name].filter(Boolean).join(' ').trim() || id;
    return { id: String(id), text: label };
  }
  function setRegencyOptions(list, selectedId=''){
    if ($reg.hasClass('select2-hidden-accessible')) $reg.select2('destroy');
    $reg.empty().append(new Option('Semua Kab/Kota','',true,selectedId===''));
    (list||[]).map(normalizeItem).forEach(x=> $reg.append(new Option(x.text, x.id, false, x.id===String(selectedId))));
    $reg.select2({width:'100%',placeholder:'Pilih…',allowClear:true});
  }
  async function loadRegencies(pid, preselect=''){
    if (!pid){ setRegencyOptions([], ''); return; }
    const res = await fetch(`${baseUrl}/${pid}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
    setRegencyOptions(await res.json(), preselect);
  }
  $prov.on('change', ()=> loadRegencies($prov.val(), ''));
  if(initialProv){ $prov.val(String(initialProv)).trigger('change.select2'); loadRegencies(initialProv, initialReg); }
  else{ setRegencyOptions([], ''); }


// --- Step pyramid: urut jenjang hierarkis (bawah=terendah, atas=tertinggi)
const orderLowToHigh = @json($levels ?? []);     // ['Pemula','Terampil',...,'Utama']
const srcNames       = @json($pyramidLabels ?? []);
const srcValues      = @json($pyramidValues ?? []);

// Map sumber (mungkin sudah di-sort lainnya) -> urutan hierarkis
const idxMap   = Object.fromEntries(srcNames.map((n, i) => [n, i]));
const names    = orderLowToHigh.slice(); // label sumbu Y
const real     = names.map(n => Number((idxMap[n] != null ? srcValues[idxMap[n]] : 0)) || 0);

// Hitung padding kiri/kanan agar batang tetap di tengah (opsional)
const maxVal   = Math.max(...real, 1);
const leftPad  = real.map(v => (maxVal - v) / 2);
const rightPad = leftPad.slice();

const dom = document.getElementById('pyramidOneSide');
if (dom && window.echarts) {
  const chart  = echarts.init(dom);
  const colors = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#fd7e14','#20c997'];

  chart.setOption({
    grid: { left: 10, right: 10, top: 10, bottom: 10, containLabel: true },
    xAxis: {
      type: 'value', min: 0, max: maxVal,
      axisLabel: { formatter: v => Number(v).toLocaleString('id-ID') }
    },
    yAxis: {
      type: 'category',
      data: names,            // urutan hierarkis
      inverse: false,          // item pertama (Pemula) ditaruh DI BAWAH
      axisTick: { show: false }
    },
    tooltip: {
      trigger: 'axis', axisPointer: { type: 'shadow' },
      formatter: (params) => {
        const p = params.find(x => x.seriesName === 'Nilai') ?? params[0];
        return `${p.name}: ${Number(p.value).toLocaleString('id-ID')}`;
      }
    },
    series: [
      { name:'PadLeft',  type:'bar', stack:'total', data:leftPad,
        itemStyle:{ color:'transparent' }, emphasis:{ disabled:true }, silent:true },
      { name:'Nilai',    type:'bar', stack:'total', data:real, barWidth:28,
        itemStyle:{ color:p=>colors[p.dataIndex%colors.length], borderRadius:[4,4,4,4] },
        label:{ show:true, position:'inside',
                formatter:p=>`${p.name}\n${Number(p.value).toLocaleString('id-ID')}` } },
      { name:'PadRight', type:'bar', stack:'total', data:rightPad,
        itemStyle:{ color:'transparent' }, emphasis:{ disabled:true }, silent:true }
    ],
    animationDuration: 400
  });

  // aman untuk container yang sempat tersembunyi
  setTimeout(() => chart.resize(), 50);
  window.addEventListener('resize', () => chart.resize());
}


  // --- Line: Tren per tahun
  const lineYears = @json($lineChartYears ?? []);
  const lineDatasetsRaw = @json($lineChartData ?? []);
  const ctxLine = document.getElementById('chartLineJenjang');
  if (ctxLine) {
    const colors = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#fd7e14','#20c997'];
    const ds = (lineDatasetsRaw||[]).map((d,i)=>({ label:d.label, data:d.data, borderColor:colors[i%colors.length], backgroundColor:colors[i%colors.length], tension:.35, fill:false }));
    new Chart(ctxLine, {
      type:'line', data:{ labels:lineYears, datasets:ds },
      options:{
        responsive:true, interaction:{mode:'index',intersect:false},
        plugins:{ legend:{position:'bottom'}, tooltip:{callbacks:{label:(c)=>`${c.dataset.label}: ${c.parsed.y?.toLocaleString?.()}`}} },
        scales:{ y:{beginAtZero:true} }
      }
    });
  }
});

// --- Leaflet Map
var map = L.map('leafletMap-dashboard').setView([-2.5489,118.0149],5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'&copy; OpenStreetMap contributors' }).addTo(map);
const markers = @json($markers ?? []);

// ICON UNTUK SETIAP MATRA
const iconDarat = L.icon({
    iconUrl: '/images/matra/darat.png',
    iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

const iconLaut = L.icon({
    iconUrl: '/images/matra/laut.png',
    iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

const iconUdara = L.icon({
    iconUrl: '/images/matra/udara.png',
   iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

const iconKereta = L.icon({
    iconUrl: '/images/matra/kereta.png',
    iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

// FUNGSI memilih icon berdasarkan matra
function getMatraIcon(matra) {
   switch (matra) {
      case 'Darat': return iconDarat;
      case 'Laut': return iconLaut;
      case 'Udara': return iconUdara;
      case 'Kereta': return iconKereta;
      default: return iconDarat; // fallback
   }
}


markers.forEach(m=>{
  if(!m.lat||!m.lng) return;
  const headerHtml = `<div><b>${m.unit??'-'}</b></div>
    <div>Provinsi: ${m.prov??'-'}</div>
    <div>Kab/Kota: ${m.kab??'-'}</div>
    <div>Moda: ${m.matra??'-'}</div>
    <div>Instansi: ${m.instansi??'-'}</div>
    <div><b>Total Formasi:</b> ${m.total_kuota??0}</div>
    <div><b>Total Terisi:</b> ${m.total_terisi??0}</div>
    <div><b>Total Sisa:</b> ${m.total_sisa??0}</div>`;
  let bodyHtml=''; if(m.per_jenjang&&m.per_jenjang.length){
    bodyHtml+=`<div class="mt-2"><i>Rincian per Jenjang</i>:</div><ul class="mb-0">`;
    m.per_jenjang.forEach(j=>{ bodyHtml+=`<li><b>${j.nama}:</b> Kuota: ${j.kuota}, Terisi: ${j.terisi}, Sisa: ${j.sisa}</li>`; });
    bodyHtml+='</ul>';
  }
 // L.marker([m.lat,m.lng]).addTo(map).bindPopup(`<div style="min-width:260px">${headerHtml}${bodyHtml}</div>`);
 L.marker([m.lat, m.lng], { icon: getMatraIcon(m.matra) })
 .addTo(map)
 .bindPopup(`<div style="min-width:260px">${headerHtml}${bodyHtml}</div>`);
});
</script>

<script>
(function(){
  // base URL export
  const baseExcel = @json(route('user.dashboard.peta.export-excel'));
  const basePdf   = @json(route('user.dashboard.peta.export-pdf'));

  // ambil query filter yang sedang aktif agar file = tampilan
  const qsObj = @json(request()->query());
  const qs = new URLSearchParams(qsObj).toString();
  const urlExcel = qs ? `${baseExcel}?${qs}` : baseExcel;
  const urlPdf   = qs ? `${basePdf}?${qs}`   : basePdf;

  async function downloadViaFetch(url, fallbackName){
    try{
      const res = await fetch(url, {
        method:'GET',
        headers:{ 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if(!res.ok) throw new Error('Gagal mengunduh');
      const blob = await res.blob();

      // coba ambil nama file dari header; jika tidak ada, pakai fallback
      let filename = fallbackName;
      const cd = res.headers.get('Content-Disposition');
      if(cd){
        const m = /filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i.exec(cd);
        if(m) filename = decodeURIComponent(m[1] || m[2] || fallbackName);
      }

      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      setTimeout(()=>{ URL.revokeObjectURL(link.href); link.remove(); }, 100);
    }catch(e){
      console.error(e);
      alert('Unduhan gagal. Coba lagi.');
    }
  }

  document.getElementById('btnExportExcel')
          ?.addEventListener('click', ()=> downloadViaFetch(urlExcel, 'rekap_pemangku.xlsx'));
  document.getElementById('btnExportPdf')
          ?.addEventListener('click',   ()=> downloadViaFetch(urlPdf,   'rekap_pemangku.pdf'));
})();
</script>
@endpush
