<!-- SEKSYEN TEMPAHAN (RESERVASI) -->
<section class="reservation-section">
    <div class="reservation-container">
        <div class="reservation-content">
            <h2>Ingin Reservasi GOR A.Yani ?</h2>
            <p>Cocok untuk turnament, pameran, konser atau acara besar anda. Hubungi kami untuk lebih lanjut.</p>
            <a href="/contact" class="btn-hubungi">Hubungi Kami</a>
        </div>
        <div class="reservation-image">
            <!-- Ganti dengan nama fail vektor ilustrasi anda -->
            <img src="{{ asset('storage/images/VECTOR-1.png') }}" alt="Ilustrasi Reservasi">
        </div>
    </div>
</section>

<!-- (FOOTER) -->
<footer class="footer-section">
    <div class="footer-container">
        <!-- Kolum 1: Logo & Info -->
        <div class="footer-col">
            <img src="{{ asset('storage/images/Logo-Si-goray.png') }}" alt="SI-GORAY Logo" class="footer-logo">
            <p class="footer-text">Sistem Informasi E-Ticket<br>GOR A.Yani</p>
            <div class="social-icons">
                <a href="#" class="sosmed-link" aria-label="Facebook">
                    <i class="fi fi-brands-facebook"></i>
                </a>
                <a href="#" class="sosmed-link" aria-label="YouTube">
                    <i class="fi fi-brands-youtube"></i>
                </a>
                <a href="#" class="sosmed-link" aria-label="Instagram">
                    <i class="fi fi-brands-instagram"></i>
                </a>
            </div>
        </div>

        <!-- Kolum 2: Kontak -->
        <div class="footer-col">
            <h3>Kontak Kami</h3>
            <ul class="contact-list">
                <li><span class="material-symbols-outlined">mail</span> Email: sigoray@gmail.com</li>
                <li><span class="material-symbols-outlined">call</span> Telephone: (0321) 08xxxxxx</li>
                <li><span class="material-symbols-outlined">chat</span> Whatsapp: 08xxxxxx</li>
            </ul>
        </div>

        <!-- Kolum 3: Alamat -->
        <div class="footer-col">
            <h3>Alamat Kami</h3>
            <p>Jl. Gajah Mada No.149,<br>Mergelo, Balongsari, Kec.<br>Magersari, Kota Mojokerto,<br>Jawa Timur 61314</p>
        </div>

            <!-- KOLOM 4: GOOGLE MAPS -->
            <div class="footer-col map-col">
                <h3>Lokasi GOR A. Yani</h3>

                <div class="map-wrapper">
                    <iframe
                        src="https://maps.google.com/maps?q=-7.4711254,112.4398327&z=18&output=embed"
                        width="100%"
                        height="220"
                        style="border: 0;"
                        allowfullscreen
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Lokasi GOR A. Yani Mojokerto">
                    </iframe>
                </div>

                <a
                    href="https://maps.app.goo.gl/oQz6vdyLz3duyock8"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="map-direction">
                    <span class="material-symbols-rounded">location_on</span>
                    Lihat Petunjuk Arah
                </a>
            </div>

        </div>

        <div class="footer-bottom">
            <p>Copyright sigoray.go.id 2026</p>
        </div>
</footer>