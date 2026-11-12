<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \App\Providers\AdminNotificationServiceProvider::class,
    App\Providers\VisitorNotificationServiceProvider::class,
    App\Providers\VisitorEventNotificationServiceProvider::class,
];
