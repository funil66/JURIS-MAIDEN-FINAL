<?php

namespace App\Filament\Resources\GoogleDriveFileResource\Pages;

use App\Filament\Resources\GoogleDriveFileResource;
use App\Models\GoogleDriveFile;
use App\Services\GoogleDriveService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListGoogleDriveFiles extends ListRecords
{
    protected static string $resource = GoogleDriveFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_all')
                ->label('Sincronizar Pendentes')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => GoogleDriveFile::pending()->exists())
                ->action(function () {
                    $service = new GoogleDriveService();
                    $synced = $service->syncPendingFiles(50);

                    Notification::make()
                        ->title("{$synced} arquivo(s) sincronizado(s)")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('settings')
                ->label('Configurações')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(route('filament.funil.pages.google-drive-settings')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(GoogleDriveFile::count())
                ->icon('heroicon-o-document-duplicate'),

            'synced' => Tab::make('Sincronizados')
                ->badge(GoogleDriveFile::synced()->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->synced()),

            'pending' => Tab::make('Pendentes')
                ->badge(GoogleDriveFile::pending()->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending()),

            'failed' => Tab::make('Com Falha')
                ->badge(GoogleDriveFile::failed()->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->failed()),
        ];
    }
}
