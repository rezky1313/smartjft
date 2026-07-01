@extends('layouts.users.master')

@section('title', 'Kategori Soal — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-tags mr-2"></i>Kategori Soal</h3>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambah">
          <i class="fas fa-plus mr-1"></i> Tambah Kategori
        </button>
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
                <th>Nama Kategori</th>
                <th>Jabatan</th>
                <th width="110">Jenjang</th>
                <th width="80">Matra</th>
                <th width="100">Bidang</th>
                <th width="80" class="text-center">Jml Soal</th>
                <th width="80" class="text-center">Status</th>
                <th width="110" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($kategoris as $i => $k)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td><strong>{{ $k->nama }}</strong>
                  @if($k->deskripsi)<br><small class="text-muted">{{ $k->deskripsi }}</small>@endif
                </td>
                <td><small>{{ $k->jabatan ?? '—' }}</small></td>
                <td>
                  <span class="badge badge-light border">{{ $k->label_jenjang }}</span>
                </td>
                <td>
                  @php $warnaMatra = ['darat'=>'success','laut'=>'primary','udara'=>'info','asdp'=>'warning','umum'=>'secondary']; @endphp
                  <span class="badge badge-{{ $warnaMatra[$k->matra] ?? 'secondary' }}">{{ $k->label_matra }}</span>
                </td>
                <td><small>{{ $k->bidang ?? '—' }}</small></td>
                <td class="text-center">
                  <span class="badge badge-{{ $k->soal_count > 0 ? 'primary' : 'secondary' }}">{{ $k->soal_count }}</span>
                </td>
                <td class="text-center">
                  <span class="badge badge-{{ $k->aktif ? 'success' : 'danger' }}">
                    {{ $k->aktif ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-center">
                  <button class="btn btn-xs btn-outline-warning" title="Edit"
                          onclick="openEdit({{ $k->id }}, {{ json_encode($k->nama) }}, {{ json_encode($k->jabatan) }}, '{{ $k->jenjang }}', '{{ $k->matra }}', {{ json_encode($k->bidang) }}, {{ json_encode($k->deskripsi) }}, {{ $k->aktif ? 'true' : 'false' }})">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <form action="{{ route('soal-kategori.destroy', $k->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus kategori {{ $k->nama }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-outline-danger" title="Hapus" {{ $k->soal_count > 0 ? 'disabled' : '' }}>
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="9" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form action="{{ route('soal-kategori.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Kategori Soal</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          @include('soal_kategori._form')
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Modal Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="formEdit" method="POST">
      @csrf @method('PUT')
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title"><i class="fas fa-pencil-alt mr-2"></i>Edit Kategori</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          @include('soal_kategori._form', ['prefix' => 'edit_'])
          <div class="form-group mt-3 mb-0">
            <label class="font-weight-bold">Status</label>
            <div>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="edit_aktif_ya" name="aktif" value="1" class="custom-control-input">
                <label class="custom-control-label" for="edit_aktif_ya">Aktif</label>
              </div>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="edit_aktif_tidak" name="aktif" value="0" class="custom-control-input">
                <label class="custom-control-label" for="edit_aktif_tidak">Nonaktif</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function openEdit(id, nama, jabatan, jenjang, matra, bidang, deskripsi, aktif) {
  const form = document.getElementById('formEdit');
  form.action = '/soal-kategori/' + id;

  form.querySelector('[name="nama"]').value       = nama || '';
  form.querySelector('[name="jabatan"]').value    = jabatan || '';
  form.querySelector('[name="jenjang"]').value    = jenjang || '';
  form.querySelector('[name="matra"]').value      = matra || '';
  form.querySelector('[name="bidang"]').value     = bidang || '';
  form.querySelector('[name="deskripsi"]').value  = deskripsi || '';

  document.getElementById('edit_aktif_ya').checked    = aktif === true;
  document.getElementById('edit_aktif_tidak').checked = aktif === false;

  $('#modalEdit').modal('show');
}
</script>
@endpush
