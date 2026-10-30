<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GenerateAdminPasswordHash extends Command
{
    protected $signature = 'sigoray:admin-password-hash';
    protected $description = 'Buat hash password Admin tanpa menampilkan password di terminal';

    public function handle(): int
    {
        $password = (string) $this->secret('Password Admin (minimal 14 karakter)');
        $confirm = (string) $this->secret('Ulangi password Admin');

        if (strlen($password) < 14 || ! hash_equals($password, $confirm)) {
            $this->error('Password minimal 14 karakter dan konfirmasi harus sama.');
            return self::FAILURE;
        }

        $this->line('Masukkan baris berikut ke .env (jangan bagikan isi .env):');
        $this->line("SIGORAY_ADMIN_PASSWORD_HASH='" . Hash::make($password) . "'");
        return self::SUCCESS;
    }
}
