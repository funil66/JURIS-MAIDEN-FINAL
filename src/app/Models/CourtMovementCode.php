<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtMovementCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'code',
        'name',
        'description',
        'category',
        'is_deadline_trigger',
        'deadline_days',
        'deadline_type',
        'is_favorable',
        'is_unfavorable',
        'importance_level',
        'action_required',
        'notification_template',
        'is_active',
    ];

    protected $casts = [
        'is_deadline_trigger' => 'boolean',
        'deadline_days' => 'integer',
        'is_favorable' => 'boolean',
        'is_unfavorable' => 'boolean',
        'importance_level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Categorias de movimentações
     */
    public const CATEGORY_PETITION = 'petition';
    public const CATEGORY_DECISION = 'decision';
    public const CATEGORY_SENTENCE = 'sentence';
    public const CATEGORY_HEARING = 'hearing';
    public const CATEGORY_CITATION = 'citation';
    public const CATEGORY_INTIMATION = 'intimation';
    public const CATEGORY_PUBLICATION = 'publication';
    public const CATEGORY_CERTIFICATE = 'certificate';
    public const CATEGORY_ATTACHMENT = 'attachment';
    public const CATEGORY_DISTRIBUTION = 'distribution';
    public const CATEGORY_CONCLUSION = 'conclusion';
    public const CATEGORY_REMESSA = 'remessa';
    public const CATEGORY_RETURN = 'return';
    public const CATEGORY_ARCHIVE = 'archive';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_PETITION => 'Petição',
        self::CATEGORY_DECISION => 'Decisão',
        self::CATEGORY_SENTENCE => 'Sentença',
        self::CATEGORY_HEARING => 'Audiência',
        self::CATEGORY_CITATION => 'Citação',
        self::CATEGORY_INTIMATION => 'Intimação',
        self::CATEGORY_PUBLICATION => 'Publicação',
        self::CATEGORY_CERTIFICATE => 'Certidão',
        self::CATEGORY_ATTACHMENT => 'Juntada',
        self::CATEGORY_DISTRIBUTION => 'Distribuição',
        self::CATEGORY_CONCLUSION => 'Conclusão',
        self::CATEGORY_REMESSA => 'Remessa',
        self::CATEGORY_RETURN => 'Retorno',
        self::CATEGORY_ARCHIVE => 'Arquivamento',
        self::CATEGORY_OTHER => 'Outros',
    ];

    /**
     * Tipos de prazo
     */
    public const DEADLINE_CALENDAR = 'calendar';
    public const DEADLINE_BUSINESS = 'business';

    public const DEADLINE_TYPES = [
        self::DEADLINE_CALENDAR => 'Dias Corridos',
        self::DEADLINE_BUSINESS => 'Dias Úteis',
    ];

    /**
     * Níveis de importância
     */
    public const IMPORTANCE_LEVELS = [
        1 => 'Baixa',
        2 => 'Normal',
        3 => 'Alta',
        4 => 'Urgente',
        5 => 'Crítica',
    ];

    /**
     * Relacionamento: Tribunal
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Relacionamento: Movimentações
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CourtMovement::class);
    }

    /**
     * Accessor: Label da categoria
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Accessor: Label do tipo de prazo
     */
    public function getDeadlineTypeLabelAttribute(): ?string
    {
        if (!$this->deadline_type) {
            return null;
        }

        return self::DEADLINE_TYPES[$this->deadline_type] ?? $this->deadline_type;
    }

    /**
     * Accessor: Label do nível de importância
     */
    public function getImportanceLevelLabelAttribute(): string
    {
        return self::IMPORTANCE_LEVELS[$this->importance_level] ?? 'Normal';
    }

    /**
     * Accessor: Cor por nível de importância
     */
    public function getImportanceColorAttribute(): string
    {
        return match ($this->importance_level) {
            1 => 'gray',
            2 => 'info',
            3 => 'warning',
            4 => 'danger',
            5 => 'danger',
            default => 'gray',
        };
    }

    /**
     * Buscar código por código externo
     */
    public static function findByCode(string $code, ?int $courtId = null): ?static
    {
        $query = static::where('code', $code);

        if ($courtId) {
            $query->where('court_id', $courtId);
        }

        return $query->first();
    }

    /**
     * Buscar ou criar código
     */
    public static function findOrCreateFromApi(
        string $code,
        string $name,
        ?int $courtId = null,
        ?string $category = null
    ): static {
        return static::firstOrCreate(
            [
                'code' => $code,
                'court_id' => $courtId,
            ],
            [
                'name' => $name,
                'category' => $category ?? self::CATEGORY_OTHER,
                'importance_level' => 2,
                'is_active' => true,
            ]
        );
    }

    /**
     * Scope: Ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Que geram prazo
     */
    public function scopeDeadlineTriggers($query)
    {
        return $query->where('is_deadline_trigger', true);
    }

    /**
     * Scope: Por categoria
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Por importância mínima
     */
    public function scopeMinImportance($query, int $level)
    {
        return $query->where('importance_level', '>=', $level);
    }

    /**
     * Scope: Favoráveis
     */
    public function scopeFavorable($query)
    {
        return $query->where('is_favorable', true);
    }

    /**
     * Scope: Desfavoráveis
     */
    public function scopeUnfavorable($query)
    {
        return $query->where('is_unfavorable', true);
    }
}
