<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use App\Models\Service;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\CreateAction as CalendarCreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction as CalendarDeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction as CalendarEditAction;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarPage extends FullCalendarWidget
{
    public Model|string|null $model = Event::class;

    /**
     * Configurações do calendário
     */
    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'locale' => 'pt-br',
            'firstDay' => 0, // Domingo
            'editable' => true,
            'selectable' => true,
            'dayMaxEvents' => true,
            'navLinks' => true,
            'nowIndicator' => true,
            'businessHours' => [
                'daysOfWeek' => [1, 2, 3, 4, 5], // Segunda a Sexta
                'startTime' => '08:00',
                'endTime' => '18:00',
            ],
        ];
    }

    /**
     * Buscar eventos para o calendário
     */
    public function fetchEvents(array $info): array
    {
        $events = [];

        // Eventos do modelo Event
        $eventRecords = Event::query()
            ->where('starts_at', '>=', $info['start'])
            ->where('starts_at', '<=', $info['end'])
            ->get();

        foreach ($eventRecords as $event) {
            $events[] = EventData::make()
                ->id($event->id)
                ->title($event->title)
                ->start($event->starts_at)
                ->end($event->ends_at ?? $event->starts_at)
                ->allDay($event->all_day)
                ->backgroundColor($event->color)
                ->borderColor($event->color)
                ->extendedProps([
                    'type' => 'event',
                    'status' => $event->status,
                ]);
        }

        // Serviços com data agendada
        $services = Service::query()
            ->whereNotNull('scheduled_datetime')
            ->where('scheduled_datetime', '>=', $info['start'])
            ->where('scheduled_datetime', '<=', $info['end'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['client', 'serviceType'])
            ->get();

        foreach ($services as $service) {
            $events[] = EventData::make()
                ->id('service-' . $service->id)
                ->title('🔧 ' . $service->serviceType->name . ' - ' . $service->client->name)
                ->start($service->scheduled_datetime)
                ->end($service->scheduled_datetime->addHours(2))
                ->allDay(false)
                ->backgroundColor('#dc2626') // Vermelho para serviços
                ->borderColor('#dc2626')
                ->url(route('filament.funil.resources.services.edit', $service->id))
                ->extendedProps([
                    'type' => 'service',
                    'service_id' => $service->id,
                ]);
        }

        // Prazos de serviços
        $deadlines = Service::query()
            ->whereNotNull('deadline_date')
            ->where('deadline_date', '>=', $info['start'])
            ->where('deadline_date', '<=', $info['end'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['client', 'serviceType'])
            ->get();

        foreach ($deadlines as $service) {
            $events[] = EventData::make()
                ->id('deadline-' . $service->id)
                ->title('⏰ Prazo: ' . $service->serviceType->name)
                ->start($service->deadline_date)
                ->allDay(true)
                ->backgroundColor('#f59e0b') // Amarelo para prazos
                ->borderColor('#f59e0b')
                ->url(route('filament.funil.resources.services.edit', $service->id))
                ->extendedProps([
                    'type' => 'deadline',
                    'service_id' => $service->id,
                ]);
        }

        return $events;
    }

    /**
     * Formulário para criar/editar eventos
     */
    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Título')
                ->required(),

            Select::make('type')
                ->label('Tipo')
                ->options(Event::getTypeOptions())
                ->default('task')
                ->required(),

            Toggle::make('all_day')
                ->label('Dia Inteiro')
                ->default(false),

            DateTimePicker::make('starts_at')
                ->label('Início')
                ->required()
                ->native(false)
                ->seconds(false),

            DateTimePicker::make('ends_at')
                ->label('Término')
                ->native(false)
                ->seconds(false),

            Select::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name')
                ->searchable()
                ->preload(),

            Select::make('service_id')
                ->label('Serviço')
                ->relationship('service', 'code')
                ->searchable()
                ->preload(),

            TextInput::make('location')
                ->label('Local'),

            ColorPicker::make('color')
                ->label('Cor')
                ->default('#3b82f6'),

            Textarea::make('description')
                ->label('Descrição')
                ->rows(2),
        ];
    }

    /**
     * Ações do header
     */
    protected function headerActions(): array
    {
        return [
            CalendarCreateAction::make()
                ->label('Novo Evento')
                ->mountUsing(function (Form $form, array $arguments) {
                    $form->fill([
                        'starts_at' => $arguments['start'] ?? now(),
                        'ends_at' => $arguments['end'] ?? null,
                        'all_day' => $arguments['allDay'] ?? false,
                    ]);
                }),
        ];
    }

    /**
     * Ações ao clicar em evento
     */
    protected function modalActions(): array
    {
        return [
            CalendarEditAction::make()
                ->label('Editar'),
            CalendarDeleteAction::make()
                ->label('Excluir'),
        ];
    }

    /**
     * Ao arrastar e soltar evento
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        // Ignorar se for serviço ou deadline
        if (str_starts_with($event['id'], 'service-') || str_starts_with($event['id'], 'deadline-')) {
            return false;
        }

        $eventModel = Event::find($event['id']);
        
        if ($eventModel) {
            $eventModel->update([
                'starts_at' => $event['start'],
                'ends_at' => $event['end'] ?? $event['start'],
            ]);
        }

        return true;
    }

    /**
     * Ao redimensionar evento
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool
    {
        if (str_starts_with($event['id'], 'service-') || str_starts_with($event['id'], 'deadline-')) {
            return false;
        }

        $eventModel = Event::find($event['id']);
        
        if ($eventModel) {
            $eventModel->update([
                'ends_at' => $event['end'],
            ]);
        }

        return true;
    }

    /**
     * Resolve o registro do evento
     */
    public function resolveEventRecord(array $data): Model
    {
        return Event::find($data['id']);
    }

    /**
     * Título da página
     */
    public static function getNavigationLabel(): string
    {
        return 'Calendário';
    }
}
