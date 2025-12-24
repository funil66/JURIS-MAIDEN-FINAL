<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleDriveSetting extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'root_folder_id',
        'root_folder_name',
        'auto_sync',
        'sync_reports',
        'sync_documents',
        'sync_invoices',
        'sync_contracts',
        'folder_structure',
        'is_connected',
        'last_sync_at',
        'last_error',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'auto_sync' => 'boolean',
        'sync_reports' => 'boolean',
        'sync_documents' => 'boolean',
        'sync_invoices' => 'boolean',
        'sync_contracts' => 'boolean',
        'is_connected' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeConnected($query)
    {
        return $query->where('is_connected', true);
    }

    public function scopeWithAutoSync($query)
    {
        return $query->where('auto_sync', true);
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Verifica se o token está expirado
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Verifica se precisa renovar o token (10 min antes de expirar)
     */
    public function needsTokenRefresh(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->subMinutes(10)->isPast();
    }

    /**
     * Opções de estrutura de pastas
     */
    public static function getFolderStructureOptions(): array
    {
        return [
            'flat' => '📁 Pasta Única (todos os arquivos juntos)',
            'by_client' => '👤 Por Cliente (pasta por cliente)',
            'by_type' => '📂 Por Tipo (documentos, relatórios, faturas)',
            'by_date' => '📅 Por Data (ano/mês)',
        ];
    }

    /**
     * Atualiza tokens de acesso
     */
    public function updateTokens(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = null): void
    {
        $this->access_token = encrypt($accessToken);
        
        if ($refreshToken) {
            $this->refresh_token = encrypt($refreshToken);
        }
        
        if ($expiresIn) {
            $this->token_expires_at = now()->addSeconds($expiresIn);
        }
        
        $this->save();
    }

    /**
     * Obtém access token descriptografado
     */
    public function getDecryptedAccessToken(): ?string
    {
        try {
            return $this->access_token ? decrypt($this->access_token) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém refresh token descriptografado
     */
    public function getDecryptedRefreshToken(): ?string
    {
        try {
            return $this->refresh_token ? decrypt($this->refresh_token) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Marca como desconectado
     */
    public function disconnect(): void
    {
        $this->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'is_connected' => false,
            'last_error' => null,
        ]);
    }

    /**
     * Registra erro
     */
    public function logError(string $message): void
    {
        $this->update([
            'last_error' => $message,
        ]);
    }

    /**
     * Registra sincronização bem sucedida
     */
    public function logSuccessfulSync(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Obtém ou cria configuração para usuário atual
     */
    public static function getForCurrentUser(): ?self
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return null;
        }

        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'folder_structure' => 'by_client',
                'auto_sync' => false,
                'sync_reports' => true,
                'sync_documents' => true,
            ]
        );
    }
}
