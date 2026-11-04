<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Identity;
use App\Services\BookingService;
use App\Services\IdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PengunjungWebBookingController extends Controller
{
    /** Data identitas sensitif tidak dikirim kembali ke browser. */
    public function identities(Request $request): JsonResponse
    {
        $data = Identity::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get(['id', 'status_warga_kota'])
            ->map(fn (Identity $identity) => [
                'id' => (string) $identity->id,
                'status_warga_kota' => (bool) $identity->status_warga_kota,
            ])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Identitas diverifikasi oleh service yang sama dengan API Sanctum. */
    public function registerIdentity(Request $request, IdentityService $service): JsonResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        try {
            $identity = $service->createIdentity(
                (string) $request->user()->id,
                $data['nik'],
                $data['tanggal_lahir']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas tidak dapat didaftarkan. Periksa kembali data identitas.',
            ], 422);
        } catch (RuntimeException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Layanan pendaftaran identitas sedang tidak tersedia.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $identity->id,
                'status_warga_kota' => (bool) $identity->status_warga_kota,
            ],
        ], 201);
    }

    /** BookingRequest memvalidasi kepemilikan identitas & jadwal. */
    public function createBooking(BookingRequest $request, BookingService $service): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = (string) $request->user()->id;

        try {
            $booking = $service->createBooking($data);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Jangan membuat QR atau menyatakan pembayaran berhasil pada tahap DRAFT.
        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat.',
            'data' => [
                'id' => (string) $booking->id,
                'status_booking' => $booking->status_booking,
                'retribusi_diperlukan' => (bool) $booking->retribusi_diperlukan,
            ],
        ], 201);
    }
}
