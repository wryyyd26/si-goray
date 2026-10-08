
@extends('layouts.app')

@section('content')
    @include('components.navbar')

    <main class="contact-page">

        <!-- Header -->
        <section class="contact-hero">
            <div class="contact-container">
                <span class="about-label">HUBUNGI KAMI</span>
                <h1>Ada Pertanyaan? Hubungi Kami</h1>
                <p>
                    Kami siap menerima pertanyaan, saran, maupun masukan
                    mengenai layanan SI-GORAY.
                </p>
            </div>
        </section>

        <!-- Konten Kontak -->
        <section class="contact-content">
            <div class="contact-container contact-grid">

                <!-- Informasi Kontak -->
                <div class="contact-information">
                    <h2>Informasi Kontak</h2>
                    <p class="contact-description">
                        Silakan hubungi kami melalui informasi berikut
                        untuk mendapatkan bantuan lebih lanjut.
                    </p>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">✉</div>
                        <div>
                            <h3>Email</h3>
                            <p>info@si-goray.com</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">☎</div>
                        <div>
                            <h3>Telepon</h3>
                            <p>+62 812-0000-0000</p>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <div class="contact-info-icon">📍</div>
                        <div>
                            <h3>Alamat</h3>
                            <p>
                                Kota Mojokerto, Jawa Timur, Indonesia
                            </p>
                        </div>
                    </div>

                    <div class="contact-social">
                        <h3>Media Sosial</h3>
                        <div class="contact-social-links">
                            <a href="#" aria-label="Instagram">IG</a>
                            <a href="#" aria-label="Facebook">FB</a>
                            <a href="#" aria-label="WhatsApp">WA</a>
                        </div>
                    </div>
                </div>

                <!-- Form Pesan -->
                <div class="contact-form-card">
                    <h2>Kirim Pesan</h2>
                    <p>
                        Isi formulir berikut dan sampaikan pesanmu kepada kami.
                    </p>

                    <form action="#" method="POST" class="contact-form">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Masukkan nama lengkap"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Alamat Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="nama@email.com"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="subject">Subjek</label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                placeholder="Masukkan subjek pesan"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="message">Pesan</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                placeholder="Tuliskan pesanmu di sini..."
                                required
                            ></textarea>
                        </div>

                        <button type="submit" class="contact-submit">
                            Kirim Pesan
                        </button>
                    </form>
                </div>

            </div>
        </section>
    </main>
@endsection