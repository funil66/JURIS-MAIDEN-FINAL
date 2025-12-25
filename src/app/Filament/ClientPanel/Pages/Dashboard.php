<?php

namespace App\Filament\ClientPanel\Pages;

use App\Models\Event;
use App\Models\Service;
use App\Models\Transaction;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Início';
    protected static ?string $title = 'Portal do Cliente';
    protected static ?string $slug = 'client-dashboard';
    protected static string $view = 'filament.client-panel.pages.dashboard';
    protected static ?int $navigationSort = 1;

    public array $stats = [];
    public $upcomingEvents = [];
    public $recentServices = [];
    public $pendingPayments = [];

    public function mount(): void
    {
        $client = Auth::guard('client')->user();

        // Estatísticas
        $this->stats = [
            'total_services' => Service::where('client_id', $client->id)->count(),
            'services_in_progress' => Service::where('client_id', $client->id)
                ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
                ->count(),
            'services_completed' => Service::where('client_id', $client->id)
                ->where('status', 'completed')
                ->count(),
            'pending_payments' => Transaction::where('client_id', $client->id)
                ->where('status', 'pending')
                ->where('type', 'income')
                ->sum('amount'),
        ];

        // Próximos eventos
        $this->upcomingEvents = Event::where('client_id', $client->id)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        // Serviços recentes
        $this->recentServices = Service::where('client_id', $client->id)
            ->with('serviceType')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Pagamentos pendentes
        $this->pendingPayments = Transaction::where('client_id', $client->id)
            ->where('status', 'pending')
            ->where('type', 'income')
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }
}
