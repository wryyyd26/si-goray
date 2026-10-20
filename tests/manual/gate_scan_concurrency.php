<?php
/**
 * SI-GORAY: standalone two-process GateAccessService concurrency smoke test.
 * Run from project root: php tests/manual/gate_scan_concurrency.php
 * MUST be run in dedicated gor_reservation_testing. No RefreshDatabase, no migrate:fresh.
 * This script adds its own fixture, then deletes only its own records.
 */

declare(strict_types=1);

use App\Models\AccessLog;
use App\Models\Booking;
use App\Models\Identity;
use App\Models\PetugasGerbang;
use App\Models\QrTicket;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\GateAccessService;
use App\Services\QrTicketService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';
$app = require $projectRoot . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Explicitly select testing DB irrespective of the current PowerShell env/.env.
config()->set('database.default', 'mysql');
config()->set('database.connections.mysql.database', 'gor_reservation_testing');
DB::purge('mysql');

try {
    $actualDatabase = DB::connection('mysql')->getDatabaseName();
    if ($actualDatabase !== 'gor_reservation_testing') {
        throw new RuntimeException('REFUSED: database is not gor_reservation_testing: ' . $actualDatabase);
    }
    DB::select('SELECT 1 AS ok');
} catch (Throwable $e) {
    fwrite(STDERR, "Database test unavailable: {$e->getMessage()}\n");
    exit(2);
}

function failIf(string $reason): never
{
    throw new RuntimeException($reason);
}

if (($argv[1] ?? '') === '--worker') {
    $payloadFile = $argv[2] ?? '';
    $barrierFile = $argv[3] ?? '';
    $petugasId = $argv[4] ?? '';
    try {
        file_put_contents($barrierFile . "." . $petugasId . ".ready", "ready");
        $deadline = microtime(true) + 15;
        while (!is_file($barrierFile)) {
            if (microtime(true) > $deadline) {
                failIf('Worker timeout waiting for barrier');
            }
            usleep(10000);
        }
        $payload = file_get_contents($payloadFile);
        if ($payload === false) {
            failIf('Cannot load QR payload');
        }
        $petugas = PetugasGerbang::findOrFail($petugasId);
        $result = app(GateAccessService::class)->scan(trim($payload), $petugas);
        echo json_encode(['success' => $result['success'], 'reason' => $result['reason']], JSON_THROW_ON_ERROR), PHP_EOL;
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
        exit(3);
    }
}

$ids = ['user' => [], 'identity' => [], 'slot' => [], 'tariff' => [], 'booking' => [], 'petugas' => [], 'qr' => []];
$procs = [];
$exitCode = 0;
$tmpDir = sys_get_temp_dir() . '/sigoray_gate_' . bin2hex(random_bytes(8));
if (!mkdir($tmpDir, 0700) && !is_dir($tmpDir)) {
    failIf('Cannot create temporary directory');
}
$barrier = $tmpDir . '/go';
$payloadFile = $tmpDir . '/payload';

try {
    // Same REGULER booking structure as the already passing PaymentFlowTest.
    $visitor = User::create(['no_hp_email' => 'gate-visitor-' . Str::uuid() . '@example.com', 'status_otp' => true]);
    $ids['user'][] = $visitor->id;
    $nik = '3576010101900001';
    $identity = Identity::create([
        'user_id' => $visitor->id,
        'nik_enc' => $nik,
        'nik_hash' => hash('sha256', $nik),
        'tanggal_lahir_enc' => '1990-01-01',
        'status_warga_kota' => false,
        'sumber_validasi' => 'local',
        'divalidasi_at' => now(),
    ]);
    $ids['identity'][] = $identity->id;
    $slotId = (string) Str::uuid();
    DB::table('regular_slots')->insert([
        'id' => $slotId,
        'tanggal' => now()->addDays(7)->toDateString(),
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'kapasitas' => 100,
        'kuota_terpakai' => 0,
    ]);
    $ids['slot'][] = $slotId;
    $tariff = TariffReference::create([
        'jenis_pengunjung' => 'LUAR_KOTA',
        'nominal' => 50000,
        'berlaku_mulai' => now()->subDay()->toDateString(),
        'aktif' => true,
    ]);
    $ids['tariff'][] = $tariff->id;
    $booking = Booking::create([
        'user_id' => $visitor->id,
        'identity_id' => $identity->id,
        'jenis_booking' => 'REGULER',
        'regular_slot_id' => $slotId,
        'event_schedule_id' => null,
        'tariff_id' => $tariff->id,
        'retribusi_diperlukan' => true,
        'status_booking' => 'CONFIRMED',
    ]);
    $ids['booking'][] = $booking->id;
    $qr = app(QrTicketService::class)->generate($booking);
    $ids['qr'][] = $qr->id;
    file_put_contents($payloadFile, $qr->payload_signed);

    for ($i = 0; $i < 2; $i++) {
        $p = new PetugasGerbang();
        $p->nama = 'Concurrency Test ' . ($i + 1);
        $p->username = 'gate_concurrent_' . bin2hex(random_bytes(6));
        $p->aktif = true;
        $p->save();
        $ids['petugas'][] = $p->id;
    }

    foreach ($ids['petugas'] as $id) {
        $command = [PHP_BINARY, __FILE__, '--worker', $payloadFile, $barrier, $id];
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($command, $descriptors, $pipes, $projectRoot);
        if (!is_resource($proc)) {
            failIf('Unable to start worker PHP process');
        }
        fclose($pipes[0]);
        $procs[] = ['proc' => $proc, 'pipes' => $pipes];
    }

    // Wait until BOTH workers are initialized, then release the barrier.
    $deadline = microtime(true) + 10;
    while (true) {
        $readyCount = 0;
        foreach ($ids['petugas'] as $id) {
            if (is_file($barrier . "." . $id . ".ready")) $readyCount++;
        }
        if ($readyCount === 2) break;
        if (microtime(true) > $deadline) failIf('Workers did not become ready');
        usleep(10000);
    }
    file_put_contents($barrier, 'GO');
    $results = [];
    foreach ($procs as $index => $entry) {
        $stdout = trim(stream_get_contents($entry['pipes'][1]));
        $stderr = trim(stream_get_contents($entry['pipes'][2]));
        fclose($entry['pipes'][1]);
        fclose($entry['pipes'][2]);
        $code = proc_close($entry['proc']);
        unset($procs[$index]);
        if ($code !== 0) {
            failIf("Worker error (exit $code): $stderr");
        }
        $parsed = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        $results[] = $parsed;
    }

    $successes = count(array_filter($results, static fn (array $v): bool => $v['success'] === true));
    $successLogs = AccessLog::where('qr_ticket_id', $qr->id)->where('hasil_validasi', 'BERHASIL')->count();
    $finalStatus = $qr->fresh()->status_qr;
    echo 'Worker results: ' . json_encode($results, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "Successful worker responses: $successes; successful access logs: $successLogs; QR status: $finalStatus\n";
    if ($successes !== 1 || $successLogs !== 1 || $finalStatus !== 'SUDAH_DIPAKAI') {
        failIf('FAILED concurrency invariant: expected exactly one successful entry');
    }
    echo "PASS: Only one concurrent scan granted access.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    // Clean up only fixture records we created; never truncate or migrate:fresh.
    // Collect database errors rather than mask the concurrency result.
    foreach ($procs as $entry) {
        foreach ([1, 2] as $fd) {
            if (is_resource($entry['pipes'][$fd])) fclose($entry['pipes'][$fd]);
        }
        if (is_resource($entry['proc'])) proc_terminate($entry['proc']);
    }
    try {
        foreach ($ids['qr'] as $id) {
            AccessLog::where('qr_ticket_id', $id)->delete();
            QrTicket::where('id', $id)->delete();
        }
        foreach ($ids['petugas'] as $id) PetugasGerbang::where('id', $id)->delete();
        foreach ($ids['booking'] as $id) Booking::where('id', $id)->delete();
        foreach ($ids['tariff'] as $id) TariffReference::where('id', $id)->delete();
        foreach ($ids['identity'] as $id) Identity::where('id', $id)->delete();
        foreach ($ids['slot'] as $id) DB::table('regular_slots')->where('id', $id)->delete();
        foreach ($ids['user'] as $id) User::where('id', $id)->delete();
    } catch (Throwable $cleanupError) {
        fwrite(STDERR, 'WARNING: Fixture cleanup failed: ' . $cleanupError->getMessage() . PHP_EOL);
    }
    foreach ($ids['petugas'] as $id) {
        $ready = $barrier . "." . $id . ".ready";
        if (is_file($ready)) unlink($ready);
    }
    foreach ([$barrier, $payloadFile] as $p) {
        if (is_file($p)) unlink($p);
    }
    if (is_dir($tmpDir)) rmdir($tmpDir);
}

exit($exitCode);
