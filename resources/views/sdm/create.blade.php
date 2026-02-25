@extends('layouts.users.master')
@section('title', 'Data Formasi Jabatan')
@section('isi')
{{-- @section('content') --}}
<div class="container mt-3">
    <h4>Tambah SDM</h4>
    <form action="{{ route('user.sdm.store') }}" method="POST" class="mt-3">
        @csrf
        @include('sdm.form', ['mode' => 'create'])
    </form>
</div>
@endsection
