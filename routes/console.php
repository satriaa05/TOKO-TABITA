<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Webhook bisa gagal; sinkronisasi berkala menutup celah tersebut.
Schedule::command('payments:sync-pending')->everyTenMinutes()->withoutOverlapping();
Schedule::command('media:prune')->daily();
