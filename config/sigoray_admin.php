<?php

return [
    // Akun Admin khusus; tidak menggunakan tabel users maupun OTP.
    'username' => env('SIGORAY_ADMIN_USERNAME'),
    'password_hash' => env('SIGORAY_ADMIN_PASSWORD_HASH'),
    'session_minutes' => (int) env('SIGORAY_ADMIN_SESSION_MINUTES', 120),
];
