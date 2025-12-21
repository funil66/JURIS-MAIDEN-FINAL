<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    /**
     * Handle the OAuth callback from Google
     */
    public function callback(Request $request)
    {
        // Verificar se há erro
        if ($request->has('error')) {
            Log::error('Google OAuth Error: ' . $request->get('error'));
            
            return redirect('/funil/google-calendar-settings')
                ->with('error', 'Erro na autorização: ' . $request->get('error'));
        }

        // Verificar código de autorização
        if (!$request->has('code')) {
            return redirect('/funil/google-calendar-settings')
                ->with('error', 'Código de autorização não recebido.');
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect('/funil/login')
                    ->with('error', 'Você precisa estar logado para conectar o Google Calendar.');
            }

            $service = new GoogleCalendarService($user);
            
            // Trocar código por tokens
            $tokens = $service->authenticate($request->get('code'));
            
            if (!$tokens) {
                throw new \Exception('Não foi possível obter os tokens de acesso.');
            }

            // Salvar tokens no usuário
            $user->saveGoogleTokens($tokens);

            Log::info('Google Calendar conectado para usuário: ' . $user->id);

            return redirect('/funil/google-calendar-settings')
                ->with('success', 'Google Calendar conectado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Google OAuth Exception: ' . $e->getMessage());
            
            return redirect('/funil/google-calendar-settings')
                ->with('error', 'Erro ao conectar: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Google Calendar
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                $user->disconnectGoogleCalendar();
            }

            return redirect('/funil/google-calendar-settings')
                ->with('success', 'Google Calendar desconectado.');

        } catch (\Exception $e) {
            return redirect('/funil/google-calendar-settings')
                ->with('error', 'Erro ao desconectar: ' . $e->getMessage());
        }
    }

    /**
     * Force sync all events
     */
    public function sync()
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isGoogleCalendarConnected()) {
                return redirect('/funil/google-calendar-settings')
                    ->with('error', 'Conecte o Google Calendar primeiro.');
            }

            $service = new GoogleCalendarService($user);

            if (!$service->isAuthenticated()) {
                return redirect('/funil/google-calendar-settings')
                    ->with('warning', 'Token expirado. Por favor, reconecte.');
            }

            // Sincronizar eventos
            $synced = 0;
            
            $events = \App\Models\Event::where('date', '>=', now()->subDays(7))->get();
            foreach ($events as $event) {
                try {
                    $service->syncEvent($event, 'event');
                    $synced++;
                } catch (\Exception $e) {
                    Log::warning('Erro ao sincronizar evento ' . $event->id . ': ' . $e->getMessage());
                }
            }

            return redirect('/funil/google-calendar-settings')
                ->with('success', "$synced eventos sincronizados com sucesso!");

        } catch (\Exception $e) {
            return redirect('/funil/google-calendar-settings')
                ->with('error', 'Erro na sincronização: ' . $e->getMessage());
        }
    }
}
