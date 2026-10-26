<?php

namespace Tests\Feature;

use App\Models\Booking;

use App\Models\Identity;

use App\Models\PaymentGatewayCallback;

use App\Models\PaymentTransaction;

use App\Models\TariffReference;

use App\Models\User;

use App\Services\MidtransCallbackService;

use App\Services\MidtransStatusGateway;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use InvalidArgumentException;

use Mockery;

use RuntimeException;

use Tests\TestCase;

class PaymentCallbackTest extends TestCase

{

    use RefreshDatabase;

    protected function setUp(): void

    {

        parent::setUp();

        if (

            DB::connection()->getDatabaseName()

            !== 'gor_reservation_testing'

        ) {

            throw new RuntimeException(

                'Database testing tidak sesuai.'

            );

        }

        config()->set(

            'midtrans.server_key',

            'sandbox-key-for-automated-tests'

        );

        config()->set('midtrans.is_production', false);

    }

    private function buatPayment(): PaymentTransaction

    {

        $user = User::create([

            'no_hp_email' => 'callback@example.com',

            'status_otp' => true,

        ]);

        $identity = Identity::create([

            'user_id' => $user->id,

            'nik_enc' => '3576010101900002',

            'nik_hash' => hash('sha256', '3576010101900002'),

            'tanggal_lahir_enc' => '1990-01-01',

            'status_warga_kota' => false,

            'sumber_validasi' => 'local',

            'divalidasi_at' => now(),

        ]);

        $slotId = (string) Str::uuid();

        DB::table('regular_slots')->insert([

            'id' => $slotId,

            'tanggal' => now()->addDays(7)->toDateString(),

            'jam_mulai' => '08:00:00',

            'jam_selesai' => '10:00:00',

            'kapasitas' => 100,

            'kuota_terpakai' => 0,

        ]);

        $tariff = TariffReference::create([

            'jenis_pengunjung' => 'LUAR_KOTA',

            'nominal' => 50000,

            'berlaku_mulai' => now()->subDay()->toDateString(),

            'aktif' => true,

        ]);

        $booking = Booking::create([

            'user_id' => $user->id,

            'identity_id' => $identity->id,

            'jenis_booking' => 'REGULER',

            'regular_slot_id' => $slotId,

            'event_schedule_id' => null,

            'tariff_id' => $tariff->id,

            'retribusi_diperlukan' => true,

            'status_booking' => 'DRAFT',

        ]);

        return PaymentTransaction::create([

            'booking_id' => $booking->id,

            'kode_bayar' => 'GOR-' . Str::uuid(),

            'metode_pembayaran' => 'MIDTRANS',

            'nominal' => 50000,

            'status_pembayaran' => 'PENDING',

            'jumlah_retry' => 0,

            'referensi_gateway' => null,

            'expired_at' => now()->addHours(24),

            'snap_token' => 'test-token',

        ]);

    }

    private function payload(

        PaymentTransaction $payment,

        string $status

    ): array {

        $payload = [

            'order_id' => $payment->kode_bayar,

            'status_code' => '200',

            'gross_amount' => '50000.00',

            'transaction_status' => $status,

        ];

        $payload['signature_key'] = hash(

            'sha512',

            $payload['order_id']

                . $payload['status_code']

                . $payload['gross_amount']

                . config('midtrans.server_key')

        );

        return $payload;

    }

    private function service(

        PaymentTransaction $payment,

        string $status,

        int $calls = 1

    ): MidtransCallbackService {

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldReceive('getStatus')

            ->times($calls)

            ->with($payment->kode_bayar)

            ->andReturn((object) [

                'order_id' => $payment->kode_bayar,

                'gross_amount' => '50000.00',

                'transaction_status' => $status,

                'status_code' => '200',

                'fraud_status' => 'accept',

            ]);

        return new MidtransCallbackService($gateway);

    }

    public function test_settlement_mengonfirmasi_booking(): void

    {

        $payment = $this->buatPayment();

        $service = $this->service($payment, 'settlement');

        $hasil = $service->receive(

            $this->payload($payment, 'settlement')

        );

        $this->assertSame('SUCCESS', $hasil['status']);

        $this->assertTrue($hasil['updated']);

        $this->assertSame(

            'SUCCESS',

            $payment->fresh()->status_pembayaran

        );

        $this->assertSame(

            'CONFIRMED',

            $payment->booking->fresh()->status_booking

        );

        $this->assertSame(

            1,

            PaymentGatewayCallback::where(

                'transaction_id',

                $payment->id

            )->count()

        );

    }

    public function test_deny_menjadi_failed(): void

    {

        $this->assertStatusAkhir('deny', 'FAILED');

    }

    public function test_cancel_menjadi_cancelled(): void

    {

        $this->assertStatusAkhir('cancel', 'CANCELLED');

    }

    public function test_expire_menjadi_expired(): void

    {

        $this->assertStatusAkhir('expire', 'EXPIRED');

    }

    private function assertStatusAkhir(

        string $gatewayStatus,

        string $expected

    ): void {

        $payment = $this->buatPayment();

        $service = $this->service($payment, $gatewayStatus);

        $hasil = $service->receive(

            $this->payload($payment, $gatewayStatus)

        );

        $this->assertSame($expected, $hasil['status']);

        $this->assertTrue($hasil['updated']);

        $this->assertSame(

            $expected,

            $payment->fresh()->status_pembayaran

        );

        $this->assertSame(

            'DRAFT',

            $payment->booking->fresh()->status_booking

        );

    }

    public function test_signature_tidak_valid_ditolak(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldNotReceive('getStatus');

        $service = new MidtransCallbackService($gateway);

        $payload = $this->payload($payment, 'settlement');

        $payload['signature_key'] = 'signature-palsu';

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Signature tidak valid.');

        $service->receive($payload);

    }

    public function test_callback_berulang_tidak_memproses_ulang(): void

    {

        $payment = $this->buatPayment();

        $service = $this->service($payment, 'settlement', 2);

        $payload = $this->payload($payment, 'settlement');

        $pertama = $service->receive($payload);

        $kedua = $service->receive($payload);

        $this->assertTrue($pertama['updated']);

        $this->assertFalse($kedua['updated']);

        $this->assertSame(

            'SUCCESS',

            $payment->fresh()->status_pembayaran

        );

        // Dua callback tercatat sebagai riwayat audit.

        $this->assertSame(

            2,

            PaymentGatewayCallback::where(

                'transaction_id',

                $payment->id

            )->count()

        );

    }

    public function test_status_final_tidak_ditimpa(): void

    {

        $payment = $this->buatPayment();

        $payment->status_pembayaran = 'SUCCESS';

        $payment->save();

        $service = $this->service($payment, 'cancel');

        $hasil = $service->receive(

            $this->payload($payment, 'cancel')

        );

        $this->assertFalse($hasil['updated']);

        $this->assertTrue($hasil['requires_review']);

        $this->assertSame(

            'SUCCESS',

            $payment->fresh()->status_pembayaran

        );

    }

    public function test_gateway_gagal_tidak_mengubah_payment(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldReceive('getStatus')

            ->once()

            ->andThrow(

                new RuntimeException('Simulasi timeout Midtrans')

            );

        $service = new MidtransCallbackService($gateway);

        try {

            $service->receive(

                $this->payload($payment, 'settlement')

            );

            $this->fail('Seharusnya terjadi exception.');

        } catch (RuntimeException $e) {

            $this->assertSame(

                'Simulasi timeout Midtrans',

                $e->getMessage()

            );

        }

        $this->assertSame(

            'PENDING',

            $payment->fresh()->status_pembayaran

        );

        $this->assertSame(

            0,

            PaymentGatewayCallback::where(

                'transaction_id',

                $payment->id

            )->count()

        );

    }

    public function test_payload_tidak_lengkap_ditolak(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldNotReceive('getStatus');

        $service = new MidtransCallbackService($gateway);

        $payload = $this->payload($payment, 'settlement');

        unset($payload['gross_amount']);

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(

            'Payload Midtrans tidak lengkap.'

        );

        $service->receive($payload);

    }

    public function test_order_tidak_dikenal_ditolak(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldNotReceive('getStatus');

        $service = new MidtransCallbackService($gateway);

        $payload = $this->payload($payment, 'settlement');

        $payload['order_id'] = 'GOR-ORDER-TIDAK-DIKENAL';

        $payload['signature_key'] = hash(

            'sha512',

            $payload['order_id']

                . $payload['status_code']

                . $payload['gross_amount']

                . config('midtrans.server_key')

        );

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Order tidak ditemukan.');

        $service->receive($payload);

    }

    public function test_order_gateway_berbeda_ditolak(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldReceive('getStatus')

            ->once()

            ->andReturn((object) [

                'order_id' => 'GOR-ORDER-BERBEDA',

                'gross_amount' => '50000.00',

                'transaction_status' => 'settlement',

                'status_code' => '200',

            ]);

        $service = new MidtransCallbackService($gateway);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(

            'Order ID gateway tidak sesuai.'

        );

        $service->receive(

            $this->payload($payment, 'settlement')

        );

    }

    public function test_nominal_callback_berbeda_ditolak(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldReceive('getStatus')

            ->once()

            ->andReturn((object) [

                'order_id' => $payment->kode_bayar,

                'gross_amount' => '60000.00',

                'transaction_status' => 'settlement',

                'status_code' => '200',

            ]);

        $service = new MidtransCallbackService($gateway);

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(

            'Nominal notifikasi tidak sesuai.'

        );

        $service->receive(

            $this->payload($payment, 'settlement')

        );

    }

    public function test_nominal_database_berbeda_ditolak(): void

    {

        $payment = $this->buatPayment();

        // Mengubah nominal hanya pada database testing.

        $payment->nominal = 60000;

        $payment->save();

        $service = $this->service($payment, 'settlement');

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage(

            'Nominal database tidak sesuai.'

        );

        $service->receive(

            $this->payload($payment, 'settlement')

        );

    }

    public function test_gateway_pending_tidak_mengonfirmasi_booking(): void

    {

        $payment = $this->buatPayment();

        $service = $this->service($payment, 'pending');

        $hasil = $service->receive(

            $this->payload($payment, 'pending')

        );

        $this->assertSame('PENDING', $hasil['status']);

        $this->assertFalse($hasil['updated']);

        $this->assertSame(

            'PENDING',

            $payment->fresh()->status_pembayaran

        );

        $this->assertSame(

            'DRAFT',

            $payment->booking->fresh()->status_booking

        );

    }

    public function test_capture_fraud_challenge_tidak_berhasil(): void

    {

        $payment = $this->buatPayment();

        $gateway = Mockery::mock(MidtransStatusGateway::class);

        $gateway->shouldReceive('getStatus')

            ->once()

            ->andReturn((object) [

                'order_id' => $payment->kode_bayar,

                'gross_amount' => '50000.00',

                'transaction_status' => 'capture',

                'status_code' => '200',

                'fraud_status' => 'challenge',

            ]);

        $service = new MidtransCallbackService($gateway);

        $hasil = $service->receive(

            $this->payload($payment, 'capture')

        );

        $this->assertSame('PENDING', $hasil['status']);

        $this->assertFalse($hasil['updated']);

        $this->assertSame(

            'PENDING',

            $payment->fresh()->status_pembayaran

        );

    }

    public function test_success_pada_booking_cancelled_ditolak(): void

    {

        $payment = $this->buatPayment();

        $booking = $payment->booking;

        $booking->status_booking = 'CANCELLED';

        $booking->save();

        $service = $this->service($payment, 'settlement');

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(

            'Status booking perlu pemeriksaan manual.'

        );

        $service->receive(

            $this->payload($payment, 'settlement')

        );

    }
}
