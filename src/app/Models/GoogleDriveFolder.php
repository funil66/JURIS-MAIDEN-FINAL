<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleDriveFolder extends Model
{
    protected $fillable = [
        'user_id',
        'google_folder_id',
        'name',
        'parent_folder_id',
        'web_view_link',
        'folderable_type',
        'folderable_id',
        'folder_type',
        'full_path',
    ];

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entidade relacionada (polymorphic)
     */
    public function folderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Arquivos nesta pasta
     */
    public function files(): HasMany
    {
        return $this->hasMany(GoogleDriveFile::class, 'google_folder_id', 'google_folder_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeRoot($query)
    {
        return $query->where('folder_type', 'root');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('folder_type', $type);
    }

    public function scopeForEntity($query, Model $entity)
    {
        return $query->where('folderable_type', get_class($entity))
                     ->where('folderable_id', $entity->id);
    }

    // ==========================================
    // CONSTANTES
    // ==========================================

    public static function getFolderTypeOptions(): array
    {
        return [
            'root' => '🏠 Pasta Raiz',
            'client' => '👤 Pasta de Cliente',
            'process' => '⚖️ Pasta de Processo',
            'documents' => '📄 Documentos',
            'reports' => '📊 Relatórios',
            'invoices' => '💰 Faturas',
            'contracts' => '📝 Contratos',
            'year' => '📅 Ano',
            'month' => '📆 Mês',
            'custom' => '📁 Customizada',
        ];
    }

    public static function getFolderTypeColors(): array
    {
        return [
            'root' => 'primary',
            'client' => 'info',
            'process' => 'warning',
            'documents' => 'success',
            'reports' => 'purple',
            'invoices' => 'yellow',
            'contracts' => 'pink',
            'year' => 'gray',
            'month' => 'gray',
            'custom' => 'secondary',
        ];
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Obtém ou cria pasta para uma entidade
     */
    public static function getOrCreateForEntity(Model $entity, string $folderType): ?self
    {
        return static::firstOrCreate([
            'folderable_type' => get_class($entity),
            'folderable_id' => $entity->id,
            'folder_type' => $folderType,
        ], [
            'user_id' => auth()->id(),
            'name' => static::generateFolderName($entity, $folderType),
        ]);
    }

    /**
     * Gera nome da pasta baseado na entidade
     */
    public static function generateFolderName(Model $entity, string $folderType): string
    {
        $entityName = match(class_basename($entity)) {
            'Client' => $entity->name ?? "Cliente {$entity->id}",
            'Process' => $entity->uid ?? "Processo {$entity->id}",
            'Contract' => $entity->uid ?? "Contrato {$entity->id}",
            'Invoice' => $entity->uid ?? "Fatura {$entity->id}",
            default => "Entidade {$entity->id}",
        };

        $prefix = match($folderType) {
            'client' => '',
            'process' => 'Processo - ',
            'documents' => 'Documentos - ',
            'reports' => 'Relatórios - ',
            'invoices' => 'Faturas - ',
            'contracts' => 'Contratos - ',
            default => '',
        };

        return $prefix . $entityName;
    }

    /**
     * Atualiza com dados do Drive
     */
    public function updateFromDrive(array $driveData): void
    {
        $this->update([
            'google_folder_id' => $driveData['id'] ?? $this->google_folder_id,
            'web_view_link' => $driveData['webViewLink'] ?? $this->web_view_link,
        ]);
    }
}
