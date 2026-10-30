<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Hash;

trait LogsInSiGorayAdmin
{
    protected function loginAsSiGorayAdmin(): void
    {
        $username = 'admin-test';
        $hash = Hash::make('PasswordTestingSangatPanjang!123');

        config()->set('sigoray_admin.username', $username);
        config()->set('sigoray_admin.password_hash', $hash);
        config()->set('sigoray_admin.session_minutes', 120);

        $this->withSession([
            'sigoray_admin_authenticated' => true,
            'sigoray_admin_username' => $username,
            'sigoray_admin_password_version' => hash('sha256', $hash),
            'sigoray_admin_since' => now()->timestamp,
        ]);
    }
}
