<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EventSchedule;
use App\Models\Identity;
use App\Models\RegularSlot;
use App\Models\TariffReference;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingService
{
    /**
     * Membuat booking baru dengan status DRAFT.
     *
     * Proses:
     * 1. Validasi jenis booking.
     * 2. Validasi identity dan kepemilikan identity.
     * 3. Menentukan status warga/non-warga.
     * 4. Menentukan tarif aktif.
     * 5. Menentukan retribusi secara backend.
     * 6. Validasi jadwal sesuai jenis booking.
     * 7. Mengunci slot/jadwal dengan lockForUpdate().
     * 8. Mengecek kapasitas.
     * 9. Membuat booking.
     * 10. Menambah kuota_terpakai.
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {

            /*
             * ==========================================
             * 1. VALIDASI JENIS BOOKING
             * ==========================================
             */
            $jenisBooking = strtoupper($data['jenis_booking'] ?? '');

            if (!in_array($jenisBooking, ['REGULER', 'EVENT'], true)) {
                throw new InvalidArgumentException(
                    'Jenis booking harus REGULER atau EVENT.'
                );
            }

            /*
             * ==========================================
             * 2. VALIDASI IDENTITY
             * ==========================================
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
            $identity = Identity::where('id', $data['identity_id'])
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$identity) {
                throw new InvalidArgumentException(
                    'Identity tidak ditemukan atau bukan milik user.'
                );
            }

            /*
             * Identity harus sudah divalidasi.
             */
            if ($identity->status_warga_kota === null) {
                throw new InvalidArgumentException(
                    'Status warga kota belum divalidasi.'
                );
            }

            /*
             * ==========================================
             * 3. TENTUKAN RETRIBUSI
             * ==========================================
             *
             * true  = Non-Warga Kota
             * false = Warga Kota
             *
             * Nilai ini TIDAK diambil dari frontend.
             */
            $retribusiDiperlukan = !$identity->status_warga_kota;

            /*
             * ==========================================
             * 4. CARI TARIF AKTIF
             * ==========================================
             *
             * Backend mengambil tarif yang:
             * - jenis pengunjung UMUM
             * - aktif
             * - sudah mulai berlaku
             * - paling baru
             */
            $tariff = TariffReference::where(
                'jenis_pengunjung',
                'UMUM'
            )
                ->where('aktif', true)
                ->where('berlaku_mulai', '<=', today())
                ->orderByDesc('berlaku_mulai')
                ->first();

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
             * ==========================================
             * 5. BOOKING REGULER
             * ==========================================
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
                 * Ambil slot dan kunci row.
                 */
                $regularSlot = RegularSlot::where(
                    'id',
                    $data['regular_slot_id']
                )
                    ->lockForUpdate()
                    ->first();

                if (!$regularSlot) {
                    throw new InvalidArgumentException(
                        'Regular slot tidak ditemukan.'
                    );
                }

                /*
                 * Cek kapasitas.
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
             * ==========================================
             * 6. BOOKING EVENT
             * ==========================================
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
                 * Ambil jadwal EVENT dan kunci row.
                 */
                $eventSchedule = EventSchedule::where(
                    'id',
                    $data['event_schedule_id']
                )
                    ->lockForUpdate()
                    ->first();

                if (!$eventSchedule) {
                    throw new InvalidArgumentException(
                        'Event schedule tidak ditemukan.'
                    );
                }

                /*
                 * Cek kapasitas.
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
             * ==========================================
             * 7. BUAT BOOKING
             * ==========================================
             *
             * tariff_id dan retribusi_diperlukan
             * berasal dari backend.
             *
             * Frontend tidak menentukan keduanya.
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
             * ==========================================
             * 8. TAMBAH KUOTA TERPAKAI
             * ==========================================
             */
            if ($regularSlot) {

                $regularSlot->increment('kuota_terpakai');

            } elseif ($eventSchedule) {

                $eventSchedule->increment('kuota_terpakai');
            }

            /*
             * Semua berhasil → COMMIT.
             * Jika terjadi error → ROLLBACK.
             */
            return $booking;
        });
    }

    /**
     * Mengambil riwayat booking milik user.
     *
     * Untuk sementara user_id dikirim dari request.
     * Setelah authentication selesai,
     * user_id akan diambil dari user yang sedang login.
     */
    public function getBookingHistory(string $userId)
    {
        return Booking::with([
            'regularSlot',
            'eventSchedule.event',
        ])
            ->where('user_id', $userId)
            ->latest('created_at')
            ->get();
    }
}