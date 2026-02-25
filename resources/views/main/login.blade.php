@extends('layouts.auth.master')
@section('title','Login')

@section('isi')
<style>
 :root{
  --bg-left:#2f3e46;
  --brand:#4ea8de;
  --card-shadow: 0 10px 20px rgba(0,0,0,.08);
  --radius:16px;
  --logo-size: 120px;   /* ubah skala di sini: 96/120/150 sesuai selera */
}

.panel-left .brand{
  display:flex;
  align-items:center;
  gap:18px;
  margin-bottom:36px;
}

.panel-left .brand .logo{
  inline-size:var(--logo-size);   /* = width, lebih “tahan” override */
  block-size:var(--logo-size);    /* = height */
  flex:0 0 var(--logo-size);
  border-radius:999px;
  display:grid;
  place-items:center;
  overflow:hidden;
  background:#4ea8de1a;          /* hapus jika tak ingin latar */
}

.panel-left .brand .logo img{
  inline-size:100%;
  block-size:100%;
  object-fit:contain;
  display:block;
}

.panel-left .brand h1{
  margin:0;
  line-height:1.1;
}


  .auth-wrap{min-height:100vh;display:grid;grid-template-columns: 1.1fr 1fr;}
  @media(max-width: 992px){ .auth-wrap{grid-template-columns:1fr;} .panel-left{display:none;} }

  .panel-left{background:var(--bg-left);color:#eaf4f4;display:flex;align-items:center;justify-content:center;padding:64px;}
  .panel-left p{max-width:520px;line-height:1.7;opacity:.9}

  .panel-right{display:flex;align-items:center;justify-content:center;padding:32px;background:#f7f9fb;}
  .card-login{background:#fff; width:100%; max-width:520px; border-radius:var(--radius); box-shadow:var(--card-shadow); padding:32px 28px;}
  .card-login h2{margin:0 0 8px;font-size:24px;font-weight:700;color:#0b132b}
  .card-login .sub{color:#5b6470;margin-bottom:20px}

  .input-row{margin-bottom:14px;}
  .field{position:relative}
  .field input{
      width:100%;padding:14px 14px 14px 44px;border:1px solid #d9e1e8;border-radius:10px;
      outline:none;transition:.2s;background:#fff;
  }
  .field input:focus{border-color:#95c8ff; box-shadow:0 0 0 4px #e7f2ff}
  .icon{
      position:absolute;left:12px;top:50%;transform:translateY(-50%);width:20px;height:20px;opacity:.7
  }

  .actions{display:flex;justify-content:space-between;align-items:center;margin:8px 0 18px}
  .actions a{font-size:14px;text-decoration:none;color:#1b6fd1}
  .btn-login{
      width:100%;padding:14px 16px;border:none;border-radius:10px;background:#2c3e50;color:#fff;
      font-weight:600;letter-spacing:.2px;cursor:pointer;transition:.2s;
  }
  .btn-login:hover{filter:brightness(.95)}
  .muted{color:#6b7280;font-size:14px;text-align:center;margin-top:14px}
  .muted a{color:#1b6fd1;text-decoration:none}

  .alert{padding:12px 14px;border-radius:10px;margin-bottom:12px;font-size:14px}
  .alert-danger{background:#fff3f3;border:1px solid #ffd1d1;color:#b42323}
  .alert-success{background:#f0fff4;border:1px solid #c6f6d5;color:#2f855a}

  .footer-copy{font-size:13px;color:#8a949e;text-align:center;margin-top:16px}
</style>

<div class="auth-wrap">
  <!-- KIRI -->
  <aside class="panel-left">
    <div>
      <div class="brand">
<div class="logo">
  <img src="{{ asset('library/assets/img/logopusbinbaru.png') }}" alt="Logo Pusbin JFT" loading="lazy">
</div>

        <h1>SMART JFT</h1>
      </div>
      <h3 style="margin:0 0 8px;font-weight:600;">Sistem Manajemen Adaptif Responsif Terintegrasi - Jabatan Fungsional Transportasi </h3>
      {{-- <p>Platform terintegrasi untuk mengelola dan memproses pengajuan kenaikan jabatan fungsional tertentu secara efisien dan transparan.</p> --}}
    </div>
  </aside>

  <!-- KANAN -->
  <main class="panel-right">
    <div class="card-login">
      <h2>Selamat Datang</h2>
      <div class="sub">Silakan login untuk melanjutkan</div>

      {{-- Alert error / success --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul style="margin:0 0 0 16px;">
            @foreach ($errors->all() as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (session('status'))
        <div class="alert alert-success" role="alert">
          {{ session('status') }}
        </div>
      @endif

      <form action="{{ url('/login') }}" method="POST" novalidate>
        @csrf

        {{-- Username atau Email --}}
        <div class="input-row">
          <label for="login" style="display:block;font-size:13px;color:#6b7280;margin-bottom:6px">Username atau Email</label>
          <div class="field">
            <svg class="icon" viewBox="0 0 24 24" fill="none">
              <path d="M12 12a5 5 0 100-10 5 5 0 000 10Z" stroke="currentColor" stroke-width="1.6"/>
              <path d="M4 22a8 8 0 1116 0" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            <input id="email" name="email" type="text" value="{{ old('email') }}" placeholder="Masukkan username atau email" required autofocus>
          </div>
        </div>

        {{-- Password --}}
        <div class="input-row">
          <label for="password" style="display:block;font-size:13px;color:#6b7280;margin-bottom:6px">Password</label>
          <div class="field">
            <svg class="icon" viewBox="0 0 24 24" fill="none">
              <path d="M6 10V8a6 6 0 1112 0v2" stroke="currentColor" stroke-width="1.6"/>
              <rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            <input id="password" name="password" type="password" placeholder="Masukkan password" required>
          </div>
        </div>

        <div class="actions">
          <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#374151">
            <input type="checkbox" name="remember" value="1" style="width:16px;height:16px"> Ingat saya
          </label>
          <a href="{{ url('/password/reset') }}">Lupa password?</a>
        </div>

        <button type="submit" class="btn-login">Login</button>

        <div class="muted">
          Belum punya akun? <a href="{{ url('/register') }}">Daftar di sini</a>
        </div>
      </form>

      <div class="footer-copy">© 2025 Pusbin JFT. All rights reserved.</div>
    </div>
  </main>
</div>
@endsection
