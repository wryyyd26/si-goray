<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EventSchedule;
use App\Models\Identity;
use App\Models\RegularSlot;
use App\Models\TariffReference;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingService
{
    /**
     * Membuat booking baru dengan status DRAFT.
     *
     * Proses:
     * 1. Validasi jenis booking.
     * 2. Validasi identity dan kepemilikannya.
     * 3. Menentukan status warga/non-warga.
     * 4. Menentukan tarif aktif.
     * 5. Menentukan retribusi dari backend.
     * 6. Memvalidasi jadwal sesuai jenis booking.
     * 7. Mengunci slot atau jadwal.
     * 8. Memeriksa kapasitas.
     * 9. Membuat booking.
     * 10. Memperbarui kuota terpakai.
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {

            /*
             * 1. VALIDASI JENIS BOOKING
             */
            $jenisBooking = strtoupper(
                $data['jenis_booking'] ?? ''
            );

            if (!in_array(
                $jenisBooking,
                ['REGULER', 'EVENT'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Jenis booking harus REGULER atau EVENT.'
                );
            }

            /*
             * 2. VALIDASI IDENTITY DAN USER
             */
            if (empty($data['identity_id'])) {
                throw new InvalidArgumentException(
                    'identity_id wajib diisi.'
                );
            }

            if (empty($data['user_id'])) {
                throw new InvalidArgumentException(
                    'user_id wajib diisi.'
                );
            }

            /*
             * Identity harus benar-benar milik user
             * yang membuat booking.
             */
            $identity = Identity::query()
                ->where('id', $data['identity_id'])
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$identity) {
                throw new InvalidArgumentException(
                    'Identity tidak ditemukan atau bukan milik user.'
                );
            }

            /*
             * Status warga harus sudah ditentukan.
             */
            if ($identity->status_warga_kota === null) {
                throw new InvalidArgumentException(
                    'Status warga kota belum divalidasi.'
                );
            }

            /*
             * 3. TENTUKAN RETRIBUSI
             *
             * Warga kota     = tidak memerlukan retribusi.
             * Non-warga kota = memerlukan retribusi.
             *
             * Nilai tidak diambil dari frontend.
             */
            $retribusiDiperlukan =
                !$identity->status_warga_kota;

            /*
             * 4. CARI TARIF AKTIF
             *
             * Booking REGULER menggunakan tarif umum.
             * Booking EVENT mencari tarif khusus event,
             * kemudian menggunakan tarif umum sebagai fallback.
             */
            $tariffQuery = TariffReference::query()
                ->where('jenis_pengunjung', 'UMUM')
                ->where('aktif', true)
                ->whereDate('berlaku_mulai', '<=', today());

            if ($jenisBooking === 'EVENT') {

                $scheduleForTariff = EventSchedule::query()
                    ->find($data['event_schedule_id'] ?? null);

                if (!$scheduleForTariff) {
                    throw new InvalidArgumentException(
                        'Event schedule tidak ditemukan.'
                    );
                }

                $tariff = (clone $tariffQuery)
                    ->where(
                        'event_id',
                        $scheduleForTariff->event_id
                    )
                    ->orderByDesc('berlaku_mulai')
                    ->first();

                if (!$tariff) {
                    $tariff = (clone $tariffQuery)
                        ->whereNull('event_id')
                        ->orderByDesc('berlaku_mulai')
                        ->first();
                }
            } else {
                $tariff = (clone $tariffQuery)
                    ->whereNull('event_id')
                    ->orderByDesc('berlaku_mulai')
                    ->first();
            }

            if (!$tariff) {
                throw new InvalidArgumentException(
                    'Tarif aktif untuk pengunjung UMUM tidak ditemukan.'
                );
            }

            /*
             * Variabel jadwal.
             */
            $regularSlot = null;
            $eventSchedule = null;

            /*
             * 5. BOOKING REGULER
             */
            if ($jenisBooking === 'REGULER') {

                if (empty($data['regular_slot_id'])) {
                    throw new InvalidArgumentException(
                        'regular_slot_id wajib diisi untuk booking REGULER.'
                    );
                }

                if (!empty($data['event_schedule_id'])) {
                    throw new InvalidArgumentException(
                        'event_schedule_id harus kosong untuk booking REGULER.'
                    );
                }

                /*
                 * Kunci slot agar pemeriksaan kuota
                 * dan pembuatan booking berlangsung konsisten.
                 */
                $regularSlot = RegularSlot::query()
                    ->where('id', $data['regular_slot_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$regularSlot) {
                    throw new InvalidArgumentException(
                        'Regular slot tidak ditemukan.'
                    );
                }

                /*
                 * Slot yang dinonaktifkan admin tidak dapat dipesan.
                 */
                if ($regularSlot->status !== 'active') {
                    throw new InvalidArgumentException(
                        'Slot reguler sedang tidak tersedia.'
                    );
                }

                /*
                 * Periksa kapasitas slot.
                 */
                if (
                    $regularSlot->kuota_terpakai
                    >= $regularSlot->kapasitas
                ) {
                    throw new InvalidArgumentException(
                        'Kuota booking REGULER sudah penuh.'
                    );
                }
            }

            /*
             * 6. BOOKING EVENT
             */
            else {

                if (empty($data['event_schedule_id'])) {
                    throw new InvalidArgumentException(
                        'event_schedule_id wajib diisi untuk booking EVENT.'
                    );
                }

                if (!empty($data['regular_slot_id'])) {
                    throw new InvalidArgumentException(
                        'regular_slot_id harus kosong untuk booking EVENT.'
                    );
                }

                /*
                 * Kunci jadwal event sebelum memeriksa kuota.
                 */
                $eventSchedule = EventSchedule::query()
                    ->where('id', $data['event_schedule_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$eventSchedule) {
                    throw new InvalidArgumentException(
                        'Event schedule tidak ditemukan.'
                    );
                }

                /*
                 * Periksa kapasitas event.
                 */
                if (
                    $eventSchedule->kuota_terpakai
                    >= $eventSchedule->kapasitas
                ) {
                    throw new InvalidArgumentException(
                        'Kuota booking EVENT sudah penuh.'
                    );
                }
            }

            /*
             * 7. BUAT BOOKING
             *
             * tariff_id dan retribusi_diperlukan
             * ditentukan oleh backend.
             */
            $booking = Booking::create([
                'user_id' => $data['user_id'],
                'identity_id' => $identity->id,
                'jenis_booking' => $jenisBooking,
                'regular_slot_id' => $regularSlot?->id,
                'event_schedule_id' => $eventSchedule?->id,
                'tariff_id' => $tariff->id,
                'retribusi_diperlukan' => $retribusiDiperlukan,
                'status_booking' => 'DRAFT',
            ]);

            /*
             * 8. TAMBAH KUOTA TERPAKAI
             */
            if ($regularSlot) {
                $regularSlot->increment('kuota_terpakai');
            } elseif ($eventSchedule) {
                $eventSchedule->increment('kuota_terpakai');
            }

            /*
             * Transaksi di-commit jika seluruh proses berhasil.
             * Jika terjadi exception, transaksi di-rollback.
             */
            return $booking;
        });
    }

    /**
     * Mengambil riwayat booking milik pengguna.
     *
     * Hanya booking dengan user_id yang sesuai
     * yang akan dikembalikan.
     *
     * Relasi yang dimuat:
     * - regularSlot
     * - eventSchedule.event
     * - activeQrTicket
     *
     * Method ini hanya mengambil data.
     * Penerbitan QR tetap dilakukan melalui alur QR Ticket.
     */
    public function getBookingHistory(
        string $userId
    ): Collection {
        return Booking::query()
            ->with([
                'regularSlot',
                'eventSchedule.event',
                'activeQrTicket:id,booking_id,payload_signed,status_qr,regenerated_from_qr_id,waktu_terbit,waktu_expired',
            ])
            ->where('user_id', $userId)
            ->latest('created_at')
            ->get();
    }
}
