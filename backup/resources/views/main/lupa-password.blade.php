
@extends('layouts.auth.master')
@section('title', 'Login Page')
@section('isi')

    <div class="box">
        <div class="top-header">
            <h1 style="font-size: 30px">PASSWORD RECOVERY</h1>
            <h3>We will send you a link to reset your password</h3>

            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->has('email'))
                    <div class="alert alert-danger" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif
            </div>
            <div class="container">
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="input-field">
                        <input type="email" name="email" class="input" id="email" placeholder="Email" required autofocus>
                    </div>
                    <br>
                    <div class="input-field">
                        <input type="submit" class="submit" value="Send link">
                    </div>

                </form>

            </div>
        </div>

    @endsection


{{-- @extends('layout.account.master')
@section('title', 'Login Page')
@section('isi') --}}
<!-- resources/views/auth/passwords/email.blade.php -->
{{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script> --}}

{{-- @if (session('status'))
<div>{{ session('status') }}</div>
@endif --}}
{{-- <div class="box">
    <div class="container">
        <div class="top-header">
            <h1>Reset Password</h1>


            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->has('email'))
                    <div class="alert alert-danger" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif
            </div>



            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="input-field">
                    <input type="email" name="email" class="input" id="email" placeholder="Email" required
                        autofocus>
                </div> --}}
                                                    {{-- <input id="email" class="input" type="email" name="email" required autofocus> --}}
                {{-- <div class="input-field">
                    <input type="submit" class="submit" value="Send Reset Link">
                </div> --}}
                                                    {{-- <button type="submit">Send Reset Link</button> --}}
            {{-- </form>
        </div>
    </div>
</div> --}}



