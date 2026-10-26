<?php

namespace App\Services;

use Midtrans\Transaction;

class MidtransStatusGateway
{
    public function getStatus(string $orderId): object
    {
        return Transaction::status($orderId);
    }
}
