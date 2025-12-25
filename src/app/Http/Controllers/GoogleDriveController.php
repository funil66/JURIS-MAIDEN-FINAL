<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    /**
     * Redireciona para autenticação do Google
     */
    public function redirect()
    {
        $service = new GoogleDriveService();
        return redirect($service->getAuthUrl());
    }

    /**
     * Callback do OAuth do Google
     */
    public function callback(Request $request)
    {
        // Verificar se houve erro
        if ($request->has('error')) {
            Log::error('Google Drive OAuth Error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);

            return redirect()
                ->route('filament.funil.pages.google-drive-settings')
                ->with('error', 'Erro na autenticação: ' . $request->get('error_description', 'Erro desconhecido'));
        }

        // Verificar se temos o código de autorização
        if (!$request->has('code')) {
            return redirect()
                ->route('filament.funil.pages.google-drive-settings')
                ->with('error', 'Código de autorização não recebido');
        }

        try {
            $service = new GoogleDriveService();
            $success = $service->handleCallback($request->get('code'));

            if ($success) {
                return redirect()
                    ->route('filament.funil.pages.google-drive-settings')
                    ->with('success', 'Google Drive conectado com sucesso!');
            } else {
                return redirect()
                    ->route('filament.funil.pages.google-drive-settings')
                    ->with('error', 'Falha ao conectar com o Google Drive');
            }
        } catch (\Exception $e) {
            Log::error('Google Drive Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('filament.funil.pages.google-drive-settings')
                ->with('error', 'Erro ao processar autenticação: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta a conta do Google Drive
     */
    public function disconnect()
    {
        $service = new GoogleDriveService();
        $service->disconnect();

        return redirect()
            ->route('filament.funil.pages.google-drive-settings')
            ->with('success', 'Google Drive desconectado com sucesso');
    }
}
