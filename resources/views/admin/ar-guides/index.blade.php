<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Panduan AR - SI-GORAY</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 32px 20px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .subtitle {
            margin: 0;
            color: #64748b;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-edit {
            background: #e0edff;
            color: #1d4ed8;
        }

        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-size: 13px;
            color: #475569;
        }

        td {
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .empty {
            padding: 35px 15px;
            text-align: center;
            color: #64748b;
        }

        .pagination {
            margin-top: 20px;
        }

        .pagination nav {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 12px;
            }

            .card {
                padding: 12px;
            }

            h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="header">
            <div>
                <h1>Kelola Panduan AR</h1>
                <p class="subtitle">
                    Kelola panduan Augmented Reality SI-GORAY.
                </p>
            </div>

            <a href="{{ route('admin.ar-guides.create') }}"
               class="btn btn-primary">
                + Tambah Panduan
            </a>
        </div>

        @if (session('success'))
            <div style="padding:12px; margin-bottom:16px; background:#dcfce7; color:#166534; border-radius:8px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            @if ($guides->count())
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Judul</th>
                            <th>Slug</th>
                            <th>Jumlah Langkah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($guides as $guide)
                            <tr>
                                <td>{{ $guides->firstItem() + $loop->index }}</td>
                                <td>{{ $guide->title }}</td>
                                <td>{{ $guide->slug }}</td>
                                <td>{{ $guide->steps_count }}</td>
                                <td>
                                    @if ($guide->is_active)
                                        <span class="badge">Aktif</span>
                                    @else
                                        <span class="badge badge-inactive">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-edit"
                                           href="{{ route('admin.ar-guides.edit', $guide) }}">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.ar-guides.destroy', $guide) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus panduan ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-delete">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="pagination">
                    {{ $guides->links() }}
                </div>
            @else
                <div class="empty">
                    <h3>Belum ada panduan AR</h3>
                    <p>Silakan tambahkan panduan AR terlebih dahulu.</p>
                </div>
            @endif
        </div>
    </main>
</body>
</html>
