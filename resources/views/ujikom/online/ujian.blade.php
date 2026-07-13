@extends('layouts.users.master')

@section('title', 'Ujian Berlangsung — Pusbin JFT')

@push('styles')
<style>
  /* Timer */
  .timer-box {
    font-size: 1.8rem;
    font-weight: 700;
    font-family: 'Courier New', monospace;
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background: #f0f0f0;
  }
  .timer-box.warning { background: #fff3cd; color: #856404; }
  .timer-box.danger  { background: #f8d7da; color: #721c24; animation: blink 1s infinite; }
  @keyframes blink { 50% { opacity: 0.5; } }

  /* Nomor soal */
  .soal-nav { display: flex; flex-wrap: wrap; gap: 5px; }
  .soal-nav .btn-soal {
    width: 38px; height: 38px;
    border: 2px solid #ccc; border-radius: 6px;
    font-weight: 600; font-size: 0.8rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s;
  }
  .soal-nav .btn-soal.dijawab   { background: #28a745; color: #fff; border-color: #28a745; }
  .soal-nav .btn-soal.belum     { background: #dc3545; color: #fff; border-color: #dc3545; }
  .soal-nav .btn-soal.aktif     { background: #007bff; color: #fff; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.3); }

  /* Pilihan jawaban */
  .pilihan-item {
    border: 2px solid #dee2e6; border-radius: 8px;
    padding: 12px 16px; margin-bottom: 8px;
    cursor: pointer; transition: all 0.15s;
    display: flex; align-items: flex-start;
  }
  .pilihan-item:hover { border-color: #007bff; background: #f8f9ff; }
  .pilihan-item.selected { border-color: #007bff; background: #e7f1ff; }
  .pilihan-kode {
    width: 32px; height: 32px; border-radius: 50%;
    background: #e9ecef; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem; margin-right: 12px; flex-shrink: 0;
  }
  .pilihan-item.selected .pilihan-kode { background: #007bff; color: #fff; }

  /* Anti-cheat warning */
  .anti-cheat-warning {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
    z-index: 99999; width: 90%; max-width: 500px;
    animation: slideDown 0.3s ease-out;
  }
  @keyframes slideDown { from { opacity: 0; transform: translate(-50%, -20px); } to { opacity: 1; transform: translate(-50%, 0); } }
</style>
@endpush

@section('isi')

@if ($sesi->jenis_sesi !== 'tunggal')
<div class="alert alert-primary py-2 mb-3 d-flex align-items-center justify-content-between flex-wrap">
  <span><i class="fas fa-layer-group mr-1"></i> <strong>{{ $sesi->label_jenis_sesi }}</strong></span>
  <span class="small">{{ $sesi->jenis_sesi === 'teknis' ? 'Sesi 1 dari 2' : 'Sesi 2 dari 2' }}</span>
</div>
@endif

<div class="row">

  {{-- Panel Kiri: Navigasi --}}
  <div class="col-lg-3 col-md-4">
    <div class="card card-outline card-primary sticky-top" style="top:10px; z-index:10;">
      <div class="card-body">
        {{-- Timer --}}
        <div class="timer-box mb-3" id="timerBox">
          <div id="timerDisplay">--:--:--</div>
        </div>

        {{-- Progress --}}
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1" style="font-size:0.82rem;">
            <span>Progress</span>
            <span id="progressText">0/{{ $daftarSoal->count() }}</span>
          </div>
          <div class="progress" style="height:10px;">
            <div class="progress-bar bg-success" id="progressBar" style="width:0%"></div>
          </div>
        </div>

        {{-- Nomor Soal --}}
        <label class="small font-weight-bold mb-2">Nomor Soal</label>
        <div class="soal-nav" id="soalNav">
          @foreach ($daftarSoal as $s)
          <div class="btn-soal {{ $s['pilihan_dipilih'] ? 'dijawab' : 'belum' }}"
               data-nomor="{{ $s['urutan'] }}" onclick="goToSoal({{ $s['urutan'] }})">
            {{ $s['urutan'] }}
          </div>
          @endforeach
        </div>

        {{-- Legend --}}
        <div class="mt-3" style="font-size:0.75rem;">
          <span class="mr-2"><span style="display:inline-block;width:12px;height:12px;background:#28a745;border-radius:3px;"></span> Dijawab</span>
          <span class="mr-2"><span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:3px;"></span> Belum</span>
          <span><span style="display:inline-block;width:12px;height:12px;background:#007bff;border-radius:3px;"></span> Aktif</span>
        </div>

        {{-- Submit --}}
        <hr>
        <button class="btn btn-danger btn-block" id="btnSubmit" onclick="submitUjian()">
          <i class="fas fa-flag-checkered mr-1"></i> Submit Ujian
        </button>
      </div>
    </div>
  </div>

  {{-- Panel Kanan: Area Soal --}}
  <div class="col-lg-9 col-md-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between py-2">
        <h3 class="card-title mb-0" id="soalHeader">Soal 1 dari {{ $daftarSoal->count() }}</h3>
        <small class="text-muted">{{ $sesi->jadwal->judul }}</small>
      </div>
      <div class="card-body" id="soalArea">
        {{-- Diisi oleh JavaScript --}}
      </div>
      <div class="card-footer d-flex justify-content-between">
        <button class="btn btn-sm btn-outline-secondary" id="btnPrev" onclick="prevSoal()">
          <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
        </button>
        <button class="btn btn-sm btn-primary" id="btnNext" onclick="nextSoal()">
          Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
        </button>
      </div>
    </div>
  </div>

</div>

{{-- Form submit hidden --}}
<form id="formSubmit" method="POST" action="{{ route('ujikom-online.submit', $sesi->id) }}" style="display:none;">
  @csrf
</form>
@endsection

@push('scripts')
<script>
(function() {
  // ── Data ────────────────────────────────────────────────────────────────
  const soalData    = @json($daftarSoal->values());
  const sesiId      = {{ $sesi->id }};
  const totalSoal   = soalData.length;
  let currentSoal   = 0; // index 0-based
  let sisaDetik     = {{ $sisaWaktu }};
  const jawabUrl    = '{{ route("ujikom-online.jawab", $sesi->id) }}';
  const navigasiUrl = '{{ route("ujikom-online.navigasi", $sesi->id) }}';
  const csrfToken   = '{{ csrf_token() }}';

  // ── Timer ───────────────────────────────────────────────────────────────
  function updateTimer() {
    if (sisaDetik <= 0) {
      document.getElementById('timerDisplay').textContent = '00:00:00';
      document.getElementById('timerBox').className = 'timer-box danger';
      // Auto submit
      document.getElementById('formSubmit').submit();
      return;
    }

    sisaDetik--;
    const jam   = Math.floor(sisaDetik / 3600);
    const menit = Math.floor((sisaDetik % 3600) / 60);
    const detik = sisaDetik % 60;
    const display = `${String(jam).padStart(2,'0')}:${String(menit).padStart(2,'0')}:${String(detik).padStart(2,'0')}`;
    document.getElementById('timerDisplay').textContent = display;

    const box = document.getElementById('timerBox');
    if (sisaDetik < 300) {
      box.className = 'timer-box danger';
    } else if (sisaDetik < 600) {
      box.className = 'timer-box warning';
    } else {
      box.className = 'timer-box';
    }
  }
  updateTimer();
  setInterval(updateTimer, 1000);

  // ── Render soal ─────────────────────────────────────────────────────────
  function renderSoal() {
    const s = soalData[currentSoal];
    document.getElementById('soalHeader').textContent = `Soal ${s.urutan} dari ${totalSoal}`;

    let html = `<div class="mb-4" style="font-size:0.95rem; line-height:1.7;">${s.pertanyaan}</div>`;
    html += `<div class="pilihan-list">`;

    s.pilihan.forEach(function(p) {
      const sel = (s.pilihan_dipilih === p.kode) ? 'selected' : '';
      html += `<div class="pilihan-item ${sel}" onclick="pilihJawaban('${p.kode}', this)" data-kode="${p.kode}">
        <div class="pilihan-kode">${p.kode}</div>
        <div style="flex:1; padding-top:4px;">${p.teks}</div>
      </div>`;
    });

    html += `</div>`;
    document.getElementById('soalArea').innerHTML = html;

    // Update nav
    document.querySelectorAll('.btn-soal').forEach(function(el) {
      el.classList.remove('aktif');
      const nomor = parseInt(el.dataset.nomor);
      const sd = soalData[nomor - 1];
      if (sd.pilihan_dipilih) {
        el.classList.remove('belum');
        el.classList.add('dijawab');
      } else {
        el.classList.remove('dijawab');
        el.classList.add('belum');
      }
    });
    document.querySelector(`.btn-soal[data-nomor="${s.urutan}"]`).classList.add('aktif');

    // Update progress
    const dijawab = soalData.filter(x => x.pilihan_dipilih).length;
    document.getElementById('progressText').textContent = `${dijawab}/${totalSoal}`;
    document.getElementById('progressBar').style.width = `${Math.round(dijawab/totalSoal*100)}%`;

    // Buttons
    document.getElementById('btnPrev').style.visibility = currentSoal === 0 ? 'hidden' : 'visible';
    document.getElementById('btnNext').style.visibility = currentSoal === totalSoal - 1 ? 'hidden' : 'visible';
  }

  // ── Pilih jawaban ───────────────────────────────────────────────────────
  window.pilihJawaban = function(kode, el) {
    const s = soalData[currentSoal];
    s.pilihan_dipilih = kode;

    // UI update
    document.querySelectorAll('.pilihan-item').forEach(function(item) {
      item.classList.remove('selected');
    });
    el.classList.add('selected');

    // Update nav button
    const navBtn = document.querySelector(`.btn-soal[data-nomor="${s.urutan}"]`);
    navBtn.classList.remove('belum');
    navBtn.classList.add('dijawab');

    // Update progress
    const dijawab = soalData.filter(x => x.pilihan_dipilih).length;
    document.getElementById('progressText').textContent = `${dijawab}/${totalSoal}`;
    document.getElementById('progressBar').style.width = `${Math.round(dijawab/totalSoal*100)}%`;

    // AJAX save
    fetch(jawabUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ soal_id: s.id, jawaban: kode }),
    })
    .then(r => r.json())
    .then(data => {
      if (data.timeout) {
        document.getElementById('formSubmit').submit();
      }
    })
    .catch(err => console.error('Gagal menyimpan jawaban:', err));
  };

  // ── Navigasi ────────────────────────────────────────────────────────────
  window.goToSoal = function(nomor) {
    const dari = soalData[currentSoal].urutan;
    currentSoal = nomor - 1;
    renderSoal();

    // Log navigasi
    fetch(navigasiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify({ dari: dari, ke: nomor }),
    }).catch(() => {});
  };

  window.nextSoal = function() {
    if (currentSoal < totalSoal - 1) goToSoal(currentSoal + 2);
  };

  window.prevSoal = function() {
    if (currentSoal > 0) goToSoal(currentSoal);
  };

  // ── Submit ──────────────────────────────────────────────────────────────
  window.submitUjian = function() {
    const belumDijawab = soalData.filter(x => !x.pilihan_dipilih).length;
    let pesan = 'Yakin ingin mengakhiri dan mengumpulkan ujian?';
    if (belumDijawab > 0) {
      pesan = `Anda masih memiliki ${belumDijawab} soal yang belum dijawab.\n\nYakin ingin submit?`;
    }
    if (confirm(pesan)) {
      document.getElementById('formSubmit').submit();
    }
  };

  // ── Prevent back ────────────────────────────────────────────────────────
  history.pushState(null, null, location.href);
  window.addEventListener('popstate', function() {
    history.pushState(null, null, location.href);
  });

  // ── Warn on leave ───────────────────────────────────────────────────────
  window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = '';
  });

  // ── Init ────────────────────────────────────────────────────────────────
  renderSoal();

  // ── Fullscreen ──────────────────────────────────────────────────────────
  try {
    if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen().catch(() => {});
    }
  } catch (e) {}

  // ── Anti-Cheat ──────────────────────────────────────────────────────────
  const pelanggaranUrl = '{{ route("ujikom-online.pelanggaran", $sesi->id) }}';

  function laporkanPelanggaran(jenis) {
    fetch(pelanggaranUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify({ jenis_pelanggaran: jenis }),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.pesan) return;
        tampilkanPeringatanAntiCheat(data.pesan);
        if (data.auto_submit) {
          setTimeout(function () { document.getElementById('formSubmit').submit(); }, 2500);
        }
      })
      .catch(function () {});
  }

  function tampilkanPeringatanAntiCheat(pesan) {
    const modal = document.createElement('div');
    modal.className = 'anti-cheat-warning';
    modal.innerHTML = '<div class="alert alert-danger text-center mb-0 shadow">' +
      '<i class="fas fa-exclamation-triangle mr-1"></i> ' + pesan + '</div>';
    document.body.prepend(modal);
    setTimeout(function () { modal.remove(); }, 5000);
  }

  // Deteksi pindah tab / minimize (Page Visibility API)
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      laporkanPelanggaran('pindah_tab');
    }
  });

  window.addEventListener('blur', function () {
    laporkanPelanggaran('minimize');
  });

  // Deteksi status kamera (wajib aktif selama ujian berlangsung)
  let cameraStream = null;
  async function mulaiPantauKamera() {
    try {
      cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
      setInterval(function () {
        const track = cameraStream.getVideoTracks()[0];
        if (!track || !track.enabled || track.readyState !== 'live') {
          laporkanPelanggaran('kamera_mati');
        }
      }, 5000);
    } catch (err) {
      tampilkanPeringatanAntiCheat('Kamera wajib diaktifkan untuk mengikuti ujian ini.');
      laporkanPelanggaran('kamera_mati');
    }
  }
  mulaiPantauKamera();
})();
</script>
@endpush
