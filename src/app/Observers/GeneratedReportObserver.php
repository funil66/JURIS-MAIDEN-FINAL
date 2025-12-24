<?php

namespace App\Observers;

use App\Models\GeneratedReport;
use App\Models\GoogleDriveFile;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;

class GeneratedReportObserver
{
    /**
     * Handle the GeneratedReport "created" event.
     */
    public function created(GeneratedReport $report): void
    {
        // Verificar se há configuração de Google Drive ativa
        $settings = GoogleDriveSetting::where('user_id', $report->generated_by)
            ->where('is_connected', true)
            ->where('sync_reports', true)
            ->first();

        if (!$settings) {
            return;
        }

        // Verificar se tem arquivo
        if (!$report->file_path) {
            return;
        }

        // Criar registro para sincronização
        GoogleDriveFile::createFromLocal(
            $report,
            $report->file_path,
            $report->file_name ?? 'relatorio.pdf'
        );

        // Se auto sync está ativo, sincronizar imediatamente
        if ($settings->auto_sync) {
            $service = new GoogleDriveService($settings);
            $file = GoogleDriveFile::where('fileable_type', GeneratedReport::class)
                ->where('fileable_id', $report->id)
                ->first();

            if ($file) {
                $service->syncFile($file);
            }
        }
    }

    /**
     * Handle the GeneratedReport "updated" event.
     */
    public function updated(GeneratedReport $report): void
    {
        // Se o arquivo foi atualizado, marcar para re-sync
        if ($report->isDirty('file_path')) {
            $driveFile = GoogleDriveFile::where('fileable_type', GeneratedReport::class)
                ->where('fileable_id', $report->id)
                ->first();

            if ($driveFile) {
                $driveFile->update([
                    'sync_status' => 'pending',
                    'local_modified_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle the GeneratedReport "deleted" event.
     */
    public function deleted(GeneratedReport $report): void
    {
        // Marcar arquivo do Drive como deletado
        $driveFile = GoogleDriveFile::where('fileable_type', GeneratedReport::class)
            ->where('fileable_id', $report->id)
            ->first();

        if ($driveFile) {
            $driveFile->update([
                'sync_status' => 'deleted',
            ]);
        }
    }
}
