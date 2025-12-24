# 🚀 PLANO DE EVOLUÇÃO - LogísticaJus

## Documento de Análise e Planejamento Estratégico
**Data:** 24 de Dezembro de 2025  
**Versão:** 2.0 - IMPLEMENTAÇÃO COMPLETA ✅

---

## 🎉 STATUS: 100% IMPLEMENTADO

Todos os 14 sprints planejados (19-32) foram implementados com sucesso!

### Commits Finais:
- **Sprint 31** (Assinatura Digital): `77b7c0a` - 25 arquivos, 4.776 inserções
- **Sprint 32** (API de Tribunais): `b50e2f5` - 20 arquivos, 4.148 inserções

---

## 📋 SUMÁRIO EXECUTIVO

Após análise detalhada dos documentos de projetos anteriores e do estado atual do LogísticaJus, este documento consolida as melhores ideias e funcionalidades a serem implementadas, com foco especial em:

1. **Sistema de Identificação Única Global (UID)** - Cada registro do sistema terá um código único irrepetível
2. **Estrutura Hierárquica de Processos** - Processos, subprocessos e diligências vinculadas
3. **Módulos Avançados** - Funcionalidades extraídas dos documentos de referência

---

## 🔢 SISTEMA DE IDENTIFICAÇÃO ÚNICA GLOBAL (UID)

### Problema Atual
O sistema atual gera códigos separados por entidade:
- `SRV-2025-0001` para Serviços
- `TRX-2025-0001` para Transações

**Problema:** Códigos como `0001` podem repetir entre entidades, causando confusão.

### Solução Proposta: Tabela Centralizada de Sequência

```
┌─────────────────────────────────────────────────────────────────┐
│                    TABELA: global_sequences                      │
├─────────────────────────────────────────────────────────────────┤
│ id │ last_number │ updated_at                                   │
│  1 │     15847   │ 2025-12-23 10:30:00                          │
└─────────────────────────────────────────────────────────────────┘
```

### Formato do UID Global

```
[PREFIXO]-[NÚMERO_GLOBAL]

Exemplos:
- CLI-10001  → Cliente #10001
- SRV-10002  → Serviço #10002
- EVT-10003  → Evento #10003
- TRX-10004  → Transação #10004
- DOC-10005  → Documento #10005
- PRC-10006  → Processo #10006 (NOVO)
- DLG-10007  → Diligência #10007 (NOVO)
- AND-10008  → Andamento #10008 (NOVO)
```

### Prefixos por Entidade

| Entidade | Prefixo | Descrição |
|----------|---------|-----------|
| Client | CLI | Clientes (PF/PJ) |
| Service | SRV | Serviços de diligência |
| Event | EVT | Compromissos/Agenda |
| Transaction | TRX | Movimentações financeiras |
| DocumentTemplate | TPL | Templates de documentos |
| GeneratedDocument | DOC | Documentos gerados |
| Process | PRC | Processos judiciais (NOVO) |
| Subprocess | SUB | Subprocessos vinculados (NOVO) |
| Diligence | DLG | Diligências avulsas (NOVO) |
| Proceeding | AND | Andamentos processuais (NOVO) |
| Payment | PAG | Pagamentos recebidos (NOVO) |
| Expense | DSP | Despesas operacionais (NOVO) |
| User | USR | Usuários do sistema |
| ServiceType | TPS | Tipos de serviço |
| PaymentMethod | MPG | Métodos de pagamento |

### Implementação Técnica

#### Migration: global_sequences
```php
Schema::create('global_sequences', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('last_number')->default(10000);
    $table->timestamps();
});

// Inserir registro inicial
DB::table('global_sequences')->insert(['last_number' => 10000]);
```

#### Trait: HasGlobalUid
```php
trait HasGlobalUid
{
    protected static function bootHasGlobalUid()
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = static::generateGlobalUid();
            }
        });
    }

    public static function generateGlobalUid(): string
    {
        return DB::transaction(function () {
            $sequence = DB::table('global_sequences')
                ->lockForUpdate()
                ->first();
            
            $nextNumber = $sequence->last_number + 1;
            
            DB::table('global_sequences')
                ->update(['last_number' => $nextNumber]);
            
            return sprintf('%s-%d', static::getUidPrefix(), $nextNumber);
        });
    }

    abstract public static function getUidPrefix(): string;
}
```

---

## 📁 ESTRUTURA DE PROCESSOS E DILIGÊNCIAS

### Modelo Hierárquico Proposto

```
PROCESSO (PRC-10050)
├── Dados do processo judicial
├── Número CNJ, Comarca, Vara
├── Partes (autor/réu)
│
├── SUBPROCESSOS (vinculados)
│   ├── SUB-10051 - Recurso de Apelação
│   └── SUB-10052 - Embargos de Declaração
│
├── ANDAMENTOS (histórico)
│   ├── AND-10053 - Distribuição
│   ├── AND-10054 - Citação
│   └── AND-10055 - Audiência realizada
│
├── DILIGÊNCIAS (serviços vinculados)
│   ├── DLG-10056 - Citação pessoal
│   └── DLG-10057 - Audiência de instrução
│
└── DOCUMENTOS
    ├── DOC-10058 - Petição inicial
    └── DOC-10059 - Certidão de citação
```

### Novas Tabelas Necessárias

#### 1. processes (Processos Judiciais)
```php
Schema::create('processes', function (Blueprint $table) {
    $table->id();
    $table->string('uid', 20)->unique();
    $table->foreignId('client_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('processes')->nullOnDelete();
    
    // Identificação do processo
    $table->string('cnj_number', 25)->nullable()->unique(); // 0000000-00.0000.0.00.0000
    $table->string('old_number', 50)->nullable(); // Numeração antiga
    $table->string('title');
    
    // Localização
    $table->string('court')->nullable(); // Tribunal (TJSP, TRT, etc)
    $table->string('jurisdiction')->nullable(); // Comarca
    $table->string('court_division')->nullable(); // Vara
    $table->string('state', 2)->nullable();
    
    // Partes
    $table->string('plaintiff')->nullable(); // Autor/Requerente
    $table->string('defendant')->nullable(); // Réu/Requerido
    $table->enum('client_role', ['plaintiff', 'defendant', 'third_party', 'other'])->default('plaintiff');
    
    // Classificação
    $table->string('matter_type')->nullable(); // Área do direito
    $table->string('action_type')->nullable(); // Tipo de ação
    $table->string('procedure_type')->nullable(); // Rito processual
    
    // Datas
    $table->date('distribution_date')->nullable();
    $table->date('filing_date')->nullable();
    $table->date('closing_date')->nullable();
    
    // Valores
    $table->decimal('case_value', 15, 2)->nullable();
    $table->decimal('contingency_value', 15, 2)->nullable();
    
    // Status
    $table->enum('status', [
        'active',        // Em andamento
        'suspended',     // Suspenso
        'archived',      // Arquivado
        'closed_won',    // Encerrado - Ganho
        'closed_lost',   // Encerrado - Perdido
        'closed_settled' // Encerrado - Acordo
    ])->default('active');
    
    $table->enum('phase', [
        'knowledge',      // Conhecimento
        'execution',      // Execução
        'appeal',         // Recursal
        'precautionary'   // Cautelar
    ])->default('knowledge');
    
    // Responsáveis
    $table->foreignId('responsible_user_id')->nullable()->constrained('users');
    $table->string('external_lawyer')->nullable();
    $table->string('external_lawyer_oab')->nullable();
    
    // Observações
    $table->text('strategy')->nullable(); // Estratégia do caso
    $table->text('notes')->nullable();
    
    $table->boolean('is_urgent')->default(false);
    $table->boolean('is_confidential')->default(false);
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['status', 'client_id']);
    $table->index('cnj_number');
});
```

#### 2. proceedings (Andamentos Processuais)
```php
Schema::create('proceedings', function (Blueprint $table) {
    $table->id();
    $table->string('uid', 20)->unique();
    $table->foreignId('process_id')->constrained()->cascadeOnDelete();
    
    $table->datetime('occurred_at');
    $table->string('title');
    $table->text('description')->nullable();
    
    $table->enum('type', [
        'distribution',    // Distribuição
        'citation',        // Citação
        'subpoena',        // Intimação
        'hearing',         // Audiência
        'decision',        // Decisão
        'sentence',        // Sentença
        'appeal',          // Recurso
        'transit',         // Trânsito em julgado
        'other'
    ])->default('other');
    
    $table->enum('source', [
        'manual',          // Inserido manualmente
        'tribunal',        // Capturado do tribunal
        'push_notification' // Push do tribunal
    ])->default('manual');
    
    $table->string('external_id')->nullable(); // ID do tribunal
    $table->boolean('is_deadline')->default(false);
    $table->date('deadline_date')->nullable();
    $table->boolean('deadline_completed')->default(false);
    
    $table->foreignId('created_by_user_id')->nullable()->constrained('users');
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['process_id', 'occurred_at']);
});
```

#### 3. diligences (Diligências)
```php
Schema::create('diligences', function (Blueprint $table) {
    $table->id();
    $table->string('uid', 20)->unique();
    
    // Vínculos (pode ser vinculado a processo OU ser avulsa para cliente)
    $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('client_id')->constrained()->cascadeOnDelete();
    $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete(); // Serviço de diligência
    
    $table->string('title');
    $table->text('description')->nullable();
    
    $table->enum('type', [
        'citation',        // Citação
        'subpoena',        // Intimação
        'hearing',         // Audiência
        'protocol',        // Protocolo
        'copy_extraction', // Extração de cópias
        'research',        // Pesquisa
        'meeting',         // Reunião
        'travel',          // Viagem
        'other'
    ]);
    
    // Localização
    $table->string('location_name')->nullable();
    $table->string('location_address')->nullable();
    $table->string('location_city')->nullable();
    $table->string('location_state', 2)->nullable();
    
    // Datas
    $table->datetime('scheduled_at')->nullable();
    $table->datetime('completed_at')->nullable();
    $table->date('deadline')->nullable();
    
    // Responsável
    $table->foreignId('assigned_user_id')->nullable()->constrained('users');
    
    // Status
    $table->enum('status', [
        'pending',
        'in_progress',
        'completed',
        'cancelled',
        'rescheduled'
    ])->default('pending');
    
    // Resultado
    $table->enum('result', [
        'positive',    // Positivo/Cumprida
        'negative',    // Negativo/Não cumprida
        'partial',     // Parcial
        'rescheduled', // Reagendada
        'cancelled'    // Cancelada
    ])->nullable();
    
    $table->text('result_notes')->nullable();
    
    // Custos
    $table->decimal('estimated_cost', 10, 2)->nullable();
    $table->decimal('actual_cost', 10, 2)->nullable();
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['client_id', 'status']);
    $table->index(['process_id', 'status']);
});
```

---

## 🎯 MÓDULOS IMPLEMENTADOS ✅

### Fase 1: Core Enhancement (Sprints 19-22) ✅

| Sprint | Módulo | Descrição | Status |
|--------|--------|-----------|--------|
| 19 | Sistema UID Global | Implementar tabela de sequência e Trait | ✅ |
| 20 | Módulo Processos | CRUD de processos judiciais | ✅ |
| 21 | Módulo Andamentos | Histórico processual com timeline | ✅ |
| 22 | Módulo Diligências | Gestão de diligências vinculadas | ✅ |

### Fase 2: Financeiro Avançado (Sprints 23-25) ✅

| Sprint | Módulo | Descrição | Status |
|--------|--------|-----------|--------|
| 23 | Time Tracking | Registro de horas por atividade | ✅ |
| 24 | Contratos/Honorários | Gestão de contratos com clientes | ✅ |
| 25 | Faturamento Automático | Geração de faturas baseadas em horas/serviços | ✅ |

### Fase 3: Jurimetria e IA (Sprints 26-28) ✅

| Sprint | Módulo | Descrição | Status |
|--------|--------|-----------|--------|
| 26 | Dashboard Jurídico | KPIs específicos para advocacia | ✅ |
| 27 | Análise de Prazos | Alertas inteligentes de deadlines | ✅ |
| 28 | Relatórios Avançados | Relatórios customizáveis com gráficos | ✅ |

### Fase 4: Integrações (Sprints 29-32) ✅

| Sprint | Módulo | Descrição | Status |
|--------|--------|-----------|--------|
| 29 | Google Drive | Armazenamento de documentos na nuvem | ✅ |
| 30 | Feriados | Gestão de feriados para cálculo de prazos | ✅ |
| 31 | Assinatura Digital | Integração com certificado digital | ✅ |
| 32 | API de Tribunais | Consulta automática de andamentos | ✅ |

---

## 📊 COMPARATIVO: ESTADO ATUAL vs PROPOSTO

### Entidades Atuais

| Entidade | Tem UID? | Formato Atual |
|----------|----------|---------------|
| Client | ❌ | Sem código |
| Service | ✅ | SRV-YYYY-NNNN |
| Event | ❌ | Sem código |
| Transaction | ✅ | TRX-YYYY-NNNN |
| DocumentTemplate | ✅ | TPL-YYYY-NNNN |
| GeneratedDocument | ✅ | DOC-XXXXXXXX |
| User | ❌ | Sem código |
| ServiceType | ❌ | Sem código |
| PaymentMethod | ❌ | Sem código |

### Entidades Propostas (Todas com UID Global)

| Entidade | Prefixo | Novo? |
|----------|---------|-------|
| Client | CLI | Migração |
| Service | SRV | Migração |
| Event | EVT | Migração |
| Transaction | TRX | Migração |
| DocumentTemplate | TPL | Migração |
| GeneratedDocument | DOC | Migração |
| Process | PRC | ✅ NOVO |
| Proceeding | AND | ✅ NOVO |
| Diligence | DLG | ✅ NOVO |
| Contract | CTR | ✅ NOVO (Fase 2) |
| TimeEntry | TIM | ✅ NOVO (Fase 2) |
| Invoice | FAT | ✅ NOVO (Fase 2) |

---

## 🔄 PLANO DE MIGRAÇÃO

### Etapa 1: Preparação
1. Criar tabela `global_sequences` com valor inicial 10000
2. Criar Trait `HasGlobalUid`
3. Adicionar coluna `uid` em todas as tabelas existentes (nullable inicialmente)

### Etapa 2: Migração de Dados Existentes
1. Para cada registro existente, gerar UID global sequencial
2. Manter códigos antigos em coluna `legacy_code` para referência
3. Tornar coluna `uid` not-nullable e unique

### Etapa 3: Atualização de Referências
1. Atualizar todas as views/pages do Filament para mostrar UID
2. Atualizar relatórios
3. Atualizar integrações (WhatsApp, Google Calendar)

---

## ✅ IMPLEMENTAÇÃO CONCLUÍDA

### Arquivos Implementados por Sprint

**Sprint 19-22 (Core):**
- Trait `HasGlobalUid`, migration `global_sequences`
- Models: Process (PRC), Proceeding (AND), Diligence (DLG)
- Resources: ProcessResource, ProceedingResource, DiligenceResource

**Sprint 23-25 (Financeiro):**
- Models: TimeEntry (TIM), Contract (CTR), Invoice (FAT)
- Resources: TimeEntryResource, ContractResource, InvoiceResource
- Automação de faturamento baseada em horas

**Sprint 26-28 (Jurimetria):**
- Dashboard Jurídico com 8+ widgets
- Models: Deadline (PRZ), Holiday, DeadlineType
- Resources: DeadlineResource, HolidayResource
- Relatórios: ReportTemplate (RPT), GeneratedReport (GRP)

**Sprint 29-30 (Integrações Base):**
- Google Drive: GoogleDriveFile (GDF), GoogleDriveService
- Feriados: Holiday model completo com recorrência
- Comando: `php artisan drive:sync`

**Sprint 31 (Assinatura Digital):**
- Models: DigitalCertificate (CRT), SignatureRequest (SIG), SignatureSigner (SGN), SignatureTemplate (STM)
- DigitalSignatureService com validação de certificados
- Views públicas: /assinar/{token}
- Comando: `php artisan signatures:update-status`

**Sprint 32 (API Tribunais):**
- Models: Court (TRB), CourtQuery (CQY), CourtMovement (CMV)
- CourtApiService: DataJud, PJe, e-SAJ, Projudi, e-Proc
- Comando: `php artisan courts:sync`

### Comandos Artisan Disponíveis

```bash
# Processar prazos (verificar vencidos, alertas)
php artisan deadlines:process

# Atualizar status de assinaturas
php artisan signatures:update-status

# Sincronizar tribunais
php artisan courts:sync --scheduled
php artisan courts:sync --court=TJSP
php artisan courts:sync --all

# Sincronizar Google Drive
php artisan drive:sync
```

---

*Documento atualizado em 24/12/2025 - LogísticaJus v2.0 - IMPLEMENTAÇÃO COMPLETA ✅*

---

# LogísticaJus
├── 📂 Cadastros
│   ├── Clientes (CLI)
│   ├── Serviços (SRV)
│   └── Tribunais (TRB) ← NOVO
│
├── 📂 Jurídico
│   ├── Processos (PRC)
│   ├── Andamentos (AND)
│   ├── Prazos (PRZ)
│   └── Movimentações API (CMV) ← NOVO
│
├── 📂 Operacional
│   ├── Diligências (DLG)
│   └── Lançamentos de Tempo (TIM)
│
├── 📂 Financeiro
│   ├── Contratos (CTR)
│   └── Faturas (FAT)
│
├── 📂 Relatórios
│   ├── Templates (RPT)
│   └── Relatórios Gerados (GRP)
│
├── 📂 Assinaturas ← SPRINT 31
│   ├── Solicitações (SIG)
│   └── Certificados (CRT)
│
└── 📂 Configurações
    ├── Google Drive (GDF)
    └── Feriados (HOL)
