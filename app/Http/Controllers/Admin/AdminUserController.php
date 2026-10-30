<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($input['q'] ?? ''));

        $query = DB::table('users')->select(['id', 'no_hp_email', 'created_at']);
        if ($search !== '') {
            // NIK terenkripsi tidak dapat dicari dengan LIKE secara aman.
            $query->where(function ($q) use ($search) {
                $q->where('no_hp_email', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('id', $search);
            });
        }
        $users = $query->orderByDesc('created_at')->orderBy('id')->paginate(20)->withQueryString();
        $ids = $users->getCollection()->pluck('id')->all();
        $identities = DB::table('identities')
            ->select(['user_id', 'status_warga_kota', 'created_at', 'id'])
            ->whereIn('user_id', $ids)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $users->setCollection($users->getCollection()->map(function ($row) use ($identities) {
            $identity = $identities->get($row->id);
            return [
                'id' => $row->id,
                'nik_masked' => $identity ? 'Terlindungi' : 'Belum diisi',
                'tanggal_lahir_label' => 'Tidak ditampilkan',
                'is_warga_kota' => $identity && $identity->status_warga_kota !== null
                    ? (bool) $identity->status_warga_kota : null,
                'created_at' => $row->created_at,
            ];
        }));

        // Hitung pengguna unik, bukan seluruh baris identities.
        $statusQuery = DB::table('identities as i')
            ->whereRaw('i.id = (SELECT i2.id FROM identities i2 WHERE i2.user_id = i.user_id ORDER BY i2.created_at DESC, i2.id DESC LIMIT 1)');
        $wargaCount = (clone $statusQuery)->where('i.status_warga_kota', 1)->count();
        $nonWargaCount = (clone $statusQuery)->where('i.status_warga_kota', 0)->count();

        return view('admin.user.index', [
            'users' => $users,
            'search' => $search,
            'totalUsers' => DB::table('users')->count(),
            'wargaCount' => $wargaCount,
            'nonWargaCount' => $nonWargaCount,
        ]);
    }

    public function show(string $user)
    {
        $account = DB::table('users')->where('id', $user)->first(['id', 'created_at']);
        abort_if($account === null, 404);
        $identity = DB::table('identities')
            ->where('user_id', $user)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->first(['status_warga_kota']);

        return view('admin.user.detail', [
            'user' => [
                'id' => $account->id,
                'nik_masked' => $identity ? 'Terlindungi' : 'Belum diisi',
                'tanggal_lahir_label' => 'Tidak ditampilkan',
                'is_warga_kota' => $identity && $identity->status_warga_kota !== null
                    ? (bool) $identity->status_warga_kota : null,
                'created_at' => $account->created_at,
            ],
            'bookingCount' => DB::table('bookings')->where('user_id', $user)->count(),
        ]);
    }
}
