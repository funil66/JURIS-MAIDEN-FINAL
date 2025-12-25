<?php

namespace App\Filament\Widgets;

use App\Models\SignatureRequest;
use App\Models\DigitalCertificate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        // Estatísticas de assinaturas
        $pending = SignatureRequest::whereIn('status', [
            SignatureRequest::STATUS_PENDING,
            SignatureRequest::STATUS_PARTIALLY_SIGNED,
        ])->count();

        $completedThisMonth = SignatureRequest::where('status', SignatureRequest::STATUS_COMPLETED)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        $expiringSoon = SignatureRequest::expiringSoon(7)->count();

        // Certificados expirando
        $certificatesExpiring = DigitalCertificate::expiringSoon(30)->count();

        return [
            Stat::make('Assinaturas Pendentes', $pending)
                ->description('Aguardando assinaturas')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success')
                ->chart([7, 4, 6, 8, 5, 3, $pending])
                    ->url(route('filament.funil.resources.signature-requests.index', ['tableFilters[status][values][0]' => 'pending'])),

            Stat::make('Assinadas este Mês', $completedThisMonth)
                ->description('Documentos concluídos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([3, 5, 7, 4, 6, 8, $completedThisMonth]),

            Stat::make('Expirando em 7 dias', $expiringSoon)
                ->description('Solicitar assinatura urgente')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiringSoon > 0 ? 'danger' : 'success')
                ->chart([2, 1, 3, 2, 1, 0, $expiringSoon]),

            Stat::make('Certificados Expirando', $certificatesExpiring)
                ->description('Próximos 30 dias')
                ->descriptionIcon('heroicon-m-key')
                ->color($certificatesExpiring > 0 ? 'warning' : 'success')
                ->chart([1, 0, 1, 0, 0, 1, $certificatesExpiring])
                ->url(route('filament.funil.resources.digital-certificates.index', ['tableFilters[expiring_soon]' => true])),
        ];
    }
}
