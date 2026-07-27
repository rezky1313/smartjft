@extends('layouts.users.master')
@section('title', 'Data Formasi Jabatan')
@section('isi')

<div class="preview-card">
  <div class="preview-header">
    <span class="preview-header-title">Tambah Pemangku JFT</span>
    <span class="preview-header-subtitle d-block">Lengkapi data di bawah ini</span>
  </div>
  <div class="preview-body">

    @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Whoops!</strong> Ada masalah dengan inputanmu.<br><br>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="{{ route('user.sdm.store') }}" method="POST">
        @csrf
        @include('sdm.form', ['mode' => 'create'])
    </form>

  </div>
</div>
@endsection
