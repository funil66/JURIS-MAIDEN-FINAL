<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class GeneratedReport extends Model
{
    use SoftDeletes, HasGlobalUid;

    protected $fillable = [
        'uid',
        'user_id',
        'report_template_id',
        'name',
        'type',
        'date_from',
        'date_to',
        'filters_applied',
        'format',
        'file_path',
        'file_name',
        'file_size',
        'records_count',
        'execution_time',
        'status',
        'error_message',
        'download_count',
        'last_downloaded_at',
        'expires_at',
    ];

    protected $casts = [
        'filters_applied' => 'array',
        'date_from' => 'date',
        'date_to' => 'date',
        'execution_time' => 'float',
        'last_downloaded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function getUidPrefix(): string
    {
        return 'GRP';
    }

    // === CONSTANTES ===
    
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    // === RELATIONSHIPS ===

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    /**
     * Arquivos sincronizados com Google Drive
     */
    public function driveFiles(): MorphMany
    {
        return $this->morphMany(GoogleDriveFile::class, 'fileable');
    }

    // === ACCESSORS ===

    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_GENERATING => 'Gerando',
            self::STATUS_COMPLETED => 'Concluído',
            self::STATUS_FAILED => 'Falhou',
            self::STATUS_EXPIRED => 'Expirado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_GENERATING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    public function getFormatIconAttribute(): string
    {
        return match($this->format) {
            'pdf' => 'heroicon-o-document-text',
            'excel' => 'heroicon-o-table-cells',
            'csv' => 'heroicon-o-document-chart-bar',
            default => 'heroicon-o-document',
        };
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getExecutionTimeFormattedAttribute(): string
    {
        if (!$this->execution_time) {
            return '-';
        }

        if ($this->execution_time < 1) {
            return round($this->execution_time * 1000) . 'ms';
        }

        return round($this->execution_time, 2) . 's';
    }

    public function getPeriodDescriptionAttribute(): string
    {
        if (!$this->date_from && !$this->date_to) {
            return 'Todos os registros';
        }

        if ($this->date_from && $this->date_to) {
            return $this->date_from->format('d/m/Y') . ' a ' . $this->date_to->format('d/m/Y');
        }

        if ($this->date_from) {
            return 'A partir de ' . $this->date_from->format('d/m/Y');
        }

        return 'Até ' . $this->date_to->format('d/m/Y');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // === SCOPES ===

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // === METHODS ===

    public function markAsCompleted(string $filePath, string $fileName, int $recordsCount, float $executionTime): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::exists($filePath) ? Storage::size($filePath) : null,
            'records_count' => $recordsCount,
            'execution_time' => $executionTime,
            'expires_at' => now()->addDays(7), // Expira em 7 dias
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function incrementDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    public function getDownloadUrl(): ?string
    {
        if (!$this->file_path || !Storage::exists($this->file_path)) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function download()
    {
        if (!$this->file_path || !Storage::exists($this->file_path)) {
            return null;
        }

        $this->incrementDownload();

        return Storage::download($this->file_path, $this->file_name);
    }

    /**
     * Limpa relatórios expirados
     */
    public static function cleanupExpired(): int
    {
        $expired = self::where('status', self::STATUS_COMPLETED)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $report) {
            // Remove arquivo
            if ($report->file_path && Storage::exists($report->file_path)) {
                Storage::delete($report->file_path);
            }

            // Atualiza status
            $report->update(['status' => self::STATUS_EXPIRED]);
            $count++;
        }

        return $count;
    }
}
