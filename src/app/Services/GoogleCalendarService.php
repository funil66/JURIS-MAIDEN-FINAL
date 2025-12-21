<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Service;
use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected ?Client $client = null;
    protected ?Calendar $service = null;

    /**
     * Inicializa o cliente do Google
     */
    public function __construct()
    {
        if ($this->isConfigured()) {
            $this->initClient();
        }
    }

    /**
     * Verifica se as credenciais estão configuradas
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.google.client_id')) 
            && !empty(config('services.google.client_secret'));
    }

    /**
     * Inicializa o cliente Google
     */
    protected function initClient(): void
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Retorna URL de autorização
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Troca código de autorização por token
     */
    public function fetchAccessToken(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        return $token;
    }

    /**
     * Define o token de acesso
     */
    public function setAccessToken(array $token): void
    {
        $this->client->setAccessToken($token);

        // Verificar se token expirou e renovar
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $this->client->setAccessToken($newToken);
            }
        }

        $this->service = new Calendar($this->client);
    }

    /**
     * Verifica se está autenticado
     */
    public function isAuthenticated(): bool
    {
        return $this->client && !$this->client->isAccessTokenExpired();
    }

    /**
     * Lista calendários do usuário
     */
    public function listCalendars(): array
    {
        if (!$this->service) {
            return [];
        }

        try {
            $calendarList = $this->service->calendarList->listCalendarList();
            $calendars = [];
            
            foreach ($calendarList->getItems() as $calendar) {
                $calendars[] = [
                    'id' => $calendar->getId(),
                    'summary' => $calendar->getSummary(),
                    'primary' => $calendar->getPrimary() ?? false,
                ];
            }
            
            return $calendars;
        } catch (\Exception $e) {
            Log::error('Google Calendar: Erro ao listar calendários', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Cria evento no Google Calendar a partir de um Service
     */
    public function createEventFromService(Service $service, string $calendarId = 'primary'): ?string
    {
        if (!$this->service) {
            return null;
        }

        try {
            $event = new GoogleEvent();
            
            // Título do evento
            $title = $service->serviceType?->name ?? 'Serviço';
            $title .= ' - ' . ($service->client?->name ?? 'Cliente');
            $event->setSummary($title);
            
            // Descrição
            $description = "Código: {$service->code}\n";
            if ($service->process_number) {
                $description .= "Processo: {$service->process_number}\n";
            }
            if ($service->court) {
                $description .= "Vara: {$service->court}\n";
            }
            if ($service->jurisdiction) {
                $description .= "Comarca: {$service->jurisdiction}\n";
            }
            if ($service->instructions) {
                $description .= "\nInstruções:\n{$service->instructions}";
            }
            $event->setDescription($description);
            
            // Local
            if ($service->full_location) {
                $event->setLocation($service->full_location);
            }
            
            // Data/Hora
            if ($service->scheduled_datetime) {
                $start = new EventDateTime();
                $start->setDateTime($service->scheduled_datetime->format('c'));
                $start->setTimeZone('America/Sao_Paulo');
                $event->setStart($start);
                
                // Duração padrão: 1 hora
                $end = new EventDateTime();
                $end->setDateTime($service->scheduled_datetime->addHour()->format('c'));
                $end->setTimeZone('America/Sao_Paulo');
                $event->setEnd($end);
            }
            
            // Lembrete 1 dia antes
            $event->setReminders([
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 60],
                ],
            ]);
            
            $createdEvent = $this->service->events->insert($calendarId, $event);
            
            Log::info('Google Calendar: Evento criado', [
                'service_id' => $service->id,
                'google_event_id' => $createdEvent->getId(),
            ]);
            
            return $createdEvent->getId();
            
        } catch (\Exception $e) {
            Log::error('Google Calendar: Erro ao criar evento', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Cria evento no Google Calendar a partir de um Event local
     */
    public function createEventFromLocalEvent(Event $localEvent, string $calendarId = 'primary'): ?string
    {
        if (!$this->service) {
            return null;
        }

        try {
            $event = new GoogleEvent();
            
            $event->setSummary($localEvent->title);
            
            if ($localEvent->description) {
                $event->setDescription($localEvent->description);
            }
            
            if ($localEvent->location) {
                $event->setLocation($localEvent->location);
            }
            
            // Data/Hora
            $start = new EventDateTime();
            $start->setDateTime($localEvent->start_datetime->format('c'));
            $start->setTimeZone('America/Sao_Paulo');
            $event->setStart($start);
            
            $end = new EventDateTime();
            $endTime = $localEvent->end_datetime ?? $localEvent->start_datetime->addHour();
            $end->setDateTime($endTime->format('c'));
            $end->setTimeZone('America/Sao_Paulo');
            $event->setEnd($end);
            
            $createdEvent = $this->service->events->insert($calendarId, $event);
            
            return $createdEvent->getId();
            
        } catch (\Exception $e) {
            Log::error('Google Calendar: Erro ao criar evento local', [
                'event_id' => $localEvent->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Atualiza evento no Google Calendar
     */
    public function updateEvent(string $eventId, array $data, string $calendarId = 'primary'): bool
    {
        if (!$this->service) {
            return false;
        }

        try {
            $event = $this->service->events->get($calendarId, $eventId);
            
            if (isset($data['summary'])) {
                $event->setSummary($data['summary']);
            }
            if (isset($data['description'])) {
                $event->setDescription($data['description']);
            }
            if (isset($data['location'])) {
                $event->setLocation($data['location']);
            }
            if (isset($data['start'])) {
                $start = new EventDateTime();
                $start->setDateTime($data['start']->format('c'));
                $start->setTimeZone('America/Sao_Paulo');
                $event->setStart($start);
            }
            if (isset($data['end'])) {
                $end = new EventDateTime();
                $end->setDateTime($data['end']->format('c'));
                $end->setTimeZone('America/Sao_Paulo');
                $event->setEnd($end);
            }
            
            $this->service->events->update($calendarId, $eventId, $event);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Google Calendar: Erro ao atualizar evento', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Deleta evento do Google Calendar
     */
    public function deleteEvent(string $eventId, string $calendarId = 'primary'): bool
    {
        if (!$this->service) {
            return false;
        }

        try {
            $this->service->events->delete($calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            Log::error('Google Calendar: Erro ao deletar evento', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Lista próximos eventos do Google Calendar
     */
    public function listUpcomingEvents(int $maxResults = 10, string $calendarId = 'primary'): array
    {
        if (!$this->service) {
            return [];
        }

        try {
            $optParams = [
                'maxResults' => $maxResults,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => now()->format('c'),
            ];
            
            $results = $this->service->events->listEvents($calendarId, $optParams);
            $events = [];
            
            foreach ($results->getItems() as $event) {
                $events[] = [
                    'id' => $event->getId(),
                    'summary' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'location' => $event->getLocation(),
                    'start' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                    'end' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                    'link' => $event->getHtmlLink(),
                ];
            }
            
            return $events;
            
        } catch (\Exception $e) {
            Log::error('Google Calendar: Erro ao listar eventos', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
