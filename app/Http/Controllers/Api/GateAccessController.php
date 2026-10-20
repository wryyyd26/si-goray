<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GateAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class GateAccessController extends Controller
{
    public function scan(
        Request $request,
        GateAccessService $gateAccessService
    ): JsonResponse {
        $validated = $request->validate([
            'qr_payload' => ['required', 'string', 'max:4096'],
        ]);

        // Identitas diperoleh dari token Sanctum.
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna belum login.',
            ], 401);
        }

        // Jangan percaya petugas_id dari request.
        $petugas = $user->petugasGerbang;

        if (!$petugas) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak memiliki hak akses petugas.',
            ], 403);
        }

        if (!$petugas->aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Akun petugas tidak aktif.',
            ], 403);
        }

        // Verifikasi bahwa token memiliki izin scan.
        if (!$user->tokenCan('gate:scan')) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak memiliki izin scan.',
            ], 403);
        }

        $result = $gateAccessService->scan(
            $validated['qr_payload'],
            $petugas
        );

        return response()->json([
            'success' => $result['success'],
            'hasil' => $result['hasil'],
            'reason' => $result['reason'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }
}
