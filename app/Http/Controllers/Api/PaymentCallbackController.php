<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MidtransCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class PaymentCallbackController extends Controller
{
    public function receive(
        Request $request,
        MidtransCallbackService $service
    ): JsonResponse {
        try {
            $result = $service->receive(
                $request->json()->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi diterima.',
            ], 200);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak valid.',
            ], 403);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses notifikasi.',
            ], 500);
        }
    }
}
