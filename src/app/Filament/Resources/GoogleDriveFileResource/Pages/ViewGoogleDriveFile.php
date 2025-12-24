<?php

namespace App\Filament\Resources\GoogleDriveFileResource\Pages;

use App\Filament\Resources\GoogleDriveFileResource;
use App\Services\GoogleDriveService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewGoogleDriveFile extends ViewRecord
{
    protected static string $resource = GoogleDriveFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Sincronizar')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $this->record->sync_status !== 'synced')
                ->action(function () {
                    $service = new GoogleDriveService();
                    $success = $service->syncFile($this->record);

                    if ($success) {
                        Notification::make()
                            ->title('Arquivo sincronizado com sucesso')
                            ->success()
                            ->send();

                        $this->refreshFormData(['sync_status', 'synced_at', 'web_view_link']);
                    } else {
                        Notification::make()
                            ->title('Falha na sincronização')
                            ->body($this->record->fresh()->error_message)
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('view_drive')
                ->label('Abrir no Drive')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => $this->record->web_view_link)
                ->openUrlInNewTab()
                ->visible(fn () => filled($this->record->web_view_link)),

            Actions\DeleteAction::make()
                ->before(function () {
                    if ($this->record->google_file_id) {
                        $service = new GoogleDriveService();
                        $service->deleteFile($this->record->google_file_id);
                    }
                }),
        ];
    }
}
