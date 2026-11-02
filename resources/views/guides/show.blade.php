<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $guide->title }} | SI-GORAY</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        .navbar {
            padding: 18px 5%;
            background: white;
            border-bottom: 1px solid #e5eaf2;
        }

        .brand {
            color: #155eef;
            text-decoration: none;
            font-size: 21px;
            font-weight: 800;
        }

        .container {
            width: min(1000px, 90%);
            margin: 35px auto 60px;
        }

        .back {
            display: inline-block;
            margin-bottom: 24px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .panel {
            padding: 30px;
            margin-bottom: 24px;
            border: 1px solid #e5eaf2;
            border-radius: 16px;
            background: white;
            box-shadow: 0 5px 20px #17203308;
        }

        .label {
            display: inline-block;
            padding: 7px 12px;
            margin-bottom: 15px;
            border-radius: 20px;
            background: #eaf1ff;
            color: #155eef;
            font-size: 12px;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 16px;
            font-size: clamp(26px, 4vw, 36px);
            overflow-wrap: anywhere;
        }

        h2 {
            margin-top: 0;
            font-size: 22px;
        }

        .description {
            color: #64748b;
            line-height: 1.9;
            overflow-wrap: anywhere;
        }

        .viewer {
            display: grid;
            place-items: center;
            min-height: 260px;
            padding: 28px;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            text-align: center;
        }

        .viewer-icon {
            margin-bottom: 15px;
            font-size: 48px;
        }

        .viewer p {
            color: #64748b;
            line-height: 1.8;
        }

        .steps {
            display: grid;
            gap: 16px;
        }

        .step {
            padding: 18px;
            border: 1px solid #e5eaf2;
            border-radius: 12px;
            background: #fff;
        }

        .step h3 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .step p {
            margin: 0;
            color: #64748b;
            line-height: 1.8;
            white-space: pre-line;
        }

        .empty {
            padding: 22px;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            line-height: 1.8;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            border: none;
            border-radius: 9px;
            background: #155eef;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        footer {
            padding: 25px 15px;
            background: #10254b;
            color: #dbe6ff;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .container {
                width: 92%;
                margin-top: 24px;
            }

            .panel {
                padding: 20px;
            }

            .viewer {
                min-height: 220px;
                padding: 18px;
            }
        }


        model-viewer {
            position: relative;
            overflow: hidden;
        }

        .ar-button {
            position: absolute;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            width: max-content;
            max-width: calc(100% - 24px);
            padding: 13px 20px;
            border: none;
            border-radius: 10px;
            background: #155eef;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 5px 15px #17203325;
        }

        .ar-button:hover {
            background: #1248bd;
        }

        @media (max-width: 600px) {
            model-viewer {
                height: 350px !important;
            }

            .ar-button {
                font-size: 13px;
                padding: 12px 15px;
            }
        }


        .ar-button {
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            white-space: normal;
            text-align: center;
        }

        model-viewer {
            position: relative;
            max-width: 100%;
        }

        @media (max-width: 600px) {
            model-viewer {
                height: 350px !important;
            }
        }
    </style>



    <script type="module"
        src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.4.0/model-viewer.min.js">
    </script>

</head>

<body>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="brand">SI-GORAY</a>
    </nav>

    <main class="container">
        <a href="{{ route('guides.index') }}" class="back">
            ← Kembali ke Daftar Panduan
        </a>

        <section class="panel">
            <span class="label">PANDUAN AR</span>

            <h1>{{ $guide->title }}</h1>

            <div class="description">
                {{ $guide->description ?: 'Belum ada deskripsi untuk panduan ini.' }}
            </div>
        </section>



        <section class="panel">
            <h2>Pratinjau Augmented Reality</h2>

            <p class="description">
                Putar model 3D menggunakan jari atau mouse. Pada HP yang mendukung AR,
                tekan tombol di bawah untuk menampilkan model di lingkungan nyata.
            </p>

            <div style="position:relative;width:100%;overflow:hidden;border-radius:14px;background:#f4f7fb;">

                <model-viewer
                    id="arViewer"
                    src="{{ asset('models/gor-ayani.glb') }}"
                    alt="Model 3D GOR A. Yani"
                    camera-controls
                    touch-action="pan-y"
                    auto-rotate
                    ar
                    ar-modes="scene-viewer webxr quick-look"
                    ar-placement="floor"
                    ar-scale="auto"
                    shadow-intensity="1"
                    exposure="1"
                    environment-image="neutral"
                    loading="eager"
                    reveal="auto"
                    style="display:block;width:100%;height:450px;">

                    <div slot="progress-bar"
                        style="position:absolute;left:10%;bottom:10px;width:80%;height:5px;background:#dbeafe;border-radius:10px;overflow:hidden;">
                        <div id="modelProgress"
                            style="width:0;height:100%;background:#2563eb;transition:width .2s;">
                        </div>
                    </div>

                    <button slot="ar-button" class="ar-button" type="button">
                        <span>◉</span> Lihat di Ruang Saya (AR)
                    </button>

                </model-viewer>
            </div>

            <p id="arStatus" class="description" aria-live="polite">
                Sedang memuat model 3D...
            </p>

            <p style="font-size:13px;color:#64748b;line-height:1.7;">
                Fitur AR memerlukan perangkat dan browser yang kompatibel.
                Pada HP, gunakan HTTPS atau lingkungan lokal yang didukung.
            </p>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const viewer = document.getElementById('arViewer');
                const status = document.getElementById('arStatus');
                const progress = document.getElementById('modelProgress');

                if (!viewer || !status) return;

                viewer.addEventListener('progress', (event) => {
                    if (progress) {
                        const percent = Math.round(
                            event.detail.totalProgress * 100
                        );

                        progress.style.width = `${percent}%`;
                    }
                });

                viewer.addEventListener('load', () => {
                    if (progress) {
                        progress.style.width = '100%';
                    }

                    status.textContent =
                        'Model 3D berhasil dimuat. Kamu dapat memutar model atau mencoba AR.';
                });

                viewer.addEventListener('error', (event) => {
                    console.error('Gagal memuat model 3D:', event);

                    status.textContent =
                        'Model gagal dimuat. Periksa Console browser dan validitas file GLB.';
                });

                viewer.addEventListener('ar-status', (event) => {
                    const currentStatus = event.detail.status;

                    if (currentStatus === 'session-started') {
                        status.textContent =
                            'AR aktif. Arahkan kamera ke permukaan yang sesuai.';
                    } else if (currentStatus === 'failed') {
                        status.textContent =
                            'AR gagal dimulai. Periksa dukungan perangkat, browser, dan izin yang diperlukan.';
                    } else if (currentStatus === 'not-presenting') {
                        status.textContent =
                            'AR tidak aktif. Model 3D tetap dapat dilihat pada pratinjau.';
                    }
                });

                viewer.addEventListener('camera-change', () => {
                    // Event ini tersedia jika pengguna mengubah sudut pandang model.
                });
            });
        </script>

        <section class="panel">
            <h2>Langkah-Langkah Panduan</h2>

            @if ($guide->steps->isNotEmpty())
            <div class="steps">
                @foreach ($guide->steps as $step)
                <article class="step">
                    <h3>
                        Langkah {{ $loop->iteration }}
                        @if (!empty($step->title))
                        — {{ $step->title }}
                        @endif
                    </h3>

                    <p>{{ $step->description ?? $step->content ?? 'Detail langkah belum tersedia.' }}</p>
                </article>
                @endforeach
            </div>
            @else
            <div class="empty">
                Belum ada langkah panduan yang ditambahkan untuk panduan ini.
            </div>
            @endif
        </section>
    </main>

    <footer>
        SI-GORAY · Sistem Informasi E-Ticket GOR A. Yani
    </footer>
</body>

</html>