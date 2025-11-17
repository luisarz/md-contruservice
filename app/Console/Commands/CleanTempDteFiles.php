<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DteFileService;

class CleanTempDteFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte:clean-temp {--hours=24 : Antigüedad en horas de los archivos a eliminar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia archivos temporales de DTE más antiguos que X horas (default: 24h)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');

        $this->info("Limpiando archivos temporales DTE más antiguos que {$hours} horas...");

        $dteFileService = new DteFileService();
        $deletedCount = $dteFileService->cleanOldTempFiles($hours);

        if ($deletedCount > 0) {
            $this->info("✓ Se eliminaron {$deletedCount} archivo(s) temporal(es).");
        } else {
            $this->info("No se encontraron archivos temporales para eliminar.");
        }

        return Command::SUCCESS;
    }
}
