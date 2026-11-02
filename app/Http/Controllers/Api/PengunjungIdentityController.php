<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identity;
use App\Services\IdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PengunjungIdentityController extends Controller
{
    /**
     * Menampilkan identitas milik pengunjung terautentikasi.
     * Data sensitif tidak dikirim ke frontend.
     */
    public function index(Request $request): JsonResponse
    {
        $identities = Identity::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get(['id', 'status_warga_kota'])
            ->map(function (Identity $identity): array {
                return [
                    'id' => (string) $identity->id,
                    'status_warga_kota' =>
                        (bool) $identity->status_warga_kota,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar identitas berhasil diambil.',
            'data' => $identities,
        ]);
    }

    /**
     * Mendaftarkan identitas pengunjung.
     * User ID diperoleh dari token, bukan request frontend.
     */
    public function store(
        Request $request,
        IdentityService $identityService
    ): JsonResponse {
        $validated = $request->validate([
            'nik' => [
                'required',
                'digits:16',
            ],
            'tanggal_lahir' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ]);

        try {
            $identity = $identityService->createIdentity(
                (string) $request->user()->id,
                $validated['nik'],
                $validated['tanggal_lahir']
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas tidak dapat didaftarkan. '
                    . 'Periksa kembali data identitas.',
            ], 422);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Layanan pendaftaran identitas '
                    . 'sedang tidak tersedia.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas berhasil didaftarkan.',
            'data' => [
                'id' => (string) $identity->id,
                'status_warga_kota' =>
                    (bool) $identity->status_warga_kota,
            ],
        ], 201);
    }
}
