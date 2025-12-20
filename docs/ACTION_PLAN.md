# 🎯 PLANO DE AÇÃO - JURIS-MAIDEN-FINAL

> **Versão:** 1.0  
> **Data:** 20/12/2025  
> **Status:** Em execução

---

## 📊 VISÃO GERAL

```
┌─────────────────────────────────────────────────────────────────┐
│                      ROADMAP DO PROJETO                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  FASE 1: Estabilização          ████████████░░░░  80%           │
│  └── Sprint 11 (20/12/2025)                                     │
│      ├── ✅ Correção de bugs                                    │
│      ├── ✅ Documentação                                        │
│      ├── 🔄 GitHub                                              │
│      └── 🔲 Testes e Seed                                       │
│                                                                 │
│  FASE 2: Melhorias              ░░░░░░░░░░░░░░░░   0%           │
│  └── Sprints 12-14 (21-27/12/2025)                              │
│      ├── 🔲 Campos OAB no User                                  │
│      ├── 🔲 Modelo Process expandido                            │
│      ├── 🔲 Templates de Documentos                             │
│      └── 🔲 Áreas do Direito                                    │
│                                                                 │
│  FASE 3: Integrações            ░░░░░░░░░░░░░░░░   0%           │
│  └── Sprints 15-18 (Jan/2026)                                   │
│      ├── 🔲 Google Calendar                                     │
│      ├── 🔲 Portal do Cliente                                   │
│      └── 🔲 WhatsApp API                                        │
│                                                                 │
│  FASE 4: Avançado               ░░░░░░░░░░░░░░░░   0%           │
│  └── Sprints 19+ (Fev/2026+)                                    │
│      ├── 🔲 Jurimetria                                          │
│      ├── 🔲 Assinatura Digital                                  │
│      └── 🔲 Multi-tenancy                                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🚨 RISCOS E MITIGAÇÕES

### Risco #1: Over-engineering (PRINCIPAL - causou abandono anteriores)
| Campo | Valor |
|-------|-------|
| **Probabilidade** | 🔴 Alta (histórico) |
| **Impacto** | 🔴 Crítico (abandono) |
| **Mitigação** | Sprints pequenos, MVP primeiro, documentar antes de implementar |
| **Indicadores** | Mais de 3 features em paralelo, código não testado acumulando |

### Risco #2: Falta de documentação
| Campo | Valor |
|-------|-------|
| **Probabilidade** | 🟡 Média |
| **Impacto** | 🔴 Crítico (projeto incompreensível) |
| **Mitigação** | Atualizar IMPLEMENTATION_LOG.md a cada sessão |
| **Indicadores** | Última atualização > 3 dias |

### Risco #3: Dependências desatualizadas
| Campo | Valor |
|-------|-------|
| **Probabilidade** | 🟡 Média |
| **Impacto** | 🟡 Médio |
| **Mitigação** | `composer update` mensal, verificar changelogs |
| **Indicadores** | Security warnings do GitHub |

### Risco #4: Falta de testes
| Campo | Valor |
|-------|-------|
| **Probabilidade** | 🔴 Alta (não implementados) |
| **Impacto** | 🟡 Médio (bugs em produção) |
| **Mitigação** | Adicionar testes nas próximas fases |
| **Indicadores** | Bugs recorrentes após deploys |

### Risco #5: Complexidade de integrações externas
| Campo | Valor |
|-------|-------|
| **Probabilidade** | 🟡 Média |
| **Impacto** | 🟡 Médio (features não funcionando) |
| **Mitigação** | POC antes de integrar, fallbacks |
| **Indicadores** | APIs externas indisponíveis |

---

## 📋 FASE 1: ESTABILIZAÇÃO (Sprint 11)

### Objetivo
Garantir que o MVP funciona 100% antes de adicionar novas features.

### Tarefas Detalhadas

#### 1.1 ✅ Correção: service_date → scheduled_datetime
- **Status:** Concluído
- **Arquivos:** 8 arquivos corrigidos
- **Verificação:** Query testada e funcionando

#### 1.2 ✅ Correção: Livewire assets 404
- **Status:** Concluído
- **Solução:** Assets publicados + config
- **Verificação:** Login funcionando

#### 1.3 🔄 Configurar repositório GitHub
- **Status:** Em andamento
- **Subtarefas:**
  ```bash
  # 1. Inicializar git
  cd "/home/funil/Área de trabalho/VISUAL CODE/PROJETO 1/logisticajus"
  git init
  
  # 2. Criar .gitignore adequado
  # (já existe, verificar se está completo)
  
  # 3. Adicionar remote
  git remote add origin https://github.com/funil66/JURIS-MAIDEN-FINAL.git
  
  # 4. Primeiro commit
  git add .
  git commit -m "🚀 Initial commit - LogísticaJus MVP"
  
  # 5. Push
  git push -u origin main
  ```

#### 1.4 ✅ Criar documentação
- **Status:** Concluído
- **Arquivos criados:**
  - docs/IMPLEMENTATION_LOG.md
  - docs/ACTION_PLAN.md (este arquivo)

#### 1.5 🔲 Testar todos os CRUDs
- **Status:** Pendente
- **Checklist:**
  - [ ] Clients: Create, Read, Update, Delete, Restore
  - [ ] Services: Create, Read, Update, Delete, Restore
  - [ ] ServiceTypes: Create, Read, Update, Delete
  - [ ] Events: Create, Read, Update, Delete
  - [ ] Transactions: Create, Read, Update, Delete
  - [ ] PaymentMethods: Create, Read, Update, Delete

#### 1.6 🔲 Testar relatórios
- **Status:** Pendente
- **Checklist:**
  - [ ] Relatório de Serviços (PDF)
  - [ ] Relatório de Clientes (PDF)
  - [ ] Relatório Financeiro (PDF)
  - [ ] Relatório Geral (PDF)
  - [ ] Export Excel
  - [ ] Export CSV

#### 1.7 🔲 Testar calendário
- **Status:** Pendente
- **Checklist:**
  - [ ] Visualização mensal
  - [ ] Visualização semanal
  - [ ] Criar evento pelo calendário
  - [ ] Editar evento pelo calendário
  - [ ] Cores por tipo de evento

#### 1.8 🔲 Criar seed de dados de teste
- **Status:** Pendente
- **Dados a criar:**
  - 10 clientes (5 PF, 5 PJ)
  - 20 serviços variados
  - 15 eventos no calendário
  - 30 transações (receitas e despesas)

---

## 📋 FASE 2: MELHORIAS (Sprints 12-14)

### Sprint 12: Campos OAB e Trait HasUuid

#### 2.1 Migration: Campos OAB no User
```php
// database/migrations/xxxx_add_oab_fields_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->string('oab', 20)->nullable()->after('email');
    $table->string('oab_uf', 2)->nullable()->after('oab');
    $table->json('specialties')->nullable()->after('oab_uf'); // Áreas de atuação
    $table->string('phone', 20)->nullable()->after('specialties');
    $table->text('bio')->nullable()->after('phone');
});
```

#### 2.2 Criar Trait HasUuid
```php
// app/Traits/HasUuid.php
namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
```

### Sprint 13: Expandir modelo Service

#### 2.3 Migration: Campos de processo no Service
```php
// Já existe process_number, court, jurisdiction, state
// Adicionar:
Schema::table('services', function (Blueprint $table) {
    $table->string('judge_name')->nullable()->after('jurisdiction');
    $table->string('court_section')->nullable()->after('judge_name'); // Vara
    $table->string('plaintiff_lawyer')->nullable()->after('plaintiff');
    $table->string('defendant_lawyer')->nullable()->after('defendant');
    $table->string('action_type')->nullable()->after('defendant_lawyer'); // Tipo de ação
    $table->foreignId('legal_area_id')->nullable()->after('action_type');
});
```

#### 2.4 Criar modelo LegalArea (Áreas do Direito)
```php
// app/Models/LegalArea.php
// Campos: id, name, slug, description, icon, color, is_active

// Seeder com áreas:
// - Direito Civil
// - Direito Trabalhista
// - Direito Criminal
// - Direito Previdenciário
// - Direito Tributário
// - Direito do Consumidor
// - Direito de Família
// - Direito Empresarial
// - Direito Administrativo
// - Direito Ambiental
```

### Sprint 14: Templates de Documentos

#### 2.5 Criar modelo Template
```php
// app/Models/Template.php
// Campos: id, name, category, content (texto com variáveis), variables (json), is_active

// Variáveis suportadas:
// {{cliente.nome}}, {{cliente.documento}}, {{cliente.endereco}}
// {{servico.codigo}}, {{servico.tipo}}, {{servico.data}}
// {{processo.numero}}, {{processo.tribunal}}, {{processo.vara}}
// {{usuario.nome}}, {{usuario.oab}}, {{data.atual}}
```

#### 2.6 Criar modelo GeneratedDocument
```php
// app/Models/GeneratedDocument.php
// Campos: id, template_id, service_id, client_id, generated_by, content, file_path
```

#### 2.7 TemplateResource com editor
- CRUD de templates
- Editor com preview de variáveis
- Geração de PDF a partir do template

---

## 📋 FASE 3: INTEGRAÇÕES (Sprints 15-18)

### Sprint 15-16: Google Calendar

#### Passos:
1. Criar projeto no Google Cloud Console
2. Habilitar Calendar API
3. Configurar OAuth 2.0
4. Instalar `google/apiclient`
5. Criar GoogleCalendarService
6. Sincronização bidirecional de eventos

#### Campos adicionais necessários:
```php
Schema::table('users', function (Blueprint $table) {
    $table->json('google_tokens')->nullable();
    $table->string('google_calendar_id')->nullable();
});
```

### Sprint 17: Portal do Cliente

#### Estrutura:
- Novo painel Filament: `/cliente`
- Autenticação separada (clients como usuários)
- Visualização de serviços do cliente
- Histórico de transações
- Documentos gerados
- Chat/mensagens (opcional)

#### Campos adicionais:
```php
Schema::table('clients', function (Blueprint $table) {
    $table->string('portal_password')->nullable();
    $table->boolean('portal_enabled')->default(false);
    $table->timestamp('last_portal_login')->nullable();
});
```

### Sprint 18: WhatsApp API

#### Opções:
1. **Twilio WhatsApp** (pago, mais fácil)
2. **WhatsApp Business API** (oficial, mais complexo)
3. **Baileys/Venom** (não oficial, gratuito)

#### Implementação:
- Notificações via WhatsApp
- Lembretes de serviços
- Lembretes de pagamentos
- Confirmação de audiências

---

## 📋 FASE 4: AVANÇADO (Sprints 19+)

### Jurimetria
- Estatísticas de resultados por tipo de ação
- Tempo médio por tipo de serviço
- Taxa de sucesso por comarca/tribunal
- Dashboards analíticos

### Assinatura Digital
- Integração com certificado A1/A3
- Assinatura de documentos no sistema
- Validação de assinaturas
- Carimbo de tempo

### Multi-tenancy (SaaS)
- Separação por tenant (escritórios)
- Planos e assinaturas
- Billing integrado
- White-label

---

## 📈 MÉTRICAS DE SUCESSO

### Fase 1
- [ ] 0 erros no console
- [ ] Todos os CRUDs funcionando
- [ ] Relatórios gerando corretamente
- [ ] Código no GitHub

### Fase 2
- [ ] Templates gerando documentos
- [ ] Campos OAB preenchidos
- [ ] Áreas do direito categorizando serviços

### Fase 3
- [ ] Eventos sincronizando com Google
- [ ] Clientes acessando portal
- [ ] Notificações WhatsApp enviando

### Fase 4
- [ ] Dashboards de jurimetria
- [ ] Documentos assinados digitalmente
- [ ] Múltiplos escritórios usando o sistema

---

## 🔄 PRÓXIMA AÇÃO

**Agora:** Configurar GitHub e fazer primeiro commit

```bash
cd "/home/funil/Área de trabalho/VISUAL CODE/PROJETO 1/logisticajus"
git init
git remote add origin https://github.com/funil66/JURIS-MAIDEN-FINAL.git
git add .
git commit -m "🚀 Initial commit - LogísticaJus MVP completo"
git branch -M main
git push -u origin main
```

---

*Documento atualizado em: 20/12/2025 16:00*
