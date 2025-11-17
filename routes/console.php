<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Limpieza automática de archivos temporales DTE cada día a las 2:00 AM
Schedule::command('dte:clean-temp')->daily()->at('02:00');
