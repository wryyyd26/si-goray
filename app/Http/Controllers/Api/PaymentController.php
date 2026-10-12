<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        Booking $booking,
        PaymentService $paymentService
    ): JsonResponse {
        // Pengguna wajib login dan hanya boleh
        // membuat pembayaran untuk booking miliknya.
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        if ((string) $booking->user_id !== (string) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
            ], 403);
        }

        if ($booking->status_booking !== 'DRAFT') {
            return response()->json([
                'success' => false,
                'message' => 'Status booking tidak dapat diproses untuk pembayaran.',
            ], 422);
        }

        try {
            $payment = $paymentService->createPayment($booking->id);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembayaran berhasil dibuat.',
                'data' => $payment,
            ], 201);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat pembayaran.',
            ], 500);
        }
    }
}