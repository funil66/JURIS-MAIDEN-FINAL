<?php

namespace App\Filament\Widgets;

use App\Models\GoogleDriveFile;
use App\Models\GoogleDriveSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GoogleDriveStatsWidget extends BaseWidget
{
    protected static ?int $sort = 15;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        // Só mostrar se o usuário tiver uma conta Google Drive
        $settings = GoogleDriveSetting::where('user_id', auth()->id())->first();
        return $settings && $settings->is_connected;
    }

    protected function getStats(): array
    {
        $settings = GoogleDriveSetting::where('user_id', auth()->id())->first();
        
        if (!$settings || !$settings->is_connected) {
            return [];
        }

        $totalFiles = GoogleDriveFile::count();
        $syncedFiles = GoogleDriveFile::synced()->count();
        $pendingFiles = GoogleDriveFile::pending()->count();
        $failedFiles = GoogleDriveFile::failed()->count();
        
        $totalSize = GoogleDriveFile::synced()->sum('size');
        $formattedSize = $this->formatBytes($totalSize);

        return [
            Stat::make('📁 Arquivos no Drive', $syncedFiles)
                ->description('Sincronizados com sucesso')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.google-drive-files.index')),

            Stat::make('⏳ Pendentes', $pendingFiles)
                ->description('Aguardando sincronização')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingFiles > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.google-drive-files.index') . '?activeTab=pending'),

            Stat::make('💾 Espaço Usado', $formattedSize)
                ->description('No Google Drive')
                ->descriptionIcon('heroicon-o-server')
                ->color('primary'),

            Stat::make('🔄 Última Sync', $settings->last_sync_at?->diffForHumans() ?? 'Nunca')
                ->description($settings->last_error ? '⚠️ Com erros' : '✅ Sem erros')
                ->color($settings->last_error ? 'danger' : 'success')
                ->url(route('filament.admin.pages.google-drive-settings')),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
