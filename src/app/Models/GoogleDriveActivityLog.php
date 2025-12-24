<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleDriveActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'google_drive_file_id',
        'action',
        'file_name',
        'description',
        'error_details',
        'ip_address',
        'user_agent',
    ];

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(GoogleDriveFile::class, 'google_drive_file_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeErrors($query)
    {
        return $query->where('action', 'error');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==========================================
    // CONSTANTES
    // ==========================================

    public static function getActionOptions(): array
    {
        return [
            'upload' => '⬆️ Upload',
            'download' => '⬇️ Download',
            'delete' => '🗑️ Deleção',
            'rename' => '✏️ Renomear',
            'move' => '📁 Mover',
            'share' => '🔗 Compartilhar',
            'sync' => '🔄 Sincronização',
            'connect' => '🔌 Conexão',
            'disconnect' => '🔌 Desconexão',
            'error' => '❌ Erro',
        ];
    }

    public static function getActionColors(): array
    {
        return [
            'upload' => 'success',
            'download' => 'info',
            'delete' => 'danger',
            'rename' => 'warning',
            'move' => 'warning',
            'share' => 'primary',
            'sync' => 'info',
            'connect' => 'success',
            'disconnect' => 'gray',
            'error' => 'danger',
        ];
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Registra atividade
     */
    public static function log(
        string $action,
        ?string $fileName = null,
        ?string $description = null,
        ?int $fileId = null,
        ?string $errorDetails = null
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'google_drive_file_id' => $fileId,
            'action' => $action,
            'file_name' => $fileName,
            'description' => $description,
            'error_details' => $errorDetails,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registra upload
     */
    public static function logUpload(GoogleDriveFile $file): self
    {
        return static::log(
            'upload',
            $file->name,
            "Arquivo enviado para o Google Drive",
            $file->id
        );
    }

    /**
     * Registra download
     */
    public static function logDownload(GoogleDriveFile $file): self
    {
        return static::log(
            'download',
            $file->name,
            "Arquivo baixado do Google Drive",
            $file->id
        );
    }

    /**
     * Registra erro
     */
    public static function logError(string $error, ?string $fileName = null, ?int $fileId = null): self
    {
        return static::log(
            'error',
            $fileName,
            "Erro na operação",
            $fileId,
            $error
        );
    }

    /**
     * Registra conexão
     */
    public static function logConnect(): self
    {
        return static::log(
            'connect',
            null,
            "Conta Google Drive conectada com sucesso"
        );
    }

    /**
     * Registra desconexão
     */
    public static function logDisconnect(): self
    {
        return static::log(
            'disconnect',
            null,
            "Conta Google Drive desconectada"
        );
    }

    /**
     * Registra sincronização
     */
    public static function logSync(int $count): self
    {
        return static::log(
            'sync',
            null,
            "{$count} arquivo(s) sincronizado(s) com sucesso"
        );
    }
}
