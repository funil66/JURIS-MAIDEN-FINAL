# Roadmap de Desenvolvimento

## Visão Geral

O desenvolvimento está organizado em **sprints semanais** com revisão ao final de cada uma.
O foco é entregar incrementos funcionais testáveis a cada semana.

---

## Fase 1: MVP - Gestão Pessoal

**Duração**: 6 semanas  
**Objetivo**: Sistema funcional para gestão do trabalho de advogado correspondente autônomo

---

### Sprint 1 - Fundação e Docker ✅

**Período**: Semana 1  
**Status**: Em andamento

#### Tarefas

- [x] Criar estrutura de pastas do projeto
- [x] Configurar `docker-compose.yml` com todos os serviços
- [x] Criar `Dockerfile` PHP 8.3 otimizado
- [x] Configurar Nginx como reverse proxy
- [x] Criar `.env.example` com todas as variáveis
- [x] Documentar em `README.md`
- [x] Criar `docs/ARCHITECTURE.md`
- [x] Criar `docs/ROADMAP.md`
- [ ] Subir containers Docker
- [ ] Instalar Laravel 11 via Composer
- [ ] Instalar FilamentPHP v3
- [ ] Instalar pacotes Spatie (permission, activitylog, backup, media-library)
- [ ] Configurar FilamentPHP
- [ ] Rodar migrations base
- [ ] Criar usuário admin
- [ ] Testar acesso ao painel em `localhost:8080/admin`

#### Critérios de Aceite

- [ ] Containers sobem sem erros
- [ ] Laravel responde em `localhost:8080`
- [ ] FilamentPHP acessível em `/admin`
- [ ] Login admin funciona
- [ ] Documentação completa e atualizada

#### Revisão

**Data**: Fim da Semana 1  
**Checklist**:
- Ambiente Docker funcional
- Documentação clara para replicar
- Admin consegue logar no painel

---

### Sprint 2 - Módulo de Clientes

**Período**: Semana 2  
**Status**: Não iniciada

#### Tarefas

- [ ] Criar migration `clients`
  - `id`, `user_id`, `nome`, `tipo_pessoa` (PF/PJ)
  - `cpf`, `cnpj`, `rg`, `telefone`, `email`
  - `cep`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`
  - `observacoes`, `ativo`, `timestamps`
- [ ] Criar Model `Client` com:
  - Validação de CPF/CNPJ
  - Mutators para formatação
  - Relacionamento com User
  - Relacionamento com Services
  - Soft deletes
- [ ] Configurar Media Library para anexos
- [ ] Criar Filament Resource `ClientResource`:
  - Formulário com máscaras (CPF, CNPJ, telefone, CEP)
  - Validação inline
  - Tabs: Dados Pessoais, Endereço, Documentos
  - Upload múltiplo de documentos
- [ ] Implementar busca CEP via ViaCEP API
- [ ] Criar filtros: tipo pessoa, ativo/inativo, cidade
- [ ] Criar busca global por nome e CPF/CNPJ
- [ ] Atualizar `docs/MODULES.md`

#### Critérios de Aceite

- [ ] CRUD completo de clientes funciona
- [ ] Validação CPF/CNPJ funciona
- [ ] Upload de documentos funciona
- [ ] Busca CEP preenche endereço automaticamente
- [ ] Filtros e busca funcionam

#### Revisão

**Data**: Fim da Semana 2  
**Checklist**:
- Cadastrar 5 clientes de teste (PF e PJ)
- Testar todos os filtros
- Verificar documentos anexados

---

### Sprint 3 - Módulo de Serviços

**Período**: Semana 3  
**Status**: Não iniciada

#### Tarefas

- [ ] Criar migration `service_types`:
  - Audiência, Despacho, Protocolo, Cópia
  - Visita Penitenciária, Assinatura Procuração
  - Diligência Externa, Outros
  - Campos: `nome`, `descricao`, `valor_padrao`, `icone`, `ativo`
- [ ] Criar migration `services`:
  - `client_id`, `service_type_id`, `user_id`
  - `titulo`, `descricao`, `local`, `endereco_completo`
  - `data_agendada`, `hora_agendada`, `prazo_fatal`
  - `status` (enum: pendente, agendado, em_andamento, concluido, cancelado)
  - `valor`, `valor_deslocamento`, `observacoes`, `timestamps`
- [ ] Criar Models com relacionamentos
- [ ] Criar Filament Resource `ServiceTypeResource` (CRUD simples)
- [ ] Criar Filament Resource `ServiceResource`:
  - Seleção de cliente com busca
  - Seleção de tipo de serviço
  - Campos de data/hora com validação
  - Cálculo automático de valor (tipo + deslocamento)
- [ ] Implementar visualização Kanban por status
- [ ] Criar widgets dashboard:
  - "Serviços Hoje" (card contador)
  - "Próximos 7 Dias" (lista)
  - "Prazos Fatais Próximos" (alerta vermelho se < 3 dias)
- [ ] Atualizar `docs/MODULES.md`

#### Critérios de Aceite

- [ ] Tipos de serviço gerenciáveis
- [ ] Criar serviço vinculado a cliente funciona
- [ ] Kanban permite arrastar entre status
- [ ] Widgets mostram dados corretos
- [ ] Prazos fatais destacados

#### Revisão

**Data**: Fim da Semana 3  
**Checklist**:
- Cadastrar tipos de serviço padrão
- Criar 10 serviços de teste
- Testar movimentação no Kanban
- Verificar widgets no dashboard

---

### Sprint 4 - Módulo de Agenda + Google Calendar

**Período**: Semana 4  
**Status**: Não iniciada

#### Tarefas

- [ ] Configurar Google Cloud Console:
  - Criar projeto "LogísticaJus"
  - Habilitar Google Calendar API
  - Criar credenciais OAuth 2.0
  - Configurar redirect URI
- [ ] Instalar `spatie/laravel-google-calendar`
- [ ] Criar migration `calendar_events`:
  - `service_id` (nullable), `user_id`
  - `titulo`, `descricao`, `local`
  - `data_inicio`, `data_fim`, `dia_inteiro`
  - `google_event_id`, `sincronizado_em`
  - `lembrete_24h_enviado`, `lembrete_1h_enviado`
- [ ] Criar Model `CalendarEvent`
- [ ] Criar Service `GoogleCalendarSync`:
  - `syncToGoogle()`: cria/atualiza no Google
  - `syncFromGoogle()`: importa do Google
- [ ] Criar página Agenda no Filament:
  - Visualização calendário (mensal/semanal/diária)
  - Cores por tipo de serviço
  - Click para criar evento
- [ ] Criar Observer `ServiceObserver`:
  - Ao criar serviço com data → cria CalendarEvent
  - Sincroniza automaticamente com Google
- [ ] Criar Command `SendReminders`:
  - Executa a cada 15 minutos (cron)
  - Envia email 24h e 1h antes
  - Marca flags de lembrete enviado
- [ ] Configurar cron no Docker
- [ ] Atualizar `docs/MODULES.md`

#### Critérios de Aceite

- [ ] Autenticação OAuth com Google funciona
- [ ] Eventos criados aparecem no Google Calendar
- [ ] Eventos do Google aparecem na agenda do sistema
- [ ] Serviço com data cria evento automaticamente
- [ ] Lembretes enviados por email

#### Revisão

**Data**: Fim da Semana 4  
**Checklist**:
- Autenticar com conta Google
- Criar evento e verificar no Google Calendar
- Criar serviço e verificar evento automático
- Verificar recebimento de lembretes

---

### Sprint 5 - Módulo Financeiro

**Período**: Semana 5  
**Status**: Não iniciada

#### Tarefas

- [ ] Criar migration `financials`:
  - `service_id` (nullable), `client_id` (nullable), `user_id`
  - `tipo` (enum: receita, despesa)
  - `categoria` (honorários, deslocamento, custas, material, outros)
  - `descricao`, `valor`
  - `data_competencia`, `data_vencimento`, `data_pagamento`
  - `status` (pendente, pago, atrasado, cancelado)
  - `forma_pagamento` (pix, dinheiro, transferencia, boleto, cartao)
  - `observacoes`, `timestamps`
- [ ] Criar Model `Financial` com scopes:
  - `scopeReceitas()`, `scopeDespesas()`
  - `scopePendentes()`, `scopeAtrasados()`
  - `scopeDoMes($mes, $ano)`
- [ ] Configurar Media Library para comprovantes
- [ ] Criar Filament Resource `FinancialResource`:
  - Tabs: Todas, Receitas, Despesas, Pendentes, Atrasadas
  - Filtros por período, categoria, cliente, status
  - Cores por status (verde=pago, amarelo=pendente, vermelho=atrasado)
  - Ação em lote: marcar como pago
  - Upload de comprovante
- [ ] Criar Observer `ServiceObserver` (extensão):
  - Ao concluir serviço → cria Financial (receita)
  - Valor = service.valor + service.valor_deslocamento
- [ ] Instalar `barryvdh/laravel-dompdf`
- [ ] Criar relatórios PDF:
  - Extrato mensal (receitas x despesas)
  - Relatório por cliente
  - Comprovante de serviço prestado
- [ ] Criar widgets dashboard:
  - "Faturamento do Mês" (gráfico)
  - "A Receber" (valor total pendente)
  - "Inadimplência" (atrasados > 30 dias)
  - "Balanço" (receitas - despesas)
- [ ] Atualizar `docs/MODULES.md`

#### Critérios de Aceite

- [ ] CRUD financeiro completo
- [ ] Conclusão de serviço gera receita automática
- [ ] Filtros e tabs funcionam
- [ ] Relatórios PDF gerados corretamente
- [ ] Widgets calculam valores corretos

#### Revisão

**Data**: Fim da Semana 5  
**Checklist**:
- Concluir serviço e verificar receita criada
- Testar todos os filtros
- Gerar relatório mensal PDF
- Verificar cálculos nos widgets

---

### Sprint 6 - PWA e Deploy

**Período**: Semana 6  
**Status**: Não iniciada

#### Tarefas

- [ ] Instalar `silviolleite/laravelpwa`:
  - Configurar `manifest.json`
  - Nome: "LogísticaJus"
  - Cores: tema do escritório
  - Ícones 192x192 e 512x512
- [ ] Configurar Service Worker:
  - Cache de assets estáticos
  - Fallback offline básico
- [ ] Otimizar FilamentPHP para mobile:
  - Testar formulários em tela pequena
  - Sidebar colapsável
  - Tabelas com scroll horizontal
- [ ] Customizar login Filament:
  - Logo Allisson Sousa Advocacia
  - Cores da marca
  - Mensagem de boas-vindas
- [ ] Configurar Cloudflare Tunnel:
  - Adicionar rota `sistema.allissonsousa.adv.br`
  - Apontar para `localhost:8080`
  - Testar acesso externo
  - (Opcional) Cloudflare Access para 2FA
- [ ] Configurar backups automáticos:
  - `spatie/laravel-backup`
  - Cron diário às 03:00
  - Notificação por email
- [ ] Otimizar para produção:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `php artisan filament:optimize`
- [ ] Criar `docs/DEPLOYMENT.md`
- [ ] Atualizar `README.md` final

#### Critérios de Aceite

- [ ] PWA instalável no celular
- [ ] Sistema acessível via `sistema.allissonsousa.adv.br`
- [ ] SSL funcionando (https)
- [ ] Backup diário executando
- [ ] Performance aceitável (< 3s load time)

#### Revisão Final MVP

**Data**: Fim da Semana 6  
**Checklist**:
- [ ] Todos os módulos funcionando
- [ ] Fluxo completo: Cliente → Serviço → Agenda → Financeiro
- [ ] Acesso via Cloudflare Tunnel
- [ ] PWA instalado no celular
- [ ] Backup funcionando
- [ ] Documentação completa

---

## Fase 2: Integrações

**Duração**: 4 semanas  
**Objetivo**: Automação de comunicação e pagamentos

---

### Sprint 7-8 - WhatsApp Business API

**Período**: Semanas 7-8  
**Status**: Planejada

#### Tarefas

- [ ] Configurar conta Meta Business
- [ ] Verificar empresa
- [ ] Criar templates de mensagem:
  - Lembrete de compromisso (24h)
  - Confirmação de agendamento
  - Cobrança pendente
  - Comprovante de conclusão
- [ ] Integrar API (oficial ou via Twilio)
- [ ] Adicionar flag opt-in no cadastro de cliente
- [ ] Criar Command para envio automático de lembretes
- [ ] Logs de mensagens enviadas

---

### Sprint 9-10 - Asaas Payment Gateway

**Período**: Semanas 9-10  
**Status**: Planejada

#### Tarefas

- [ ] Criar conta Asaas
- [ ] Obter API keys (sandbox primeiro)
- [ ] Criar Service `AsaasGateway`:
  - Criar cobrança (PIX, Boleto)
  - Consultar status
  - Processar webhooks
- [ ] Integrar no módulo Financeiro:
  - Botão "Gerar Cobrança"
  - Status atualizado automaticamente
  - Link de pagamento
- [ ] Exibir QR Code PIX no sistema
- [ ] Enviar link de pagamento por email/WhatsApp

---

## Fase 3: Marketplace

**Duração**: 8 semanas  
**Objetivo**: Plataforma de intermediação entre escritórios e correspondentes

**Status**: Planejamento futuro

### Sprints 11-18 (Resumo)

- Módulo de Correspondentes (perfis, OAB, geolocalização)
- Sistema de ordens de serviço "cegas"
- Chat sanitizado (anti-disintermediação)
- Split de pagamentos Asaas
- Validação OAB (manual → crawler)
- Gamificação (rankings, badges)
- Dashboard multi-tenant

---

## Backlog Futuro

### Alta Prioridade
- [ ] Integração PJe/e-SAJ (acompanhamento processual)
- [ ] OCR para validação de documentos
- [ ] Notificações push (PWA)

### Média Prioridade
- [ ] App nativo (React Native/Flutter)
- [ ] Relatórios avançados (gráficos interativos)
- [ ] Importação de dados (Excel/CSV)

### Baixa Prioridade
- [ ] Multi-tenancy (white-label)
- [ ] API pública
- [ ] Integração com contabilidade

---

## Métricas de Sucesso

### MVP (Fase 1)
- Sistema 100% funcional para uso pessoal
- Tempo de cadastro de serviço < 2 minutos
- Sincronização Google Calendar sem falhas
- Zero perda de dados

### Integrações (Fase 2)
- Taxa de abertura WhatsApp > 80%
- Pagamentos processados automaticamente > 90%
- Redução de inadimplência em 30%

### Marketplace (Fase 3)
- 50 correspondentes cadastrados
- 100 ordens de serviço/mês
- Taxa de conclusão > 95%
- NPS > 8

---

## Changelog

### v0.1.0 (Sprint 1) - Em desenvolvimento
- Setup inicial Docker
- Instalação Laravel 11 + FilamentPHP
- Documentação base
