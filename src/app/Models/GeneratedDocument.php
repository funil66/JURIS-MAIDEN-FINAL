<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Documentos
     */
    public static function getUidPrefix(): string
    {
        return 'DOC';
    }

    protected $fillable = [
        'document_template_id',
        'client_id',
        'service_id',
        'user_id',
        'title',
        'content',
        'variables_used',
        'file_path',
        'file_name',
        'file_size',
        'status',
    ];

    protected $casts = [
        'variables_used' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Template de origem
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /**
     * Cliente relacionado
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Serviço relacionado
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Usuário que gerou
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Arquivos sincronizados com Google Drive
     */
    public function driveFiles(): MorphMany
    {
        return $this->morphMany(GoogleDriveFile::class, 'fileable');
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================

    /**
     * Status disponíveis
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => '📝 Rascunho',
            'generated' => '✅ Gerado',
            'sent' => '📤 Enviado',
            'signed' => '✍️ Assinado',
            'archived' => '📦 Arquivado',
        ];
    }

    /**
     * Cores dos status
     */
    public static function getStatusColors(): array
    {
        return [
            'draft' => 'gray',
            'generated' => 'success',
            'sent' => 'info',
            'signed' => 'primary',
            'archived' => 'warning',
        ];
    }

    /**
     * Retorna tamanho do arquivo formatado
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Verifica se tem arquivo PDF gerado
     */
    public function hasPdf(): bool
    {
        return !empty($this->file_path) && file_exists(storage_path('app/' . $this->file_path));
    }

    /**
     * URL para download do PDF
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->hasPdf()) {
            return null;
        }
        
        return route('documents.download', $this->id);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
