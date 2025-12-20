# 📋 JURIS-MAIDEN-FINAL - Log de Implementação

> **Projeto:** LogísticaJus (JURIS-MAIDEN-FINAL)  
> **Início do Log:** 20 de Dezembro de 2025  
> **Autor:** Allisson Sousa  
> **Objetivo:** Sistema de Gestão Jurídica para Advogado Correspondente Autônomo  
> **Repositório:** https://github.com/funil66/JURIS-MAIDEN-FINAL

---

## 📖 ÍNDICE

1. [Histórico do Projeto](#-histórico-do-projeto)
2. [Lições Aprendidas](#-lições-aprendidas)
3. [Estado Atual do Sistema](#-estado-atual-do-sistema)
4. [Plano de Ação](#-plano-de-ação)
5. [Log de Implementação](#-log-de-implementação)
6. [Problemas Encontrados](#-problemas-encontrados)
7. [Decisões Técnicas](#-decisões-técnicas)
8. [Próximos Passos](#-próximos-passos)
9. [Referências](#-referências)

---

## 📜 HISTÓRICO DO PROJETO

### Tentativa 1: JURIS-MAIDEN
- **Repositório:** https://github.com/funil66/JURIS-MAIDEN
- **Período:** Anterior a Dezembro/2025
- **Stack:** Laravel 12 + Livewire + AdminLTE + SQLite
- **Status:** ❌ Abandonado (~80% funcional)

**Motivos do Abandono:**
| # | Problema | Impacto |
|---|----------|---------|
| 1 | Over-engineering - tentou implementar TUDO de uma vez | Paralisia de desenvolvimento |
| 2 | Complexidade prematura (SaaS, multi-tenancy, IA, jurimetria) | Código ingerenciável |
| 3 | 27+ arquivos de planejamento na raiz | Análise paralysis |
| 4 | Livewire puro | Muito trabalho manual para UI |
| 5 | SQLite | Inadequado para produção |
| 6 | Arquivos temporários na raiz (debug_output.txt, etc) | Desorganização |

**O que tinha de bom:**
- Trait `HasUuid` para identificadores únicos
- Modelo `Processo` completo (tribunal, vara, juiz)
- Sistema de Templates de documentos
- Preparação para integrações Google
- Campos OAB no User
- Estrutura de Jurimetria

---

### Tentativa 2: JURIS-MAIDEN-2
- **Repositório:** https://github.com/funil66/JURIS-MAIDEN-2
- **Período:** Anterior a Dezembro/2025
- **Stack:** PHP Puro + MySQL + Bootstrap
- **Status:** ❌ Abandonado (site institucional + sistema básico)

**Motivos do Abandono:**
| # | Problema | Impacto |
|---|----------|---------|
| 1 | PHP puro sem framework | Difícil manutenção |
| 2 | Sem ORM, migrations ou Eloquent | Código SQL espalhado |
| 3 | Mistura de site institucional + sistema | Arquitetura confusa |
| 4 | Acoplado a cliente específico | Não reutilizável |
| 5 | Muitas páginas HTML duplicadas | Manutenção impossível |

**O que tinha de bom:**
- Sistema de Diligências bem definido
- Área do Cliente implementada
- API Google Calendar funcional
- Configuração Nginx/Cloudflare
- Scripts de backup
- Categorização por Área do Direito

---

### Tentativa 3: LogísticaJus (JURIS-MAIDEN-FINAL) ✅
- **Repositório:** https://github.com/funil66/JURIS-MAIDEN-FINAL
- **Período:** 20 de Dezembro de 2025 - Atual
- **Stack:** Laravel 12 + FilamentPHP 3.3 + Docker + MySQL + Redis
- **Status:** ✅ Em desenvolvimento ativo

**Diferenciais desta tentativa:**
| Aspecto | Abordagem |
|---------|-----------|
| Desenvolvimento | Sprints incrementais (não tudo de uma vez) |
| Infraestrutura | Docker desde o início |
| UI | FilamentPHP (não reinventar a roda) |
| Banco | MySQL 8.0 (produção-ready) |
| Documentação | Contínua (este arquivo) |
| Foco | MVP primeiro, evoluir depois |

---

## 🎓 LIÇÕES APRENDIDAS

### ❌ O que NÃO fazer (erros dos projetos anteriores)

| Erro | Consequência | Solução Adotada |
|------|--------------|-----------------|
| Implementar tudo de uma vez | Paralisia, abandono | Sprints incrementais |
| Over-engineering inicial | Código complexo demais | MVP primeiro |
| Muitos arquivos de planejamento | Análise paralysis | Um arquivo central (este) |
| SQLite em produção | Limitações de performance | MySQL + Docker |
| Livewire puro | Muito trabalho manual | FilamentPHP (UI pronta) |
| Autenticação customizada | Vulnerabilidades potenciais | FilamentPHP Auth |
| Multi-tenancy prematuro | Complexidade desnecessária | Single-tenant por hora |
| Arquivos temporários na raiz | Desorganização | .gitignore rigoroso |
| Falta de Docker | Ambiente inconsistente | Docker desde o início |
| Falta de documentação | Projeto abandonado sem contexto | Log contínuo |

### ✅ O que APROVEITAR dos projetos anteriores

| Conceito | Origem | Prioridade | Status |
|----------|--------|------------|--------|
| Trait HasUuid | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente |
| Modelo Processo expandido | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente |
| Templates de Documentos | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente |
| Campos OAB no User | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente |
| Portal do Cliente | JURIS-MAIDEN-2 | 🟡 Média | 🔲 Pendente (Fase 3) |
| Integração Google Calendar | Ambos | 🟡 Média | 🔲 Pendente (Fase 3) |
| Relatório de Audiências | JURIS-MAIDEN-2 | 🟢 Baixa | 🔲 Pendente |
| Jurimetria | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente (Fase 4) |
| Categorização por Área do Direito | JURIS-MAIDEN-2 | 🟢 Baixa | 🔲 Pendente |
| Scripts de Backup | JURIS-MAIDEN-2 | ✅ Feito | ✅ Spatie Backup |

---

## 📊 ESTADO ATUAL DO SISTEMA

**Data da última atualização:** 20/12/2025 16:00

### Infraestrutura ✅ COMPLETA
| Componente | Versão | Container | Status |
|------------|--------|-----------|--------|
| PHP | 8.3-FPM Alpine | logisticajus_app | ✅ Running |
| Nginx | Alpine | logisticajus_nginx | ✅ Running |
| MySQL | 8.0 | logisticajus_mysql | ✅ Running |
| Redis | Alpine | logisticajus_redis | ✅ Running |
| Mailpit | Latest | logisticajus_mailpit | ✅ Running |

### Backend ✅ COMPLETO
| Pacote | Versão | Função |
|--------|--------|--------|
| Laravel | 12.43.1 | Framework base |
| FilamentPHP | 3.3.45 | Admin Panel |
| Spatie Permission | Latest | Roles & Permissions |
| Spatie ActivityLog | Latest | Auditoria |
| Spatie Backup | Latest | Backups automáticos |
| DomPDF | Latest | Geração de PDF |
| Maatwebsite Excel | Latest | Export Excel/CSV |
| FullCalendar | Latest | Calendário |

### Modelos (7) ✅ COMPLETOS
| Modelo | Tabela | Soft Delete | Activity Log |
|--------|--------|-------------|--------------|
| User | users | ❌ | ✅ |
| Client | clients | ✅ | ✅ |
| Service | services | ✅ | ✅ |
| ServiceType | service_types | ✅ | ✅ |
| Event | events | ✅ | ✅ |
| Transaction | transactions | ✅ | ✅ |
| PaymentMethod | payment_methods | ✅ | ✅ |

### Filament Resources (6) ✅ COMPLETOS
| Resource | CRUD | Filters | Bulk Actions |
|----------|------|---------|--------------|
| ClientResource | ✅ | ✅ | ✅ |
| ServiceResource | ✅ | ✅ | ✅ |
| ServiceTypeResource | ✅ | ✅ | ✅ |
| EventResource | ✅ | ✅ | ✅ |
| TransactionResource | ✅ | ✅ | ✅ |
| PaymentMethodResource | ✅ | ✅ | ✅ |

### Páginas Customizadas (3) ✅ COMPLETAS
| Página | Rota | Função |
|--------|------|--------|
| CalendarPage | /funil/calendar | FullCalendar integrado |
| ReportsPage | /funil/reports-page | Geração PDF/Excel/CSV |
| SettingsPage | /funil/settings-page | Configurações do sistema |

### Widgets Dashboard (4) ✅ COMPLETOS
| Widget | Tipo | Dados |
|--------|------|-------|
| StatsOverview | Stats | Clientes, Serviços, Receita, Pendente |
| UpcomingEvents | Table | Próximos eventos |
| PendingServices | Table | Serviços pendentes |
| FinancialChart | Chart | Receitas vs Despesas |

### Notificações (3) ✅ COMPLETAS
| Notificação | Canal | Trigger |
|-------------|-------|---------|
| ServiceReminder | Email + Database | 1 dia antes do serviço |
| PaymentDueReminder | Email + Database | 3 dias antes do vencimento |
| PaymentOverdue | Email + Database | Pagamento atrasado |

### Comandos Agendados (5) ✅ CONFIGURADOS
| Horário | Comando | Função |
|---------|---------|--------|
| 08:00 | services:send-reminders | Lembretes de serviços |
| 09:00 | payments:send-reminders | Lembretes de pagamentos |
| 03:00 | backup:run --only-db | Backup diário (DB) |
| 04:00 Dom | backup:run | Backup semanal (full) |
| 05:00 Dom | backup:clean | Limpeza de backups antigos |

### PWA ✅ CONFIGURADO
| Arquivo | Função |
|---------|--------|
| manifest.json | Metadados do app |
| sw.js | Service Worker |
| offline.html | Página offline |
| icon.svg | Ícone do app |

### Deploy ✅ PREPARADO
| Script | Função |
|--------|--------|
| setup-cloudflare.sh | Configurar Cloudflare Tunnel |
| deploy.sh | Deploy em produção |
| .env.production | Variáveis de produção |

### Seeders ✅ EXECUTADOS
| Seeder | Registros |
|--------|-----------|
| ServiceTypeSeeder | 7 tipos de serviço |
| PaymentMethodSeeder | 6 métodos de pagamento |
| AdminUserSeeder | 1 usuário admin |

---

## 🎯 PLANO DE AÇÃO

### FASE 1: Estabilização (Sprint 11) - 🔄 EM ANDAMENTO
**Objetivo:** Garantir que tudo funciona perfeitamente antes de avançar
**Prazo:** 20/12/2025

| # | Tarefa | Prioridade | Status | Notas |
|---|--------|------------|--------|-------|
| 1.1 | Corrigir erro `service_date` → `scheduled_datetime` | 🔴 Alta | ✅ Concluído | 8 arquivos corrigidos |
| 1.2 | Publicar assets Livewire | 🔴 Alta | ✅ Concluído | use_published_assets=true |
| 1.3 | Configurar repositório GitHub | 🔴 Alta | 🔄 Em andamento | - |
| 1.4 | Criar documentação (este arquivo) | 🔴 Alta | ✅ Concluído | - |
| 1.5 | Testar todos os CRUDs | 🟡 Média | 🔲 Pendente | - |
| 1.6 | Testar geração de relatórios | 🟡 Média | 🔲 Pendente | - |
| 1.7 | Testar calendário | 🟡 Média | 🔲 Pendente | - |
| 1.8 | Criar seed de dados de teste | 🟡 Média | 🔲 Pendente | - |
| 1.9 | Primeiro commit no GitHub | 🔴 Alta | 🔲 Pendente | - |

### FASE 2: Melhorias Inspiradas (Sprint 12-14)
**Objetivo:** Incorporar as melhores ideias dos projetos anteriores
**Prazo estimado:** 1 semana

| # | Tarefa | Origem | Prioridade | Status | Complexidade |
|---|--------|--------|------------|--------|--------------|
| 2.1 | Migration: Adicionar campos OAB ao User | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Baixa |
| 2.2 | Criar Trait HasUuid | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Baixa |
| 2.3 | Migration: Expandir Service com campos de processo | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Média |
| 2.4 | Criar modelo LegalArea (Áreas do Direito) | JURIS-MAIDEN-2 | 🟢 Baixa | 🔲 Pendente | Baixa |
| 2.5 | Criar modelo Template (documentos) | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Média |
| 2.6 | Criar modelo GeneratedDocument | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Média |
| 2.7 | TemplateResource (CRUD) | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Média |
| 2.8 | Geração de documentos com variáveis | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Alta |
| 2.9 | Relatório específico de Audiências | JURIS-MAIDEN-2 | 🟢 Baixa | 🔲 Pendente | Baixa |

### FASE 3: Integrações (Sprint 15-18)
**Objetivo:** Conectar com serviços externos
**Prazo estimado:** 2 semanas

| # | Tarefa | Origem | Prioridade | Status | Complexidade |
|---|--------|--------|------------|--------|--------------|
| 3.1 | Configurar OAuth Google | Ambos | 🟡 Média | 🔲 Pendente | Média |
| 3.2 | Integração Google Calendar (sincronização) | Ambos | 🟡 Média | 🔲 Pendente | Alta |
| 3.3 | Integração Google Drive (backup docs) | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Média |
| 3.4 | Portal do Cliente (painel separado) | JURIS-MAIDEN-2 | 🟡 Média | 🔲 Pendente | Alta |
| 3.5 | WhatsApp API (notificações) | JURIS-MAIDEN | 🟡 Média | 🔲 Pendente | Alta |
| 3.6 | Consulta PJe/e-SAJ (scraping básico) | Novo | 🟢 Baixa | 🔲 Pendente | Alta |

### FASE 4: Funcionalidades Avançadas (Sprint 19+)
**Objetivo:** Diferenciação e features premium
**Prazo estimado:** 1+ mês

| # | Tarefa | Origem | Prioridade | Status | Complexidade |
|---|--------|--------|------------|--------|--------------|
| 4.1 | Jurimetria (estatísticas de resultados) | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Alta |
| 4.2 | Dashboard de métricas avançadas | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Média |
| 4.3 | Assinatura Digital (certificado A1/A3) | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Muito Alta |
| 4.4 | Multi-tenancy (SaaS) | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Muito Alta |
| 4.5 | App Mobile (React Native/Flutter) | Novo | 🟢 Baixa | 🔲 Pendente | Muito Alta |
| 4.6 | IA para análise de documentos | JURIS-MAIDEN | 🟢 Baixa | 🔲 Pendente | Muito Alta |

---

## 📝 LOG DE IMPLEMENTAÇÃO

### 📅 20/12/2025 - Dia 1 (Início do Projeto)

#### 🌅 Manhã (00:00 - 12:00) - Sprints 1-10

**Sprint 1: Infraestrutura Docker**
- ✅ Criado `docker-compose.yml` com 5 containers
- ✅ Configurado PHP 8.3-FPM Alpine
- ✅ Configurado Nginx Alpine
- ✅ Configurado MySQL 8.0
- ✅ Configurado Redis Alpine
- ✅ Configurado Mailpit para emails de desenvolvimento

**Sprint 2: Laravel + FilamentPHP**
- ✅ Laravel 12.43.1 instalado
- ✅ FilamentPHP 3.3.45 configurado
- ✅ Painel "Funil" em /funil
- ✅ Usuário admin criado

**Sprint 3: Módulo Clientes**
- ✅ Model Client com validação CPF/CNPJ
- ✅ ClientResource com CRUD completo
- ✅ Soft deletes implementado
- ✅ Activity log configurado

**Sprint 4: Módulo Serviços**
- ✅ Model ServiceType (7 tipos seedados)
- ✅ Model Service com código automático (SRV-YYYY-NNNN)
- ✅ ServiceResource e ServiceTypeResource

**Sprint 5: Módulo Agenda**
- ✅ Model Event
- ✅ EventResource
- ✅ CalendarPage com FullCalendar
- ✅ Widgets UpcomingEvents

**Sprint 6: Módulo Financeiro**
- ✅ Model PaymentMethod (6 métodos seedados)
- ✅ Model Transaction
- ✅ TransactionResource e PaymentMethodResource
- ✅ Widget FinancialChart

**Sprint 7: Relatórios**
- ✅ ReportsPage criada
- ✅ Geração de PDF com DomPDF
- ✅ Export Excel/CSV com Maatwebsite
- ✅ 4 tipos de relatório (Serviços, Clientes, Financeiro, Geral)

**Sprint 8: Notificações**
- ✅ ServiceReminder notification
- ✅ PaymentDueReminder notification
- ✅ PaymentOverdue notification
- ✅ Comandos agendados configurados

**Sprint 9: PWA + Deploy**
- ✅ manifest.json criado
- ✅ Service Worker implementado
- ✅ Scripts de deploy (Cloudflare Tunnel)
- ✅ .env.production configurado

**Sprint 10: Backup**
- ✅ Spatie Backup configurado
- ✅ Backup diário do banco
- ✅ Backup semanal completo
- ✅ Limpeza automática de backups antigos

---

#### 🌆 Tarde (14:00 - 16:00) - Correções e Análise

**14:00 - 14:30: Problema #1 - Livewire 404**
- ❌ Erro: 405 Method Not Allowed ao fazer login
- 🔍 Investigação: Livewire.js retornando 404
- ✅ Solução: Publicar assets com `vendor:publish --tag=livewire:assets`
- ✅ Configuração: `use_published_assets => true` em config/livewire.php

**14:30 - 15:00: Problema #2 - Coluna service_date**
- ❌ Erro: SQLSTATE[42S22] Unknown column 'service_date'
- 🔍 Investigação: 8 arquivos referenciando coluna inexistente
- ✅ Solução: Substituir `service_date` por `scheduled_datetime`
- 📁 Arquivos corrigidos:
  - app/Filament/Pages/ReportsPage.php
  - app/Exports/ServicesExport.php
  - app/Exports/ClientsExport.php
  - app/Console/Commands/SendServiceReminders.php
  - app/Notifications/ServiceReminder.php
  - resources/views/filament/pages/reports-page.blade.php
  - resources/views/reports/services.blade.php
  - resources/views/reports/general.blade.php

**15:00 - 15:30: Análise de Projetos Anteriores**
- ✅ Analisado JURIS-MAIDEN (Laravel + Livewire)
- ✅ Analisado JURIS-MAIDEN-2 (PHP Puro)
- ✅ Identificados pontos fortes e fracos de cada um
- ✅ Criado plano de incorporação de ideias

**15:30 - 16:00: Documentação**
- ✅ Criado IMPLEMENTATION_LOG.md
- 🔄 Configurando repositório GitHub

---

## ⚠️ PROBLEMAS ENCONTRADOS

### Problema #1: Livewire.js 404
| Campo | Valor |
|-------|-------|
| **ID** | P001 |
| **Data** | 20/12/2025 14:30 |
| **Severidade** | 🔴 Crítico (bloqueante) |
| **Sintoma** | Erro 405 Method Not Allowed ao tentar login |
| **Causa Raiz** | Rota dinâmica `/livewire/livewire.js` retornando 404 |

**Investigação:**
1. Verificado que rota existe em `php artisan route:list`
2. Verificado que arquivo não existe em `/public/vendor/livewire/`
3. Identificado que Livewire 3 usa rota dinâmica por padrão
4. Em ambiente Docker, rota dinâmica não funcionou

**Solução:**
```bash
php artisan vendor:publish --tag=livewire:assets --force
```

E adicionar em `config/livewire.php`:
```php
'asset_url' => null,
'use_published_assets' => true,
```

**Lição Aprendida:** Em ambiente Docker, preferir assets publicados a rotas dinâmicas.

**Status:** ✅ Resolvido

---

### Problema #2: Coluna service_date inexistente
| Campo | Valor |
|-------|-------|
| **ID** | P002 |
| **Data** | 20/12/2025 14:50 |
| **Severidade** | 🔴 Crítico (página quebrada) |
| **Sintoma** | Erro 500 na página de Relatórios |
| **Mensagem** | `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'service_date'` |
| **Causa Raiz** | Código referenciando coluna `service_date` que não existe na migration |

**Investigação:**
1. Verificado schema da tabela `services` com `Schema::getColumnListing()`
2. Identificado que coluna correta é `scheduled_datetime`
3. Encontrado referências incorretas em 8 arquivos

**Solução:**
```bash
sed -i "s/service_date/scheduled_datetime/g" <arquivos>
php artisan view:clear
```

**Arquivos Afetados:** 8 arquivos (listados acima)

**Lição Aprendida:** Sempre verificar schema real antes de usar nomes de coluna.

**Status:** ✅ Resolvido

---

## 🔧 DECISÕES TÉCNICAS

### DT-001: Usar FilamentPHP ao invés de Livewire puro
| Campo | Valor |
|-------|-------|
| **Data** | 20/12/2025 |
| **Contexto** | JURIS-MAIDEN usava Livewire puro com muito código manual |
| **Decisão** | Usar FilamentPHP 3.3 para UI administrativa |
| **Alternativas Consideradas** | Livewire puro, Laravel Nova, Backpack |

**Justificativa:**
- CRUD pronto (não reinventar a roda)
- Formulários, tabelas, filtros incluídos
- Menos código para manter
- Comunidade ativa
- Gratuito (Nova é pago)

**Consequências:**
- ✅ Desenvolvimento mais rápido
- ✅ UI consistente e bonita
- ⚠️ Menos flexibilidade visual (aceitável para admin)

---

### DT-002: MySQL ao invés de SQLite
| Campo | Valor |
|-------|-------|
| **Data** | 20/12/2025 |
| **Contexto** | JURIS-MAIDEN usava SQLite |
| **Decisão** | Usar MySQL 8.0 em Docker |
| **Alternativas Consideradas** | SQLite, PostgreSQL, MariaDB |

**Justificativa:**
- Performance em produção
- Suporte completo a JSON columns
- Mais robusto para concorrência
- Amplamente suportado em hosting

**Consequências:**
- ✅ Pronto para produção
- ✅ Full-text search nativo
- ⚠️ Mais complexo que SQLite (Docker resolve)

---

### DT-003: Docker desde o início
| Campo | Valor |
|-------|-------|
| **Data** | 20/12/2025 |
| **Contexto** | Projetos anteriores não tinham Docker |
| **Decisão** | Docker Compose com 5 containers desde o dia 1 |

**Justificativa:**
- Ambiente consistente (dev = prod)
- Fácil deploy
- Isolamento de serviços
- Onboarding simplificado para novos devs

**Consequências:**
- ✅ Deploy simplificado
- ✅ Ambiente idêntico dev/prod
- ⚠️ Curva de aprendizado Docker (documentação ajuda)

---

### DT-004: Pacotes Spatie para funcionalidades comuns
| Campo | Valor |
|-------|-------|
| **Data** | 20/12/2025 |
| **Decisão** | Usar pacotes Spatie (Permission, ActivityLog, Backup) |

**Justificativa:**
- Código testado e mantido pela comunidade
- Integração Laravel nativa
- Evitar reinventar a roda
- Documentação excelente

**Pacotes utilizados:**
- spatie/laravel-permission (Roles & Permissions)
- spatie/laravel-activitylog (Auditoria)
- spatie/laravel-backup (Backups)

**Consequências:**
- ✅ Funcionalidades robustas
- ✅ Menos bugs
- ⚠️ Dependência de terceiros (risco baixo - Spatie é confiável)

---

### DT-005: Assets Livewire publicados (não rota dinâmica)
| Campo | Valor |
|-------|-------|
| **Data** | 20/12/2025 |
| **Contexto** | Livewire.js 404 em ambiente Docker |
| **Decisão** | Publicar assets ao invés de usar rota dinâmica |

**Justificativa:**
- Funciona melhor com cache/CDN
- Evita problemas de rota em ambientes containerizados
- Mais previsível

**Configuração necessária:**
```php
// config/livewire.php
'use_published_assets' => true,
```

**Consequências:**
- ✅ Mais estável em Docker
- ⚠️ Precisa republicar ao atualizar Livewire

---

### DT-006: Desenvolvimento incremental (Sprints)
| Campo | Valor |
|-------|-------|
| **Data** | 20/12/2025 |
| **Contexto** | JURIS-MAIDEN tentou fazer tudo de uma vez e foi abandonado |
| **Decisão** | Desenvolver em sprints pequenos e funcionais |

**Justificativa:**
- Cada sprint entrega valor
- Fácil identificar problemas
- Motivação por progresso visível
- Permite pivot se necessário

**Consequências:**
- ✅ Projeto sempre funcional
- ✅ Progresso mensurável
- ⚠️ Pode parecer mais lento (mas é mais sustentável)

---

## 🚀 PRÓXIMOS PASSOS

### Imediato (Hoje - 20/12/2025)
- [x] Finalizar configuração GitHub ✅
- [x] Fazer primeiro commit ✅
- [x] Testar todos os CRUDs manualmente ✅
- [x] Criar seeder de dados de teste ✅

### Sprint 11 (20/12/2025) - Estabilização
- [x] Correção Livewire.js 404
- [x] Correção service_date → scheduled_datetime
- [x] Análise projetos anteriores
- [x] Configuração repositório GitHub
- [x] TestDataSeeder com 75 registros
- [x] Documentação IMPLEMENTATION_LOG.md

### Sprint 12 (20/12/2025) - Campos OAB no User ✅
- [x] Migration: add_oab_fields_to_users_table
  - Campos: oab, oab_uf, specialties, phone, whatsapp, bio, avatar, website, linkedin, is_active
- [x] User model: casts, métodos auxiliares (getOabFormattedAttribute, getSpecialtiesTextAttribute)
- [x] User model: listas estáticas (getOabStates, getLegalSpecialties)
- [x] EditProfile.php: página de edição de perfil com seções organizadas
- [x] edit-profile.blade.php: view do perfil com informações da conta
- [x] FunilPanelProvider: habilitado profile()
- [x] Commit: "Sprint 12: Campos OAB e Página de Perfil"

### Sprint 13 (20/12/2025) - Expandir Modelo Service ✅
- [x] Migration: add_extended_fields_to_services_table
  - Dados do Juízo: judge_name, court_secretary, court_phone, court_email
  - Solicitante: requester_name, requester_email, requester_phone, requester_oab
  - Deslocamento: travel_distance_km, travel_cost, travel_type, travel_notes
  - Documentos: attachments, has_substabelecimento, has_procuracao, documents_received, documents_received_at
  - Resultado: result_type, actual_datetime, result_summary, result_attachments
  - Qualidade: client_rating, client_feedback, requires_followup, followup_notes
- [x] Service model: 26 novos campos no fillable
- [x] Service model: 12 novos casts (arrays, booleans, decimals, dates)
- [x] Service model: métodos auxiliares (getTravelTypeOptions, getResultTypeOptions, getRatingOptions, etc)
- [x] Service model: scopes (needsFollowup, missingDocuments)
- [x] ServiceResource: 6 novas seções no formulário
  - Dados do Juízo
  - Solicitante
  - Deslocamento
  - Documentos (com FileUpload)
  - Resultado (com FileUpload para comprovantes)
  - Avaliação e Follow-up

### Esta Semana (Próximos Sprints)
- [ ] Sprint 14: Templates de Documentos
- [ ] Sprint 15: Google Calendar integração
- [ ] Sprint 16: Portal do Cliente

### Janeiro/2026
- [ ] Sprint 17: WhatsApp API para notificações
- [ ] Sprint 18: Relatório específico de Audiências
- [ ] Sprint 19: Dashboard avançado com métricas

---

## 📚 REFERÊNCIAS

### Repositórios do Projeto
| Repositório | URL | Status |
|-------------|-----|--------|
| JURIS-MAIDEN (v1) | https://github.com/funil66/JURIS-MAIDEN | Abandonado |
| JURIS-MAIDEN-2 (v2) | https://github.com/funil66/JURIS-MAIDEN-2 | Abandonado |
| JURIS-MAIDEN-FINAL (v3) | https://github.com/funil66/JURIS-MAIDEN-FINAL | ✅ Ativo |

### Documentação Técnica
| Tecnologia | URL |
|------------|-----|
| Laravel 12 | https://laravel.com/docs/12.x |
| FilamentPHP 3 | https://filamentphp.com/docs/3.x |
| Spatie Packages | https://spatie.be/open-source |
| Docker | https://docs.docker.com/ |
| Livewire 3 | https://livewire.laravel.com/docs |

### Contato
| Campo | Valor |
|-------|-------|
| **Desenvolvedor** | Allisson Sousa |
| **Email** | allissonsousa.adv@gmail.com |
| **Domínio Produção** | sistema.allissonsousa.adv.br |

---

## 📋 CHECKLIST PARA NOVOS DESENVOLVEDORES

Se você está continuando este projeto, siga estes passos:

### 1. Clonar e Configurar
```bash
git clone https://github.com/funil66/JURIS-MAIDEN-FINAL.git
cd JURIS-MAIDEN-FINAL
cp src/.env.example src/.env
```

### 2. Subir Ambiente Docker
```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 3. Acessar o Sistema
- URL: http://localhost:8080/funil
- Email: allissonsousa.adv@gmail.com
- Senha: (definida no seeder)

### 4. Ler Esta Documentação
1. Entenda o histórico (por que projetos anteriores falharam)
2. Veja o estado atual (o que já está pronto)
3. Consulte o plano de ação (próximos passos)
4. Verifique problemas conhecidos (evite repetir erros)

### 5. Antes de Qualquer Alteração
1. Crie uma branch: `git checkout -b feature/nome-da-feature`
2. Atualize este log com o que vai fazer
3. Teste localmente
4. Documente problemas encontrados
5. Faça PR para main

---

> **⚠️ REGRA DE OURO:** Este documento deve ser atualizado a CADA sessão de desenvolvimento.
> Se você fez algo e não documentou aqui, é como se não tivesse feito.

---

*Última atualização: 20/12/2025 16:00*
*Próxima atualização prevista: Após configurar GitHub*
