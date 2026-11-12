
@extends('layouts.app')

@section('title', 'Jelajahi GOR 3D & AR | SI-GORAY')

@section('content')
@include('components.navbar')

<link rel="stylesheet" href="{{ asset('css/gor-ar.css') }}">

<main class="gor-ar-page">
    <div class="gor-ar-container">

        <header class="gor-ar-heading">
            <a href="{{ route('home') }}" class="gor-ar-back">
                &larr; Kembali ke Beranda
            </a>

            <span class="gor-ar-kicker">
                GOR SENI MAJAPAHIT
            </span>

            <h1>Jelajahi GOR dalam 3D</h1>

            <p>
                Kenali denah dan fasilitas GOR melalui
                model 3D interaktif. Putar, perbesar,
                dan jelajahi bangunan dari berbagai sudut.
            </p>
        </header>

        <section class="gor-ar-card">

            <div class="gor-ar-card-header">
                <div>
                    <h2>Denah 3D GOR</h2>
                    <p>Model interaktif fasilitas GOR.</p>
                </div>

                <span class="gor-ar-indicator">
                    3D INTERAKTIF
                </span>
            </div>

            <div
                id="gor-3d-viewer"
                class="gor-ar-viewer"
                data-model-url="{{ asset('models/denah-gor.glb') }}"
            >
                <div id="gor-ar-loading" class="gor-ar-loading">
                    Memuat model 3D GOR...
                </div>
            </div>

            <div class="gor-ar-toolbar">
                <button type="button" id="gor-reset-view">
                    Atur Ulang Tampilan
                </button>

                <div id="gor-ar-button-container"></div>
            </div>

            <p id="gor-ar-status" class="gor-ar-status" role="status">
                Geser untuk memutar model, scroll atau cubit
                untuk zoom, dan gunakan dua jari untuk menggeser.
            </p>

        </section>

        <section class="gor-ar-guide">
            <h2>Cara Menggunakan</h2>

            <div class="gor-ar-guide-grid">

                <article class="gor-ar-guide-item">
                    <span class="material-symbols-rounded">
                        3d_rotation
                    </span>
                    <h3>Putar Model</h3>
                    <p>Geser model untuk melihat setiap sisi GOR.</p>
                </article>

                <article class="gor-ar-guide-item">
                    <span class="material-symbols-rounded">
                        zoom_in
                    </span>
                    <h3>Perbesar Model</h3>
                    <p>Gunakan scroll atau gerakan mencubit pada layar.</p>
                </article>

                <article class="gor-ar-guide-item">
                    <span class="material-symbols-rounded">
                        view_in_ar
                    </span>
                    <h3>Mode AR</h3>
                    <p>
                        Pada perangkat yang mendukung,
                        arahkan kamera ke permukaan datar
                        dan ketuk untuk menempatkan model.
                    </p>
                </article>

            </div>
        </section>

    </div>
</main>

@include('components.footer')

<script type="importmap">
{
    "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.187.0/build/three.module.js",
        "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.187.0/examples/jsm/"
    }
}
</script>

<script type="module" src="{{ asset('js/gor-ar.js') }}"></script>
@endsection
