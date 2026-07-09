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
            text: "Halaman Akan Berpindah Sebentar Lagi ",
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
            text: "Halaman Akan Berpindah Sebentar Lagi ",
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
        }, 2000); // Durasi 2 detik (2000 milidetik)
    }



</script>
