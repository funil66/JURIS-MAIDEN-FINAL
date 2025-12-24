<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtMovement extends Model
{
    use HasFactory, HasGlobalUid;

    /**
     * Prefixo do UID para Movimentações de Tribunal
     */
    protected static string $uidPrefix = 'CMV';

    protected $fillable = [
        'uid',
        'court_id',
        'process_id',
        'court_query_id',
        'process_number',
        'movement_code',
        'description',
        'complement',
        'movement_date',
        'source',
        'proceeding_id',
        'is_imported',
        'imported_at',
        'imported_by',
        'movement_hash',
        'raw_data',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
        'imported_at' => 'datetime',
        'is_imported' => 'boolean',
        'raw_data' => 'array',
    ];

    /**
     * Sources
     */
    public const SOURCE_API = 'api';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_IMPORT = 'import';

    public const SOURCES = [
        self::SOURCE_API => 'API',
        self::SOURCE_MANUAL => 'Manual',
        self::SOURCE_IMPORT => 'Importação',
    ];

    /**
     * Boot: Gerar hash automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->movement_hash) {
                $model->movement_hash = $model->generateHash();
            }
        });
    }

    /**
     * Gerar hash único para a movimentação
     */
    public function generateHash(): string
    {
        $data = implode('|', [
            $this->process_number,
            $this->movement_code,
            $this->description,
            $this->movement_date?->format('Y-m-d H:i:s'),
        ]);

        return hash('sha256', $data);
    }

    /**
     * Relacionamento: Tribunal
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Relacionamento: Processo
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Relacionamento: Consulta
     */
    public function courtQuery(): BelongsTo
    {
        return $this->belongsTo(CourtQuery::class);
    }

    /**
     * Relacionamento: Andamento importado
     */
    public function proceeding(): BelongsTo
    {
        return $this->belongsTo(Proceeding::class);
    }

    /**
     * Relacionamento: Usuário que importou
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Accessor: Source label
     */
    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /**
     * Importar para andamentos do processo
     */
    public function importAsProceeding(?int $userId = null): ?Proceeding
    {
        if ($this->is_imported || !$this->process_id) {
            return null;
        }

        // Criar andamento
        $proceeding = Proceeding::create([
            'process_id' => $this->process_id,
            'user_id' => $userId ?? auth()->id(),
            'title' => substr($this->description, 0, 255),
            'description' => $this->description . ($this->complement ? "\n\n" . $this->complement : ''),
            'type' => 'movement',
            'source' => 'court_api',
            'occurrence_date' => $this->movement_date,
            'is_public' => true,
            'metadata' => [
                'court_movement_id' => $this->id,
                'movement_code' => $this->movement_code,
                'court_id' => $this->court_id,
            ],
        ]);

        // Marcar como importado
        $this->update([
            'proceeding_id' => $proceeding->id,
            'is_imported' => true,
            'imported_at' => now(),
            'imported_by' => $userId ?? auth()->id(),
        ]);

        return $proceeding;
    }

    /**
     * Verificar se já existe uma movimentação igual
     */
    public static function existsByHash(string $hash): bool
    {
        return static::where('movement_hash', $hash)->exists();
    }

    /**
     * Scope: Não importadas
     */
    public function scopeNotImported($query)
    {
        return $query->where('is_imported', false);
    }

    /**
     * Scope: Importadas
     */
    public function scopeImported($query)
    {
        return $query->where('is_imported', true);
    }

    /**
     * Scope: Por processo
     */
    public function scopeForProcess($query, int $processId)
    {
        return $query->where('process_id', $processId);
    }

    /**
     * Scope: Por número de processo
     */
    public function scopeForProcessNumber($query, string $processNumber)
    {
        return $query->where('process_number', $processNumber);
    }

    /**
     * Scope: Recentes primeiro
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('movement_date');
    }
}
