<?php

/**
 * SI-GORAY - OTP Login Concurrency Test
 *
 * Jalankan:
 * php tests/manual/gate_otp_concurrency.php
 *
 * HANYA untuk database gor_reservation_testing.
 */

use App\Models\OtpVerification;
use App\Models\PetugasGerbang;
use App\Models\User;
use App\Services\GateAuthService;
use App\Services\OtpService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Paksa koneksi testing sebelum Laravel membaca .env.
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE=gor_reservation_testing');

$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = 'gor_reservation_testing';

$_SERVER['DB_CONNECTION'] = 'mysql';
$_SERVER['DB_DATABASE'] = 'gor_reservation_testing';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// Paksa dan verifikasi database setiap proses.
config([
    'database.default' => 'mysql',
    'database.connections.mysql.database'
        => 'gor_reservation_testing',
]);

DB::purge('mysql');

if (
    DB::connection()->getDatabaseName()
    !== 'gor_reservation_testing'
) {
    throw new RuntimeException(
        'DIBATALKAN: database bukan testing.'
    );
}

function jalankanWorker(
    string $inputPath,
    string $outputPath
): void {
    $input = json_decode(
        file_get_contents($inputPath),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $startAt = (float) $input['start_at'];

    // Kedua worker menunggu waktu mulai yang sama.
    while (microtime(true) < $startAt) {
        usleep(1000);
    }

    $result = [
        'success' => false,
        'reason' => null,
    ];

    try {
        $user = User::query()->findOrFail(
            $input['user_id']
        );

        $token = app(GateAuthService::class)->login(
            $user,
            $input['otp']
        );

        $result['success'] = true;
        $result['token_issued'] = !empty($token);

    } catch (RuntimeException $e) {
        $result['reason'] = 'LOGIN_REJECTED';

    } catch (Throwable $e) {
        $result['reason'] = 'UNEXPECTED_ERROR';
        $result['error_class'] = $e::class;
    }

    file_put_contents(
        $outputPath,
        json_encode(
            $result,
            JSON_THROW_ON_ERROR
        )
    );
}

function jalankanParent(): void
{
    $user = null;
    $folder = sys_get_temp_dir() .
        DIRECTORY_SEPARATOR .
        'si_goray_otp_' .
        Str::random(12);

    if (!mkdir($folder, 0700, true)) {
        throw new RuntimeException(
            'Tidak bisa membuat folder sementara.'
        );
    }

    $inputPath = $folder . '/input.json';
    $outputs = [
        $folder . '/worker1.json',
        $folder . '/worker2.json',
    ];

    try {
        // Buat akun pengujian baru.
        $user = User::create([
            'no_hp_email' =>
                'concurrency-' .
                Str::uuid() .
                '@example.com',
            'status_otp' => true,
        ]);

        $petugas = new PetugasGerbang();
        $petugas->user_id = $user->id;
        $petugas->nama = 'Petugas Concurrency OTP';
        $petugas->username =
            'otp_' . Str::lower(Str::random(12));
        $petugas->no_hp = null;
        $petugas->aktif = true;
        $petugas->save();

        // Tangkap kode OTP hanya di memori.
        $kode = null;

        app(OtpService::class)->issue(
            $user,
            'LOGIN_PETUGAS',
            function (
                User $penerima,
                string $otp
            ) use (&$kode): void {
                $kode = $otp;
            }
        );

        if (!$kode) {
            throw new RuntimeException(
                'Gagal membuat OTP pengujian.'
            );
        }

        $otpRecord = OtpVerification::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Beri worker waktu untuk mulai bersamaan.
        file_put_contents(
            $inputPath,
            json_encode([
                'user_id' => $user->id,
                'otp' => $kode,
                'start_at' => microtime(true) + 2,
            ], JSON_THROW_ON_ERROR)
        );

        $processes = [];

        for ($i = 0; $i < 2; $i++) {
            $command = [
                PHP_BINARY,
                __FILE__,
                'worker',
                $inputPath,
                $outputs[$i],
            ];

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open(
                $command,
                $descriptors,
                $pipes,
                dirname(__DIR__, 2)
            );

            if (!is_resource($process)) {
                throw new RuntimeException(
                    'Gagal menjalankan worker.'
                );
            }

            fclose($pipes[0]);

            $processes[] = [
                'process' => $process,
                'pipes' => $pipes,
            ];
        }

        $exitCodes = [];

        foreach ($processes as $item) {
            $stdout = stream_get_contents(
                $item['pipes'][1]
            );

            $stderr = stream_get_contents(
                $item['pipes'][2]
            );

            fclose($item['pipes'][1]);
            fclose($item['pipes'][2]);

            $exitCodes[] = proc_close(
                $item['process']
            );

            if ($stdout || $stderr) {
                echo "Worker output: ",
                    trim($stdout . ' ' . $stderr),
                    PHP_EOL;
            }
        }

        $results = [];

        foreach ($outputs as $path) {
            if (!is_file($path)) {
                throw new RuntimeException(
                    'Output worker tidak ditemukan.'
                );
            }

            $results[] = json_decode(
                file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        $successCount = count(
            array_filter(
                $results,
                fn ($r) => $r['success'] === true
            )
        );

        $rejectCount = count(
            array_filter(
                $results,
                fn ($r) =>
                    $r['success'] === false &&
                    $r['reason'] === 'LOGIN_REJECTED'
            )
        );

        $tokenCount = $user->tokens()->count();

        $otpRecord->refresh();

        echo PHP_EOL;
        echo "=== HASIL CONCURRENCY OTP ===", PHP_EOL;

        echo "Worker berhasil : {$successCount}",
            PHP_EOL;

        echo "Worker ditolak   : {$rejectCount}",
            PHP_EOL;

        echo "Token diterbitkan: {$tokenCount}",
            PHP_EOL;

        echo "Status OTP      : ",
            $otpRecord->status,
            PHP_EOL;

        $passed =
            $successCount === 1 &&
            $rejectCount === 1 &&
            $tokenCount === 1 &&
            $otpRecord->status === 'VERIFIED' &&
            !in_array(
                true,
                array_map(
                    fn ($code) => $code !== 0,
                    $exitCodes
                ),
                true
            );

        if ($passed) {
            echo PHP_EOL,
                "PASS - OTP hanya menghasilkan satu token.",
                PHP_EOL;
        } else {
            echo PHP_EOL,
                "FAIL - Hasil concurrency tidak sesuai.",
                PHP_EOL;

            throw new RuntimeException(
                'Concurrency OTP gagal.'
            );
        }

    } finally {
        // Hapus hanya data pengujian yang dibuat
        // oleh skrip ini. Data lain tidak disentuh.
        if ($user !== null) {
            $user->tokens()->delete();

            OtpVerification::query()
                ->where('user_id', $user->id)
                ->delete();

            PetugasGerbang::query()
                ->where('user_id', $user->id)
                ->delete();

            $user->delete();
        }

        foreach ([
            $inputPath,
            ...$outputs,
        ] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($folder)) {
            rmdir($folder);
        }
    }
}

if (($argv[1] ?? '') === 'worker') {
    jalankanWorker(
        $argv[2],
        $argv[3]
    );
} else {
    jalankanParent();
}
