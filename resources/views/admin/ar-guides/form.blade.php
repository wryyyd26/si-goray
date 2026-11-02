<WritingBlock id="62481" variant="document">
    <!DOCTYPE html>

    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $isEdit ? 'Edit' : 'Tambah' }} Panduan AR - SI-GORAY</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f1f5f9;
                color: #172033;
            }

            header {
                background: #123b70;
                color: white;
                padding: 20px 6%;
                font-size: 22px;
                font-weight: bold;
            }

            main {
                width: min(850px, 92%);
                margin: 35px auto;
            }

            .card {
                background: white;
                border-radius: 14px;
                padding: 30px;
                box-shadow: 0 6px 25px #0f172a0d;
            }

            h1 {
                margin-top: 0;
                font-size: 27px;
            }

            .subtitle {
                color: #64748b;
                line-height: 1.6;
                margin-bottom: 28px;
            }

            .field {
                margin-bottom: 22px;
            }

            label {
                display: block;
                font-weight: bold;
                margin-bottom: 9px;
            }

            input[type="text"],
            input[type="number"],
            input[type="file"],
            textarea {
                display: block;
                width: 100%;
                padding: 12px 14px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                font: inherit;
                background: white;
            }

            textarea {
                min-height: 130px;
                resize: vertical;
            }

            input:focus,
            textarea:focus {
                outline: 2px solid #bfdbfe;
                border-color: #2563eb;
            }

            .checkbox {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .checkbox input {
                width: 18px;
                height: 18px;
            }

            .checkbox label {
                margin: 0;
            }

            .hint {
                display: block;
                color: #64748b;
                font-size: 13px;
                margin-top: 7px;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 28px;
            }

            .button {
                border: 0;
                border-radius: 8px;
                padding: 12px 18px;
                font-weight: bold;
                font-size: 14px;
                cursor: pointer;
                text-decoration: none;
            }

            .primary {
                background: #1769c2;
                color: white;
            }

            .secondary {
                background: #e2e8f0;
                color: #172033;
            }

            .errors {
                padding: 15px;
                margin-bottom: 22px;
                border-radius: 8px;
                background: #fee2e2;
                color: #991b1b;
            }

            @media (max-width: 600px) {
                .card {
                    padding: 20px;
                }
            }
        </style>

    </head>

    <body>
        <header>SI-GORAY · Admin</header>

        <main>
            <section class="card">
                <h1>{{ $isEdit ? 'Edit Panduan AR' : 'Tambah Panduan AR' }}</h1>

                <p class="subtitle"> Lengkapi informasi panduan Augmented Reality untuk halaman publik SI-GORAY. </p>

                @if ($errors->any()) <div class="errors"> <strong>Periksa kembali data berikut:</strong>
                    <ul> @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
                </div> @endif

                <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.ar-guides.update', $guide) : route('admin.ar-guides.store') }}"> @csrf

                    @if ($isEdit) @method('PUT') @endif

                    <div class="field"> <label for="title">Judul panduan *</label> <input id="title" type="text" name="title" value="{{ old('title', $guide->title ?? '') }}" required> </div>

                    <div class="field"> <label for="slug">Slug URL *</label> <input id="slug" type="text" name="slug" value="{{ old('slug', $guide->slug ?? '') }}" placeholder="panduan-ar-gor" required> </div>

                    <div class="field"> <label for="description">Deskripsi</label> <textarea id="description" name="description">{{ old('description', $guide->description ?? '') }}</textarea> </div>

                    <div class="field"> <label for="model_file">File model AR (GLB/GLTF)</label> <input id="model_file" type="file" name="model_file" accept=".glb,.gltf"> <span class="hint"> Kosongkan jika tidak ingin mengganti file yang sudah ada. Format yang diizinkan mengikuti validasi controller. </span> </div>

                    <div class="field"> <label for="thumbnail_file">Gambar thumbnail</label> <input id="thumbnail_file" type="file" name="thumbnail_file" accept="image/*"> <span class="hint"> Pilih gambar pratinjau panduan AR. </span> </div>

                    <div class="field"> <label for="sort_order">Urutan tampilan</label> <input id="sort_order" type="number" name="sort_order" min="0" value="{{ old('sort_order', $guide->sort_order ?? 0) }}"> </div>

                    <div class="field checkbox"> <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $guide->is_active ?? true)) > <label for="is_active">Aktifkan panduan</label> </div>

                    <div class="actions"> <button type="submit" class="button primary"> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Panduan' }} </button>

                        <a href="{{ route('admin.ar-guides.index') }}" class="button secondary"> Kembali </a>
                    </div>
                </form>
            </section>

        </main>
    </body>

    </html>
</WritingBlock>