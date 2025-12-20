# Arquitetura do Sistema

## Visão Geral

O LogísticaJus é uma aplicação monolítica modular construída sobre Laravel 11, 
utilizando FilamentPHP v3 para interface administrativa. O sistema é containerizado 
com Docker para facilitar desenvolvimento, deploy e futura migração para Raspberry Pi.

## Stack Técnica

### Backend
| Componente | Tecnologia | Versão |
|------------|------------|--------|
| Framework | Laravel | 11.x |
| PHP | PHP-FPM | 8.3 |
| Admin Panel | FilamentPHP | 3.x |
| ORM | Eloquent | - |
| Queue | Laravel Queue + Redis | - |

### Banco de Dados
| Componente | Tecnologia | Versão |
|------------|------------|--------|
| SGBD | MySQL | 8.0 |
| Cache/Session | Redis | Alpine |
| Tipos Especiais | MySQL Spatial (futuro) | - |

### Infraestrutura
| Componente | Tecnologia | Propósito |
|------------|------------|-----------|
| Containers | Docker + Compose | Isolamento |
| Web Server | Nginx | Reverse Proxy |
| Tunnel | Cloudflare Tunnel | Zero-trust access |
| SSL | Cloudflare | Certificado automático |
| Backup | Spatie Backup | Dados e arquivos |

## Diagrama de Containers

```
┌─────────────────────────────────────────────────────────────────┐
│                     CLOUDFLARE TUNNEL                            │
│              sistema.allissonsousa.adv.br                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Docker Network                               │
│                  (logisticajus_network)                         │
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │    Nginx     │───▶│   PHP-FPM    │───▶│    MySQL     │      │
│  │    :8080     │    │   (Laravel)  │    │    :3306     │      │
│  │              │    │              │    │              │      │
│  │  Static      │    │  FilamentPHP │    │  Dados       │      │
│  │  Assets      │    │  Artisan     │    │  Persistidos │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│                             │                                    │
│                             ▼                                    │
│                  ┌──────────────┐    ┌──────────────┐          │
│                  │    Redis     │    │   Mailpit    │          │
│                  │    :6379     │    │    :8025     │          │
│                  │              │    │              │          │
│                  │  Cache       │    │  Email Dev   │          │
│                  │  Sessions    │    │  Testing     │          │
│                  │  Queue       │    │              │          │
│                  └──────────────┘    └──────────────┘          │
└─────────────────────────────────────────────────────────────────┘
```

## Pacotes Laravel Principais

### Core
| Pacote | Versão | Propósito |
|--------|--------|-----------|
| `filament/filament` | ^3.3 | Admin panel completo |
| `livewire/livewire` | ^3.0 | Componentes reativos |

### Autorização e Auditoria
| Pacote | Versão | Propósito |
|--------|--------|-----------|
| `spatie/laravel-permission` | ^6.x | Roles e permissões |
| `spatie/laravel-activitylog` | ^4.x | Log de atividades |

### Arquivos e Mídia
| Pacote | Versão | Propósito |
|--------|--------|-----------|
| `spatie/laravel-media-library` | ^11.x | Upload e gestão de arquivos |
| `barryvdh/laravel-dompdf` | ^3.x | Geração de PDFs |

### Integrações (Fase 2)
| Pacote | Versão | Propósito |
|--------|--------|-----------|
| `spatie/laravel-google-calendar` | ^3.x | Sincronização agenda |
| `silviolleite/laravelpwa` | ^2.x | Progressive Web App |

### Backup e Manutenção
| Pacote | Versão | Propósito |
|--------|--------|-----------|
| `spatie/laravel-backup` | ^9.x | Backup automático |

## Modelo de Dados (Conceitual)

### Diagrama ER Simplificado

```
┌─────────────────┐
│      User       │
├─────────────────┤
│ id              │
│ name            │
│ email           │
│ password        │
│ role            │
└────────┬────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐         ┌─────────────────┐
│     Client      │         │   ServiceType   │
├─────────────────┤         ├─────────────────┤
│ id              │         │ id              │
│ user_id (FK)    │         │ nome            │
│ nome            │         │ valor_padrao    │
│ tipo_pessoa     │         │ icone           │
│ cpf / cnpj      │         └────────┬────────┘
│ telefone        │                  │
│ email           │                  │ 1:N
│ endereco        │                  │
└────────┬────────┘                  │
         │                           │
         │ 1:N                       │
         ▼                           ▼
┌─────────────────────────────────────────────┐
│                  Service                     │
├─────────────────────────────────────────────┤
│ id                                          │
│ client_id (FK)                              │
│ service_type_id (FK)                        │
│ user_id (FK)                                │
│ titulo                                      │
│ descricao                                   │
│ local                                       │
│ data_agendada                               │
│ prazo_fatal                                 │
│ status (pendente/agendado/concluido/etc)    │
│ valor                                       │
└────────┬────────────────────────────────────┘
         │
         │ 1:1 (opcional)
         ▼
┌─────────────────────────────────────────────┐
│               CalendarEvent                  │
├─────────────────────────────────────────────┤
│ id                                          │
│ service_id (FK, nullable)                   │
│ user_id (FK)                                │
│ titulo                                      │
│ data_inicio / data_fim                      │
│ google_event_id                             │
│ lembretes_enviados                          │
└─────────────────────────────────────────────┘
         │
         │ 1:1
         ▼
┌─────────────────────────────────────────────┐
│                Financial                     │
├─────────────────────────────────────────────┤
│ id                                          │
│ service_id (FK, nullable)                   │
│ client_id (FK, nullable)                    │
│ user_id (FK)                                │
│ tipo (receita/despesa)                      │
│ categoria                                   │
│ valor                                       │
│ data_vencimento / data_pagamento            │
│ status (pendente/pago/atrasado)             │
│ forma_pagamento                             │
└─────────────────────────────────────────────┘
```

## Decisões Arquiteturais

### 1. Monolito Modular vs Microsserviços

**Decisão**: Monolito Modular

**Justificativa**:
- Equipe de uma pessoa (complexidade desnecessária com microsserviços)
- Performance superior para carga esperada (< 1000 usuários)
- Facilidade de manutenção e debug
- Deploy simplificado
- Transações ACID nativas

**Trade-offs aceitos**:
- Menos flexibilidade de escala horizontal
- Acoplamento maior entre módulos

### 2. FilamentPHP vs Laravel Nova vs Backpack

**Decisão**: FilamentPHP v3

**Justificativa**:
- Open source (sem custo de licença)
- Baseado em Livewire 3 (totalmente reativo)
- Componentes modernos (Tailwind CSS)
- CRUD gerado automaticamente
- Excelente documentação
- Comunidade ativa brasileira

**Recursos utilizados**:
- Resources (CRUD)
- Widgets (Dashboard)
- Forms (Formulários complexos)
- Tables (Listagens com filtros)
- Notifications (Alertas)
- Actions (Ações em lote)

### 3. Docker desde o início

**Decisão**: Containerização completa

**Justificativa**:
- Ambiente 100% replicável (dev = prod)
- Facilita migração futura para Raspberry Pi
- Isolamento de versões PHP (não conflita com outros sites)
- Volumes para persistência de dados
- Compose para orquestração simples

**Containers**:
- `app`: PHP 8.3-FPM + Laravel
- `nginx`: Web server / reverse proxy
- `mysql`: Banco de dados
- `redis`: Cache, sessions, queue
- `mailpit`: Teste de emails (dev only)

### 4. Cloudflare Tunnel vs Port Forwarding

**Decisão**: Cloudflare Tunnel (Zero Trust)

**Justificativa**:
- Zero portas abertas no roteador (segurança)
- SSL automático e gratuito
- Proteção DDoS incluída
- Cloudflare Access para 2FA adicional
- Funciona com IP dinâmico
- Oculta IP real do servidor

**Configuração**:
```
sistema.allissonsousa.adv.br → Tunnel → localhost:8080
```

### 5. MySQL vs PostgreSQL

**Decisão**: MySQL 8.0

**Justificativa**:
- Maior familiaridade
- Excelente suporte no Laravel
- Tipos espaciais nativos (ST_Distance_Sphere)
- Compatível com MariaDB (Raspberry Pi)
- Ferramentas de backup maduras

## Fluxo de Dados

### Criação de Serviço

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   Usuário    │───▶│  FilamentPHP │───▶│   Service    │
│  (Browser)   │    │   Resource   │    │    Model     │
└──────────────┘    └──────────────┘    └──────┬───────┘
                                               │
                    ┌──────────────────────────┼──────────────────────────┐
                    │                          │                          │
                    ▼                          ▼                          ▼
           ┌──────────────┐           ┌──────────────┐           ┌──────────────┐
           │   Observer   │           │  ActivityLog │           │    Event     │
           │(auto-create) │           │   (audit)    │           │  Listener    │
           └──────┬───────┘           └──────────────┘           └──────┬───────┘
                  │                                                      │
                  ▼                                                      ▼
           ┌──────────────┐                                     ┌──────────────┐
           │  Financial   │                                     │   Calendar   │
           │   Record     │                                     │    Sync      │
           └──────────────┘                                     └──────────────┘
```

## Segurança

### Autenticação
- FilamentPHP Auth (session-based)
- Rate limiting em login (5 tentativas/minuto)
- Password hashing (Bcrypt)
- Session timeout configurável

### Autorização
- Spatie Permission para RBAC
- Policies do Laravel
- Gate checks

### Proteção de Dados
- CSRF tokens em todos os forms
- XSS protection (escape automático Blade)
- SQL Injection prevention (Eloquent)
- Validação de input rigorosa

### Auditoria
- Activity Log para todas as ações CRUD
- IP e User Agent registrados
- Retenção de 90 dias

### LGPD
- Consentimento explícito no cadastro
- Exportação de dados do usuário
- Direito ao esquecimento (soft delete → hard delete)
- Logs de acesso a dados sensíveis

## Preparação para Escala Futura

### Fase 2: Integrações
- Google Calendar API
- WhatsApp Business API
- Asaas Payment Gateway
- Notificações Push (PWA)

### Fase 3: Marketplace
- Tabela `correspondents` (perfis de advogados parceiros)
- Tabela `service_orders` (ordens de serviço "cegas")
- Sistema de matching geográfico (MySQL Spatial)
- Chat sanitizado (anti-disintermediação)
- Split de pagamentos (Asaas)

### Fase 4: Multi-tenancy
- Avaliar `stancl/tenancy` ou `spatie/laravel-multitenancy`
- Separação por banco de dados ou schema
- White-label para escritórios parceiros

## Performance

### Caching Strategy
- Config/Route/View caching (produção)
- Redis para session e cache
- Query caching para dados frequentes
- Eager loading para evitar N+1

### Otimizações
- Nginx gzip compression
- Static assets com cache headers (30d)
- Lazy loading de imagens
- Database indexes estratégicos

### Monitoramento (futuro)
- Laravel Telescope (dev)
- Laravel Horizon (queue monitoring)
- Sentry (error tracking)

## Backup e Recuperação

### Estratégia
- Backup diário às 03:00
- Retenção: 7 dias local, 30 dias remoto
- Inclui: banco de dados, uploads, .env

### Comandos
```bash
# Backup manual
docker-compose exec app php artisan backup:run

# Listar backups
docker-compose exec app php artisan backup:list

# Limpar backups antigos
docker-compose exec app php artisan backup:clean
```

## Migração para Raspberry Pi

### Requisitos
- Raspberry Pi 4 (4GB+ RAM)
- SSD USB (não usar SD card para banco)
- Docker + Docker Compose instalados

### Adaptações necessárias
1. Trocar imagem MySQL por MariaDB (ARM nativo)
2. Reduzir workers PHP-FPM
3. Aumentar swap
4. Usar Redis como cache agressivamente

### docker-compose.override.yml (Pi)
```yaml
services:
  mysql:
    image: mariadb:10.11
  app:
    deploy:
      resources:
        limits:
          memory: 512M
```
