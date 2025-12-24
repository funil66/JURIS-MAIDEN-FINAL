<?php

namespace App\Observers;

use App\Models\GeneratedDocument;
use App\Models\GoogleDriveFile;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;

class GeneratedDocumentObserver
{
    /**
     * Handle the GeneratedDocument "created" event.
     */
    public function created(GeneratedDocument $document): void
    {
        // Verificar se há configuração de Google Drive ativa
        $settings = GoogleDriveSetting::where('user_id', $document->user_id)
            ->where('is_connected', true)
            ->where('sync_documents', true)
            ->first();

        if (!$settings) {
            return;
        }

        // Verificar se tem arquivo
        if (!$document->file_path) {
            return;
        }

        // Criar registro para sincronização
        GoogleDriveFile::createFromLocal(
            $document,
            $document->file_path,
            $document->file_name ?? $document->title . '.pdf'
        );

        // Se auto sync está ativo, sincronizar imediatamente
        if ($settings->auto_sync) {
            $service = new GoogleDriveService($settings);
            $file = GoogleDriveFile::where('fileable_type', GeneratedDocument::class)
                ->where('fileable_id', $document->id)
                ->first();

            if ($file) {
                $service->syncFile($file);
            }
        }
    }

    /**
     * Handle the GeneratedDocument "updated" event.
     */
    public function updated(GeneratedDocument $document): void
    {
        // Se o arquivo foi atualizado, marcar para re-sync
        if ($document->isDirty('file_path')) {
            $driveFile = GoogleDriveFile::where('fileable_type', GeneratedDocument::class)
                ->where('fileable_id', $document->id)
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
     * Handle the GeneratedDocument "deleted" event.
     */
    public function deleted(GeneratedDocument $document): void
    {
        // Marcar arquivo do Drive como deletado
        $driveFile = GoogleDriveFile::where('fileable_type', GeneratedDocument::class)
            ->where('fileable_id', $document->id)
            ->first();

        if ($driveFile) {
            // Opcional: deletar do Drive também
            // $service = new GoogleDriveService();
            // $service->deleteFile($driveFile->google_file_id);

            $driveFile->update([
                'sync_status' => 'deleted',
            ]);
        }
    }
}
