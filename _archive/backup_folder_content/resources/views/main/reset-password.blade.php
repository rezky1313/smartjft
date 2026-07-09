@extends('layouts.auth.master')
@section('title', 'Login Page')
@section('isi')

    <div class="box">
        <div class="top-header">
            <h1 style="font-size: 30px">RESET PASSWORD</h1>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
            </div>
            <div class="container">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <h3>Reset password for {{ $email }}</h3>
                    <br>
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">


                    <div class="input-field">
                        <input type="password" name="password" class="input" id="password" placeholder="New password"
                            required>
                    </div>
                    <br>
                    <div class="input-field">
                        <input type="password" name="password_confirmation" class="input" id="password_confirmation"
                            placeholder="Confirm new password" required>
                    </div>
                    <br>
                    <div class="input-field">
                        <input type="submit" class="submit" value="Reset Password">
                    </div>

                </form>

            </div>
        </div>

@endsection

    <!-- resources/views/auth/passwords/reset.blade.php -->
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
</script>

<h3>Reset Password</h3>

<div class="card-body">
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <label for="password">New Password</label>
        <input id="password" type="password" name="password" required>

        <label for="password_confirmation">Confirm New Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <button type="submit">Reset Password</button>
    </form> --}}
