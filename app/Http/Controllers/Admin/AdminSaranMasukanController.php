<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaranMasukan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSaranMasukanController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(['feedback', 'reservasi'])],
            'status' => ['nullable', Rule::in(['baru', 'diproses', 'selesai'])],
        ]);

        $query = SaranMasukan::query();

        if (!empty($filter['search'])) {
            $search = $filter['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if (!empty($filter['category'])) {
            $query->where('category', $filter['category']);
        }

        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        $pesan = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $ringkasan = [
            'total' => SaranMasukan::count(),
            'baru' => SaranMasukan::where('status', 'baru')->count(),
            'diproses' => SaranMasukan::where('status', 'diproses')->count(),
            'selesai' => SaranMasukan::where('status', 'selesai')->count(),
        ];

        return view(
            'admin.saran-masukan.index',
            compact('pesan', 'ringkasan')
        );
    }

    public function detail(SaranMasukan $saranMasukan)
    {
        return view('admin.saran-masukan.detail', [
            'saranMasukan' => $saranMasukan,
        ]);
    }

    public function updateStatus(Request $request, SaranMasukan $saranMasukan)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['baru', 'diproses', 'selesai']),
            ],
        ]);

        $saranMasukan->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.saran-masukan.detail', $saranMasukan)
            ->with('success', 'Status pesan berhasil diperbarui.');
    }

    public function destroy(SaranMasukan $saranMasukan)
    {
        $saranMasukan->delete();

        return redirect()
            ->route('admin.saran-masukan.index')
            ->with('success', 'Pesan berhasil dihapus.');
    }
}
