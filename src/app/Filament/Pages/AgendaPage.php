<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Diligence;
use App\Models\Proceeding;
use App\Models\Service;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgendaPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.agenda-page';

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $title = 'Agenda';

    protected static ?string $slug = 'agenda';

    protected static ?string $navigationGroup = 'Dashboard';

    protected static ?int $navigationSort = 2;

    public ?string $currentView = 'month';
    public ?string $currentDate = null;
    public ?string $selectedDate = null;
    public ?array $selectedEvent = null;

    public function mount(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function getEvents(): Collection
    {
        $start = Carbon::parse($this->currentDate)->startOfMonth()->subWeek();
        $end = Carbon::parse($this->currentDate)->endOfMonth()->addWeek();

        $events = collect();

        // Eventos do modelo Event
        $eventRecords = Event::whereBetween('start_date', [$start, $end])
            ->orWhereBetween('end_date', [$start, $end])
            ->get();

        foreach ($eventRecords as $event) {
            $events->push([
                'id' => 'event-' . $event->id,
                'type' => 'event',
                'title' => $event->title,
                'start' => $event->start_date,
                'end' => $event->end_date ?? $event->start_date,
                'color' => $event->color ?? 'indigo',
                'description' => $event->description,
                'all_day' => $event->all_day ?? false,
                'url' => route('filament.funil.resources.events.edit', $event),
            ]);
        }

        // Prazos processuais
        $deadlines = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->whereBetween('deadline_date', [$start, $end])
            ->with('process')
            ->get();

        foreach ($deadlines as $deadline) {
            $isOverdue = Carbon::parse($deadline->deadline_date)->isPast();
            $events->push([
                'id' => 'deadline-' . $deadline->id,
                'type' => 'deadline',
                'title' => '⏰ ' . ($deadline->title ?? 'Prazo processual'),
                'start' => $deadline->deadline_date,
                'end' => $deadline->deadline_date,
                'color' => $isOverdue ? 'rose' : 'amber',
                'description' => $deadline->process?->number ?? '',
                'all_day' => true,
                'url' => $deadline->process ? route('filament.funil.resources.processes.edit', $deadline->process) : null,
                'priority' => $isOverdue ? 'high' : 'medium',
            ]);
        }

        // Diligências
        $diligences = Diligence::whereBetween('scheduled_date', [$start, $end])
            ->orWhereBetween('scheduled_at', [$start, $end])
            ->with(['process', 'responsible'])
            ->get();

        foreach ($diligences as $diligence) {
            $scheduleDate = $diligence->scheduled_at ?? $diligence->scheduled_date;
            if (!$scheduleDate) continue;

            $typeColors = [
                'hearing' => 'sky',
                'expertise' => 'purple',
                'meeting' => 'emerald',
                'visit' => 'teal',
                'protocol' => 'orange',
            ];

            $typeIcons = [
                'hearing' => '⚖️',
                'expertise' => '🔬',
                'meeting' => '👥',
                'visit' => '🏢',
                'protocol' => '📋',
            ];

            $events->push([
                'id' => 'diligence-' . $diligence->id,
                'type' => 'diligence',
                'title' => ($typeIcons[$diligence->type] ?? '📌') . ' ' . $diligence->title,
                'start' => $scheduleDate,
                'end' => $scheduleDate,
                'color' => $typeColors[$diligence->type] ?? 'slate',
                'description' => $diligence->process?->number ?? '',
                'all_day' => false,
                'url' => route('filament.funil.resources.diligences.edit', $diligence),
            ]);
        }

        // Serviços agendados
        $services = Service::whereNotNull('scheduled_datetime')
            ->whereBetween('scheduled_datetime', [$start, $end])
            ->with('client')
            ->get();

        foreach ($services as $service) {
            $events->push([
                'id' => 'service-' . $service->id,
                'type' => 'service',
                'title' => '📝 ' . $service->title,
                'start' => $service->scheduled_datetime,
                'end' => $service->scheduled_datetime,
                'color' => 'violet',
                'description' => $service->client?->name ?? '',
                'all_day' => false,
                'url' => route('filament.funil.resources.services.edit', $service),
            ]);
        }

        return $events->sortBy('start');
    }

    public function getEventsForDate(?string $date = null): Collection
    {
        $date = $date ?? $this->selectedDate;
        $targetDate = Carbon::parse($date)->format('Y-m-d');

        return $this->getEvents()->filter(function ($event) use ($targetDate) {
            $start = Carbon::parse($event['start'])->format('Y-m-d');
            $end = Carbon::parse($event['end'])->format('Y-m-d');
            return $targetDate >= $start && $targetDate <= $end;
        })->values();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function previousMonth(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subMonth()->format('Y-m-d');
    }

    public function nextMonth(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addMonth()->format('Y-m-d');
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function getCalendarDays(): array
    {
        $currentMonth = Carbon::parse($this->currentDate);
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Começar no domingo da semana que contém o primeiro dia do mês
        $startOfCalendar = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $days = [];
        $current = $startOfCalendar->copy();

        while ($current <= $endOfCalendar) {
            $dateStr = $current->format('Y-m-d');
            $events = $this->getEvents()->filter(function ($event) use ($dateStr) {
                $start = Carbon::parse($event['start'])->format('Y-m-d');
                return $start === $dateStr;
            });

            $days[] = [
                'date' => $dateStr,
                'day' => $current->day,
                'isCurrentMonth' => $current->month === $currentMonth->month,
                'isToday' => $current->isToday(),
                'isSelected' => $dateStr === $this->selectedDate,
                'isWeekend' => $current->isWeekend(),
                'events' => $events->take(3)->values()->toArray(),
                'moreCount' => max(0, $events->count() - 3),
            ];

            $current->addDay();
        }

        return $days;
    }

    public function getUpcomingEvents(): Collection
    {
        return $this->getEvents()
            ->filter(fn ($event) => Carbon::parse($event['start'])->isFuture() || Carbon::parse($event['start'])->isToday())
            ->sortBy('start')
            ->take(10)
            ->values();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_event')
                ->label('Novo Evento')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(route('filament.funil.resources.events.create')),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayEvents = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->whereDate('deadline_date', today())
            ->count();

        $todayEvents += Diligence::whereDate('scheduled_date', today())
            ->orWhereDate('scheduled_at', today())
            ->count();

        return $todayEvents > 0 ? (string) $todayEvents : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdueCount = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->where('deadline_date', '<', today())
            ->count();

        return $overdueCount > 0 ? 'danger' : 'primary';
    }
}
