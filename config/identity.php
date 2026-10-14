<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identity Hash Key
    |--------------------------------------------------------------------------
    |
    | Secret khusus untuk membuat HMAC hash NIK.
    | Nilai sebenarnya disimpan di .env dan tidak boleh di-commit.
    |
    */

    'hash_key' => env('IDENTITY_HASH_KEY'),
];