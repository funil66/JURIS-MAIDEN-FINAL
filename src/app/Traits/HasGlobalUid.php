<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait HasGlobalUid
 * 
 * Gera um UID (Unique Identifier) global para cada registro.
 * O UID é único em TODO o sistema, não apenas na tabela.
 * 
 * Formato: [PREFIXO]-[NÚMERO_SEQUENCIAL]
 * Exemplo: CLI-10001, SRV-10002, EVT-10003
 * 
 * Uso:
 * 1. Use o trait no Model
 * 2. Implemente o método getUidPrefix() retornando o prefixo (ex: 'CLI')
 * 3. Adicione coluna 'uid' na migration
 */
trait HasGlobalUid
{
    /**
     * Boot do trait - registra o evento creating
     */
    public static function bootHasGlobalUid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = static::generateGlobalUid();
            }
        });
    }

    /**
     * Gera um UID global único usando transação com lock
     */
    public static function generateGlobalUid(): string
    {
        return DB::transaction(function () {
            // Lock para evitar race conditions
            $sequence = DB::table('global_sequences')
                ->lockForUpdate()
                ->first();
            
            if (!$sequence) {
                // Se não existir, criar com valor inicial
                DB::table('global_sequences')->insert([
                    'last_number' => 10000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $nextNumber = 10001;
            } else {
                $nextNumber = $sequence->last_number + 1;
            }
            
            // Atualizar o contador
            DB::table('global_sequences')
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);
            
            return sprintf('%s-%d', static::getUidPrefix(), $nextNumber);
        });
    }

    /**
     * Retorna o prefixo do UID para este model.
     * Pode ser sobrescrito no Model ou definido via propriedade $uidPrefix.
     */
    public static function getUidPrefix(): string
    {
        // Verifica se existe a propriedade estática $uidPrefix definida no model
        if (property_exists(static::class, 'uidPrefix')) {
            return static::$uidPrefix;
        }
        
        // Fallback: usa as 3 primeiras letras do nome da classe em maiúsculas
        $className = class_basename(static::class);
        return strtoupper(substr($className, 0, 3));
    }

    /**
     * Busca um registro pelo UID
     */
    public static function findByUid(string $uid): ?static
    {
        return static::where('uid', $uid)->first();
    }

    /**
     * Busca um registro pelo UID ou lança exceção
     */
    public static function findByUidOrFail(string $uid): static
    {
        return static::where('uid', $uid)->firstOrFail();
    }

    /**
     * Scope para buscar por UID
     */
    public function scopeWhereUid($query, string $uid)
    {
        return $query->where('uid', $uid);
    }

    /**
     * Extrai o número do UID
     */
    public function getUidNumber(): ?int
    {
        if (empty($this->uid)) {
            return null;
        }
        
        $parts = explode('-', $this->uid);
        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    /**
     * Retorna o prefixo do UID atual
     */
    public function getUidPrefixAttribute(): ?string
    {
        if (empty($this->uid)) {
            return null;
        }
        
        $parts = explode('-', $this->uid);
        return $parts[0] ?? null;
    }
}
