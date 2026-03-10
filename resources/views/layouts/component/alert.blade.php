@if(session('success'))
<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-check"></i> Sukses!</h5>
  {{ session('success') }}
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan!</h5>
  {{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-ban"></i> Error!</h5>
  {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
  <h5><i class="icon fas fa-ban"></i> Error Validasi!</h5>
  <ul>
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<script>
    // Fungsi untuk menampilkan konfirmasi delete menggunakan Sweet Alert
    function confirmDelete(form) {
        event.preventDefault();

        swal({
            title: 'Apakah Anda yakin?',
            text: "Data akan dihapus secara permanen!",
            icon: 'warning',
            buttons: ['Batal', 'Ya, hapus!'],
            dangerMode: true,
        }).then((result) => {
            if (result) {
                form.submit();
            }
        });
    }

    function pindah(event) {
        event.preventDefault();
        var href = event.currentTarget.href;
        swal({
            title: 'Informasi',
            text: "Mohon Tunggu..",
            icon: 'info',
            buttons: false,
            dangerMode: false,
        });

        setTimeout(() => {

            window.location.href = href;
        }, 2000); // Durasi 2 detik (2000 milidetik)
    }

    function pindahadmin(event) {
        event.preventDefault();
        var href = event.currentTarget.href;
        swal({
            title: 'Informasi',
            text: "Mohon Tunggu..",
            icon: 'info',
            buttons: false,
            dangerMode: false,
        });

        setTimeout(() => {

            window.location.href = href;
        }, 2000); // Durasi 2 detik (2000 milidetik)
    }
    function pindah2(event) {
        event.preventDefault();
        var href = event.currentTarget.href;
        swal({
            title: 'Informasi',
            text: "Anda Telah Logout Akun, Halaman Akan Diahlikan ",
            icon: 'info',
            buttons: false,
            dangerMode: false,
        });

        setTimeout(() => {

            window.location.href = href;
        }, 1000); // Durasi 2 detik (2000 milidetik)
    }



</script>
