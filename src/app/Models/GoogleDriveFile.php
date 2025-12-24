<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoogleDriveFile extends Model
{
    use SoftDeletes, HasGlobalUid;

    /**
     * Prefixo do UID para arquivos do Google Drive
     */
    public static function getUidPrefix(): string
    {
        return 'GDF';
    }

    protected $fillable = [
        'fileable_type',
        'fileable_id',
        'google_file_id',
        'google_folder_id',
        'web_view_link',
        'web_content_link',
        'name',
        'mime_type',
        'size',
        'md5_checksum',
        'local_path',
        'drive_path',
        'sync_status',
        'sync_direction',
        'version',
        'local_modified_at',
        'drive_modified_at',
        'synced_at',
        'uploaded_by',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'version' => 'integer',
        'local_modified_at' => 'datetime',
        'drive_modified_at' => 'datetime',
        'synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Entidade relacionada (polymorphic)
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuário que fez upload
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Logs de atividade
     */
    public function activityLogs()
    {
        return $this->hasMany(GoogleDriveActivityLog::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('fileable_type', $type);
    }

    // ==========================================
    // ATRIBUTOS
    // ==========================================

    /**
     * Tamanho formatado
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Ícone baseado no mime type
     */
    public function getFileIconAttribute(): string
    {
        $mimeIcons = [
            'application/pdf' => '📄',
            'application/msword' => '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📝',
            'application/vnd.ms-excel' => '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '📊',
            'image/jpeg' => '🖼️',
            'image/png' => '🖼️',
            'image/gif' => '🖼️',
            'text/plain' => '📃',
            'text/csv' => '📊',
        ];

        return $mimeIcons[$this->mime_type] ?? '📁';
    }

    /**
     * Status formatado
     */
    public function getSyncStatusBadgeAttribute(): string
    {
        $statuses = [
            'pending' => '⏳ Pendente',
            'syncing' => '🔄 Sincronizando',
            'synced' => '✅ Sincronizado',
            'failed' => '❌ Falhou',
            'deleted' => '🗑️ Deletado',
            'conflict' => '⚠️ Conflito',
        ];

        return $statuses[$this->sync_status] ?? $this->sync_status;
    }

    // ==========================================
    // CONSTANTES
    // ==========================================

    public static function getSyncStatusOptions(): array
    {
        return [
            'pending' => '⏳ Pendente',
            'syncing' => '🔄 Sincronizando',
            'synced' => '✅ Sincronizado',
            'failed' => '❌ Falhou',
            'deleted' => '🗑️ Deletado',
            'conflict' => '⚠️ Conflito',
        ];
    }

    public static function getSyncStatusColors(): array
    {
        return [
            'pending' => 'warning',
            'syncing' => 'info',
            'synced' => 'success',
            'failed' => 'danger',
            'deleted' => 'gray',
            'conflict' => 'warning',
        ];
    }

    public static function getSyncDirectionOptions(): array
    {
        return [
            'upload' => '⬆️ Upload para Drive',
            'download' => '⬇️ Download do Drive',
            'bidirectional' => '🔄 Bidirecional',
        ];
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Marca como sincronizado
     */
    public function markAsSynced(array $driveData = []): void
    {
        $this->update(array_merge([
            'sync_status' => 'synced',
            'synced_at' => now(),
            'error_message' => null,
        ], $driveData));
    }

    /**
     * Marca como falhou
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Marca como sincronizando
     */
    public function markAsSyncing(): void
    {
        $this->update([
            'sync_status' => 'syncing',
        ]);
    }

    /**
     * Verifica se está sincronizado
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'synced';
    }

    /**
     * Verifica se precisa sincronizar
     */
    public function needsSync(): bool
    {
        if ($this->sync_status === 'synced' && $this->local_modified_at && $this->synced_at) {
            return $this->local_modified_at->isAfter($this->synced_at);
        }

        return in_array($this->sync_status, ['pending', 'failed', 'conflict']);
    }

    /**
     * Cria registro para um arquivo local
     */
    public static function createFromLocal(Model $fileable, string $localPath, string $fileName): self
    {
        $fullPath = storage_path('app/' . $localPath);
        
        return static::create([
            'fileable_type' => get_class($fileable),
            'fileable_id' => $fileable->id,
            'name' => $fileName,
            'local_path' => $localPath,
            'mime_type' => mime_content_type($fullPath) ?? 'application/octet-stream',
            'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'md5_checksum' => file_exists($fullPath) ? md5_file($fullPath) : null,
            'local_modified_at' => file_exists($fullPath) ? now() : null,
            'sync_status' => 'pending',
            'sync_direction' => 'upload',
            'uploaded_by' => auth()->id(),
        ]);
    }
}
