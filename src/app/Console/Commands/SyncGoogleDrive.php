<?php

namespace App\Console\Commands;

use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class SyncGoogleDrive extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'google-drive:sync 
                            {--user= : ID do usuário específico}
                            {--limit=50 : Limite de arquivos por vez}
                            {--force : Forçar sincronização mesmo sem auto_sync}';

    /**
     * The console command description.
     */
    protected $description = 'Sincroniza arquivos pendentes com o Google Drive';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Iniciando sincronização com Google Drive...');

        $query = GoogleDriveSetting::connected();

        // Filtrar por usuário específico
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        // Se não forçar, apenas usuários com auto_sync
        if (!$this->option('force')) {
            $query->withAutoSync();
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->warn('⚠️ Nenhuma conta Google Drive conectada encontrada.');
            return Command::SUCCESS;
        }

        $totalSynced = 0;
        $limit = (int) $this->option('limit');

        foreach ($settings as $setting) {
            $this->info("📁 Sincronizando arquivos do usuário #{$setting->user_id}...");

            try {
                $service = new GoogleDriveService($setting);
                $synced = $service->syncPendingFiles($limit);
                $totalSynced += $synced;

                if ($synced > 0) {
                    $this->info("   ✅ {$synced} arquivo(s) sincronizado(s)");
                } else {
                    $this->info("   ℹ️ Nenhum arquivo pendente");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Erro: {$e->getMessage()}");
                $setting->logError($e->getMessage());
            }
        }

        $this->newLine();
        $this->info("🎉 Sincronização concluída! Total: {$totalSynced} arquivo(s)");

        return Command::SUCCESS;
    }
}
