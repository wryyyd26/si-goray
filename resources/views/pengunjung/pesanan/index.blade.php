
@extends('layouts.app')

@section('title', 'Pesanan Saya | SI-GORAY')

@section('content')

@include('components.navbar')

<main class="pesanan-page">

    <div class="pesanan-container">

        <!-- HEADER -->
        <header class="pesanan-header">

            <h1>Pesanan Saya</h1>

            <p>
                Lihat dan pantau informasi pemesanan
                kegiatan reguler maupun event.
            </p>

        </header>

        <!-- CONTENT -->
        <section class="pesanan-content">

            <!-- SECTION TITLE -->
            <div class="pesanan-content-header">

                <div>
                    <h2>Daftar Pesanan</h2>

                    <p>
                        Informasi pesanan kegiatan Anda
                        akan ditampilkan di halaman ini.
                    </p>
                </div>

            </div>

            @include('pengunjung.pesanan.daftar')

        </section>

    </div>

</main>

@include('components.footer')

@endsection
