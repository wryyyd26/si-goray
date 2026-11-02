    <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan AR - SI-GORAY</title>

   
</head>

<body>
    <nav class="navbar">
        <strong>SI-GORAY</strong>
    </nav>

    <header class="hero">
        <span class="label">AR GUIDE · PANDUAN DIGITAL</span>
        <h1>Panduan Augmented Reality</h1>
        <p>
            Pelajari panduan penggunaan fitur Augmented Reality untuk
            membantu menemukan lokasi dan mengenal fasilitas GOR A. Yani
            melalui pengalaman digital yang interaktif.
        </p>
    </header>

    <main class="container">
        <div class="section-heading">
            <h2>Panduan yang Tersedia</h2>
            <p>Pilih panduan untuk melihat informasi dan langkah penggunaannya.</p>
        </div>

        @if ($guides->isNotEmpty())
            <div class="guide-grid">
                @foreach ($guides as $guide)
                    <article class="guide-card">
                        <div class="card-content">
                            <div class="guide-icon">📱</div>

                            <h3>{{ $guide->title }}</h3>

                            <p>
                                {{ $guide->description ?: 'Pelajari langkah-langkah penggunaan panduan AR ini.' }}
                            </p>

                            <div class="guide-meta">
                                Panduan digital SI-GORAY
                            </div>

                            <a
                                class="guide-link"
                                href="{{ route('guides.show', $guide->slug) }}"
                            >
                                Lihat Panduan →
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <h3>Belum Ada Panduan AR</h3>
                <p>
                    Panduan belum tersedia untuk saat ini.
                    Silakan kembali lagi setelah admin menambahkan panduan.
                </p>
            </div>
        @endif
    </main>

    <footer>
        &copy; {{ date('Y') }} SI-GORAY · Sistem Informasi E-Ticket GOR A. Yani
    </footer>
</body>
</html>
