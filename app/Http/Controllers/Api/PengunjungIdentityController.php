<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class PengunjungIdentityController extends Controller
{
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
                'id' => $identity->id,
                'status_warga_kota' =>
                    (bool) $identity->status_warga_kota,
            ],
        ], 201);
    }
}
