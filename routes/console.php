<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Elimina los tokens caducados (incluidos los del modo demo) una vez al día.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
