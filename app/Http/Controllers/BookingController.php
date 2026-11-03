<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\QrTicket;
use App\Services\BookingService;
use App\Services\QrTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {
    }

    /**
     * Membuat booking baru untuk pengguna yang login.
     */
    public function store(BookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Identitas pengguna diambil dari token autentikasi.
        $data['user_id'] = $request->user()->id;

        try {
            $booking = $this->bookingService->createBooking($data);

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibuat.',
                'data' => $booking,
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Mengambil riwayat booking beserta QR aktif milik pengguna.
     */
    public function history(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;

        $bookings = $this->bookingService->getBookingHistory(
            $userId
        );

        $data = $bookings->map(
            function (Booking $booking): array {
                $item = $booking->toArray();

                $ticket = $booking->activeQrTicket;

                $item['active_qr_ticket'] = $ticket !== null
                    ? $this->formatQrTicket($ticket)
                    : null;

                return $item;
            }
        )->values();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat booking berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Mengambil QR aktif atau menerbitkan QR pertama
     * untuk booking yang sudah dikonfirmasi.
     */
    public function qrTicket(
        Request $request,
        Booking $booking,
        QrTicketService $qrTicketService
    ): JsonResponse {
        // Pastikan booking merupakan milik pengguna yang login.
        if (
            !$request->user()
            || (string) $booking->user_id
                !== (string) $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akses booking ditolak.',
            ], 403);
        }

        // QR hanya tersedia untuk booking terkonfirmasi.
        if ($booking->status_booking !== 'CONFIRMED') {
            return response()->json([
                'success' => false,
                'message' => 'QR hanya tersedia setelah booking dikonfirmasi.',
            ], 422);
        }

        // Gunakan QR aktif jika sudah tersedia.
        $ticket = $booking->activeQrTicket()->first();

        if (!$ticket) {
            // Jangan menerbitkan QR baru jika tiket sebelumnya
            // sudah ada tetapi tidak lagi berstatus AKTIF.
            if ($booking->qrTickets()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada QR aktif. Silakan hubungi petugas untuk pemeriksaan.',
                ], 409);
            }

            try {
                $ticket = $qrTicketService->generate($booking);
            } catch (RuntimeException $exception) {
                // Periksa kemungkinan QR sudah dibuat oleh
                // permintaan lain secara bersamaan.
                $ticket = $booking->activeQrTicket()->first();

                if (!$ticket) {
                    return response()->json([
                        'success' => false,
                        'message' => 'QR gagal diterbitkan. Silakan coba lagi.',
                    ], 409);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data tiket QR berhasil diambil.',
            'data' => $this->formatQrTicket($ticket),
        ]);
    }

    /**
     * Menerbitkan QR pengganti melalui QrTicketService.
     */
    public function regenerateQrTicket(
        Request $request,
        Booking $booking,
        QrTicketService $qrTicketService
    ): JsonResponse {
        // Pastikan pengguna hanya dapat mengelola booking miliknya.
        if (
            !$request->user()
            || (string) $booking->user_id
                !== (string) $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akses booking ditolak.',
            ], 403);
        }

        if ($booking->status_booking !== 'CONFIRMED') {
            return response()->json([
                'success' => false,
                'message' => 'Booking belum dikonfirmasi.',
            ], 422);
        }

        try {
            $ticket = $qrTicketService->regenerate($booking);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'QR berhasil diperbarui.',
            'data' => $this->formatQrTicket($ticket),
        ]);
    }

    /**
     * Menyeragamkan format respons QR untuk API dan frontend.
     */
    private function formatQrTicket(QrTicket $ticket): array
    {
        $issuedAt = $ticket->waktu_terbit;
        $expiredAt = $ticket->waktu_expired;

        return [
            // Field utama sesuai struktur database.
            'id' => $ticket->id,
            'booking_id' => $ticket->booking_id,
            'payload_signed' => $ticket->payload_signed,
            'status_qr' => $ticket->status_qr,
            'waktu_terbit' => $issuedAt?->toISOString(),
            'waktu_expired' => $expiredAt?->toISOString(),
            'regenerated_from_qr_id' => $ticket->regenerated_from_qr_id,

            // Alias untuk kompatibilitas frontend.
            'qr_ticket_id' => $ticket->id,
            'qr_payload' => $ticket->payload_signed,
            'status' => $ticket->status_qr,
            'issued_at' => $issuedAt?->toISOString(),
            'expires_at' => $expiredAt?->toISOString(),
            'is_expired' => !$expiredAt || $expiredAt->lte(now()),
        ];
    }
}
