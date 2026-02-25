@extends('layouts.main.master')
@section('title','Halaman Utama')
@section('isi')
<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand" href="#page-top">Pusat Pembinaan Jabatan Fungsional Transportasi</a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            Menu
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#about">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link" href="#projects">Pratinjau</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Kontak</a></li>
            </ul>
        </div>
    </div>
</nav>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $item)
                <li>{{$item}}</li>
            @endforeach
        </ul>
    </div>
@endif

<header class="masthead">
    <div class="container px-4 px-lg-5 d-flex h-100 align-items-center justify-content-center">
        <div class="d-flex justify-content-center">
            <div class="text-center">
                <h1 class="mx-auto my-0 text-uppercase">SISTEM INFORMASI MONITORING JFT</h1>
                <br>
                <h2 class="text-white-50 mx-auto mt-2 mb-5">Selamat Datang</h2>
                <a class="btn-transparent" href="login">Login</a>
                <a class="btn-transparent" href="register">Register</a>
            </div>
        </div>
    </div>
</header>
<!-- About-->
<section class="about-section text-center" id="about">
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-4">PUSBIN JFT</h2>
                <p class="text-white-50">
                    Selamat datang di halaman sistem informasi monitoring jabatan fungsional transportasi.
                </p>
            </div>
        </div>
        <img class="img-fluid" src="/library/assets/img/gedung-kemenhub.jpg" alt="..." />
    </div>
</section>
<!-- Projects-->
<section class="projects-section bg-light" id="projects">
    <div class="container px-4 px-lg-5">
        <!-- Featured Project Row-->
        <div class="row gx-0 mb-4 mb-lg-5 align-items-center">
            <div class="col-xl-8 col-lg-7"><img class="img-fluid mb-3 mb-lg-0" src="/library/assets/img/gedung-kemenhub.jpg" alt="..." /></div>
            <div class="col-xl-4 col-lg-5">
                <div class="featured-text text-center text-lg-left">
                    <h4>Pengertian</h4>
                    <p class="text-black-50 mb-0 justify">
                        .....
                    </p>
                </div>
            </div>
        </div>
        <!-- Project One Row-->
        <div class="row gx-0 mb-5 mb-lg-0 justify-content-center">
            <div class="col-lg-6"><img class="img-fluid" src="/library/assets/img/gedung-kemenhub.jpg" alt="..." /></div>
            <div class="col-lg-6">
                <div class="bg-black text-center h-100 project">
                    <div class="d-flex h-100">
                        <div class="project-text w-100 my-auto text-center text-lg-left">
                            <h4 class="text-white">Pencarian Lokasi</h4>
                            <p class="mb-0 text-white-50 justify">
                                Pengguna dapat mencari lokasi pemangku jft
                            </p>
                            <hr class="d-none d-lg-block mb-0 ms-0" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Project Two Row-->
        <div class="row gx-0 justify-content-center">
            <div class="col-lg-6"><img class="img-fluid" src="/library/assets/img/gedung-kemenhub.jpg" alt="..." /></div>
            <div class="col-lg-6 order-lg-first">
                <div class="bg-black text-center h-100 project">
                    <div class="d-flex h-100">
                        <div class="project-text w-100 my-auto text-center text-lg-right">
                            <h4 class="text-white">Pemetaan</h4>
                            <p class="mb-0 text-white-50 justify">
                               .... <!-- Aplikasi ini akan menampilkan peta interaktif Kota Malang yang menunjukkan lokasi rumah sakit dan puskesmas di wilayah tersebut.
                                Peta ini dapat di-zoom in atau out untuk melihat area yang lebih kecil atau lebih besar. -->
                            </p>
                            <hr class="d-none d-lg-block mb-0 me-0" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 col-lg-5">

<!-- Bootstrap 5 starter form -->
<form id="contact">
<h1 class="center fw-bolder">Kontak Kami</h1>
<!-- Name input -->
<div class="mb-3">
<label class="form-label" for="name">Name</label>
<input class="form-control" id="name" type="text" placeholder="Name" data-sb-validations="required" />
</div>

<!-- Email address input -->
<div class="mb-3">
<label class="form-label" for="emailAddress">Email Address</label>
<input class="form-control" id="emailAddress" type="email" placeholder="Email Address" data-sb-validations="required, email" />
</div>

<!-- Message input -->
<div class="mb-3">
<label class="form-label" for="message">Message</label>
<textarea class="form-control" id="message" type="text" placeholder="Message" style="height: 10rem;" data-sb-validations="required"></textarea>
</div>

<!-- Form submit button -->
<div class="d-grid">
<button class="btn btn-primary btn-lg" type="submit">Submit</button>
</div>

</form>

</div>
<!--===============================================================================================-->
<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
<script src="vendor/bootstrap/js/popper.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
<script src="vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
<script src="vendor/tilt/tilt.jquery.min.js"></script>
<script >
    $('.js-tilt').tilt({
        scale: 1.1
    })
</script>
<!--===============================================================================================-->
<script src="js/main.js"></script>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-23581568-13"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());

gtag('config', 'UA-23581568-13');
</script>
</div>
    </body>
</html>

@endsection


