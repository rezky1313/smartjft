@extends('layouts.users.master')

@section('title', 'Bank Soal — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    {{-- Statistik --}}
    <div class="row mb-3">
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-secondary"><i class="fas fa-database"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Soal</span>
            <span class="info-box-number">{{ $statistik['total'] }}</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Aktif</span>
            <span class="info-box-number">{{ $statistik['aktif'] }}</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-warning"><i class="fas fa-edit"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Draft</span>
            <span class="info-box-number">{{ $statistik['draft'] }}</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-danger"><i class="fas fa-ban"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Nonaktif</span>
            <span class="info-box-number">{{ $statistik['nonaktif'] }}</span>
          </div>
        </div>
      </div>
    </div>

    @if ($mansoskulBelumLengkap > 0)
    <div class="alert alert-warning d-flex align-items-center">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      <div>
        <strong>{{ $mansoskulBelumLengkap }} soal Mansoskul</strong> belum punya Matra — lengkapi lewat halaman Edit supaya soal bisa dipakai dengan benar.
      </div>
    </div>
    @endif

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0"><i class="fas fa-database mr-2"></i>Bank Soal</h3>
        @hasanyrole('super_admin|admin')
        <div>
          <a href="{{ route('bank-soal.create') }}" class="btn btn-primary btn-sm mr-1">
            <i class="fas fa-plus mr-1"></i> Tambah Soal
          </a>
          <a href="{{ route('bank-soal.import') }}" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel mr-1"></i> Import Excel
          </a>
        </div>
        @endhasanyrole
      </div>

      {{-- Filter --}}
      <div class="card-body border-bottom pb-3 pt-3">
        <form method="GET" action="{{ route('bank-soal.index') }}" class="row align-items-end">
          <div class="col-md-3 col-6 mb-2">
            <label class="small font-weight-bold mb-1">Kategori</label>
            <select name="kategori_id" class="form-control form-control-sm">
              <option value="">— Semua Kategori —</option>
              @foreach ($kategoris as $k)
              <option value="{{ $k->id }}" {{ request('kategori_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 col-6 mb-2">
            <label class="small font-weight-bold mb-1">Jenis</label>
            <select name="jenis" class="form-control form-control-sm">
              <option value="">— Semua —</option>
              <option value="mansoskul" {{ request('jenis') === 'mansoskul' ? 'selected' : '' }}>Mansoskul</option>
              <option value="teknis"    {{ request('jenis') === 'teknis'    ? 'selected' : '' }}>Teknis</option>
            </select>
          </div>
          <div class="col-md-2 col-6 mb-2">
            <label class="small font-weight-bold mb-1">Matra</label>
            <select name="matra" class="form-control form-control-sm">
              <option value="">— Semua —</option>
              <option value="darat"          {{ request('matra') === 'darat'          ? 'selected' : '' }}>Darat</option>
              <option value="laut"           {{ request('matra') === 'laut'           ? 'selected' : '' }}>Laut</option>
              <option value="udara"          {{ request('matra') === 'udara'          ? 'selected' : '' }}>Udara</option>
              <option value="asdp"           {{ request('matra') === 'asdp'           ? 'selected' : '' }}>ASDP</option>
              <option value="perkeretaapian" {{ request('matra') === 'perkeretaapian' ? 'selected' : '' }}>Perkeretaapian</option>
            </select>
          </div>
          <div class="col-md-2 col-6 mb-2">
            <label class="small font-weight-bold mb-1">Taksonomi</label>
            <select name="taksonomi" class="form-control form-control-sm">
              <option value="">— Semua —</option>
              @foreach (['C1_mengingat'=>'C1 Mengingat','C2_memahami'=>'C2 Memahami','C3_menerapkan'=>'C3 Menerapkan','C4_menganalisis'=>'C4 Menganalisis','C5_mengevaluasi'=>'C5 Mengevaluasi','C6_mencipta'=>'C6 Mencipta'] as $val => $lbl)
              <option value="{{ $val }}" {{ request('taksonomi') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 col-6 mb-2">
            <label class="small font-weight-bold mb-1">Status</label>
            <select name="status" class="form-control form-control-sm">
              <option value="">— Semua —</option>
              <option value="aktif"    {{ request('status') === 'aktif'    ? 'selected' : '' }}>Aktif</option>
              <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
              <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
          </div>
          <div class="col-md-1 mb-2">
            <button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button>
          </div>
          <div class="col-auto mb-2">
            <a href="{{ route('bank-soal.index') }}" class="btn btn-default btn-sm">Reset</a>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        @if (session('success'))
        <div class="alert alert-success mx-3 mt-3 mb-0 py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger mx-3 mt-3 mb-0 py-2">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th width="60">ID</th>
                <th>Pertanyaan</th>
                <th width="140">Kategori / Matra</th>
                <th width="110" class="text-center">Taksonomi</th>
                <th width="80" class="text-center">Jenis</th>
                <th width="70" class="text-center">Status</th>
                <th width="120" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($soals as $i => $s)
              <tr>
                <td class="text-center">{{ $soals->firstItem() + $i }}</td>
                <td class="text-muted small">#{{ $s->id }}</td>
                <td>{{ Str::limit($s->pertanyaan, 90) }}</td>
                <td>
                  @if ($s->jenis === 'teknis')
                    <small>{{ $s->kategori?->nama ?? '-' }}</small>
                  @else
                    @if ($s->matra)
                      <small>{{ $s->label_matra }}</small>
                    @else
                      <span class="badge badge-warning" title="Matra belum diisi"><i class="fas fa-exclamation-triangle mr-1"></i>Belum Lengkap</span>
                    @endif
                  @endif
                </td>
                <td class="text-center">
                  @php $badgeTaksonomi = ['C1_mengingat'=>'secondary','C2_memahami'=>'info','C3_menerapkan'=>'primary','C4_menganalisis'=>'purple','C5_mengevaluasi'=>'warning','C6_mencipta'=>'danger']; @endphp
                  <span class="badge" style="background:{{ ['C1_mengingat'=>'#6c757d','C2_memahami'=>'#17a2b8','C3_menerapkan'=>'#007bff','C4_menganalisis'=>'#6f42c1','C5_mengevaluasi'=>'#fd7e14','C6_mencipta'=>'#dc3545'][$s->taksonomi_bloom] ?? '#6c757d' }};color:#fff;font-size:0.7rem;">
                    {{ explode('_', $s->taksonomi_bloom)[0] }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge badge-{{ $s->jenis === 'mansoskul' ? 'light border' : 'info' }}" style="font-size:0.7rem;">
                    {{ $s->label_jenis }}
                  </span>
                </td>
                <td class="text-center">
                  @php $badgeStatus = ['aktif'=>'success','draft'=>'secondary','nonaktif'=>'danger']; @endphp
                  <span class="badge badge-{{ $badgeStatus[$s->status] ?? 'secondary' }}">{{ $s->label_status }}</span>
                </td>
                <td class="text-center">
                  <a href="{{ route('bank-soal.show', $s->id) }}" class="btn btn-xs btn-outline-info" title="Lihat"><i class="fas fa-eye"></i></a>
                  @hasanyrole('super_admin|admin')
                  <a href="{{ route('bank-soal.edit', $s->id) }}" class="btn btn-xs btn-outline-warning" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                  @if ($s->status === 'draft' || $s->status === 'nonaktif')
                  <form action="{{ route('bank-soal.approve', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Aktifkan soal ini?')">
                    @csrf <button class="btn btn-xs btn-outline-success" title="Aktifkan"><i class="fas fa-check"></i></button>
                  </form>
                  @elseif ($s->status === 'aktif')
                  <form action="{{ route('bank-soal.nonaktifkan', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan soal ini?')">
                    @csrf <button class="btn btn-xs btn-outline-secondary" title="Nonaktifkan"><i class="fas fa-ban"></i></button>
                  </form>
                  @endif
                  <form action="{{ route('bank-soal.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus soal ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                  </form>
                  @endhasanyrole
                </td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Belum ada soal yang sesuai filter.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @if ($soals->hasPages())
      <div class="card-footer">{{ $soals->links() }}</div>
      @endif
    </div>

  </div>
</div>
@endsection
