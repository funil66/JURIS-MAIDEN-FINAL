# ⚖️ JURIS MAIDEN

**Sistema de Gestão Jurídica — JURIS MAIDEN (Allisson Sousa Advocacia)**

Um sistema completo para organização de trabalho de advogados correspondentes jurídicos, incluindo gerenciamento de clientes, serviços, agenda, financeiro e relatórios.

---

## 📋 Índice

- [Funcionalidades](#-funcionalidades)
- [Tecnologias](#-tecnologias)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Acesso ao Sistema](#-acesso-ao-sistema)
- [Módulos](#-módulos)
- [Comandos Úteis](#-comandos-úteis)
- [Deploy em Produção](#-deploy-em-produção)
- [Estrutura do Projeto](#-estrutura-do-projeto)

---

## 🚀 Funcionalidades

### Módulo de Clientes
- Cadastro de Pessoa Física (CPF) e Jurídica (CNPJ)
- Validação automática de CPF/CNPJ
- Histórico de atividades
- Soft delete (lixeira)

### Módulo de Serviços
- 7 tipos de serviço pré-configurados (Audiência, Protocolo, Diligência, etc.)
- Geração automática de código (SRV-YYYY-NNNN)
- Vinculação com clientes
- Controle de status e valores

### Módulo de Agenda
- Calendário interativo (FullCalendar)
- Eventos e compromissos
- Visualização de serviços no calendário
- Drag-and-drop para reagendamento

### Módulo Financeiro
- Receitas e Despesas
- Múltiplas formas de pagamento
- Controle de parcelas
- Alertas de vencimento e atraso

### Módulo de Relatórios
- Relatório de Serviços (PDF)
- Relatório de Clientes (PDF)
- Relatório Financeiro (PDF)
- Relatório Geral (PDF)
- Exportação Excel/CSV

### Sistema de Notificações
- Lembretes de serviços agendados
- Alertas de pagamentos próximos ao vencimento
- Notificações de pagamentos atrasados
- Notificações no painel + Email

### Backup Automático
- Backup diário do banco de dados
- Backup semanal completo
- Limpeza automática de backups antigos

### PWA (Progressive Web App)
- Instalável no celular/desktop
- Funciona offline (básico)
- Ícone na tela inicial

---

## 🛠 Tecnologias

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| Laravel | 12.x | Framework PHP |
| FilamentPHP | 3.x | Painel administrativo |
| MySQL | 8.0 | Banco de dados |
| Redis | Alpine | Cache e sessões |
| Nginx | Alpine | Servidor web |
| Docker | 28.x | Containerização |
| Spatie Packages | - | Permissões, Activity Log, Backup |
| DomPDF | - | Geração de PDFs |
| Laravel Excel | - | Exportação Excel/CSV |

---

## 📦 Requisitos

- Docker e Docker Compose
- Git
- 2GB RAM mínimo
- 10GB de espaço em disco

---

## 🔧 Instalação

### 1. Clone o repositório
```bash
git clone <seu-repositorio>
cd logisticajus
```

### 2. Inicie os containers
```bash
docker-compose up -d
```

### 3. Instale as dependências
```bash
docker exec logisticajus_app composer install
```

### 4. Configure o ambiente
```bash
docker exec logisticajus_app cp .env.example .env
docker exec logisticajus_app php artisan key:generate
```

### 5. Execute as migrações
```bash
docker exec logisticajus_app php artisan migrate --seed
```

### 6. Crie o usuário admin
```bash
docker exec logisticajus_app php artisan make:filament-user
```

### 7. Crie o link de storage
```bash
docker exec logisticajus_app php artisan storage:link
```

---

## 🌐 Acesso ao Sistema

| Serviço | URL | Descrição |
|---------|-----|-----------|
| Sistema | http://localhost:8080/funil | Painel principal |
| Mailpit | http://localhost:8025 | Visualizar emails (dev) |

### Credenciais padrão
- **Email:** allissonsousa.adv@gmail.com
- **Senha:** Configurada durante a instalação

---

## 📁 Módulos

### Navegação do Sistema

```
🏠 Dashboard
   ├── Estatísticas gerais
   ├── Próximos eventos
   ├── Serviços pendentes
   └── Gráfico financeiro

👥 Clientes
   └── CRUD completo com validação CPF/CNPJ

📋 Serviços
   ├── Serviços (cadastro e acompanhamento)
   └── Tipos de Serviço (configuração)

📅 Agenda
   └── Calendário interativo

💰 Financeiro
   ├── Transações (receitas/despesas)
   └── Métodos de Pagamento

📊 Relatórios
   └── Geração PDF/Excel/CSV

⚙️ Configurações
   └── Notificações, Backup, Sistema
```

---

## 💻 Comandos Úteis

### Containers Docker
```bash
# Iniciar
docker-compose up -d

# Parar
docker-compose down

# Ver logs
docker-compose logs -f

# Acessar container
docker exec -it logisticajus_app bash
```

### Artisan
```bash
# Limpar cache
docker exec logisticajus_app php artisan optimize:clear

# Migrações
docker exec logisticajus_app php artisan migrate

# Seeders
docker exec logisticajus_app php artisan db:seed

# Backup manual
docker exec logisticajus_app php artisan backup:run --only-db
```

### Lembretes manuais
```bash
# Serviços para amanhã
docker exec logisticajus_app php artisan services:send-reminders --days=1

# Pagamentos próximos
docker exec logisticajus_app php artisan payments:send-reminders --days=3
```

---

## 🚀 Deploy em Produção

### 1. Configure o Cloudflare Tunnel
```bash
./scripts/setup-cloudflare.sh
```

### 2. Configure as variáveis de produção
```bash
cp src/.env.production src/.env
# Edite o .env com suas configurações reais
```

### 3. Execute o deploy
```bash
./scripts/deploy.sh
```

### 4. Configure o crontab
```bash
crontab -e
# Adicione:
* * * * * cd /caminho/para/logisticajus/src && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📂 Estrutura do Projeto

```
logisticajus/
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile
├── scripts/
│   ├── deploy.sh
│   └── setup-cloudflare.sh
├── src/
│   ├── app/
│   │   ├── Console/Commands/
│   │   ├── Exports/
│   │   ├── Filament/
│   │   │   ├── Pages/
│   │   │   ├── Resources/
│   │   │   └── Widgets/
│   │   ├── Models/
│   │   ├── Notifications/
│   │   ├── Providers/
│   │   └── Rules/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/
│   │   ├── manifest.json
│   │   ├── sw.js
│   │   └── offline.html
│   ├── resources/views/
│   │   ├── filament/pages/
│   │   └── reports/
│   ├── routes/
│   └── storage/
├── docker-compose.yml
├── cloudflared-config.yml
└── README.md
```

---

## 📊 Resumo dos Sprints

| Sprint | Módulo | Status |
|--------|--------|--------|
| 1 | Infraestrutura Docker/Laravel/Filament | ✅ |
| 2 | Módulo de Clientes | ✅ |
| 3 | Módulo de Serviços | ✅ |
| 4 | Módulo de Agenda | ✅ |
| 5 | Módulo Financeiro | ✅ |
| 6 | Relatórios (PDF/Excel/CSV) | ✅ |
| 7 | Sistema de Notificações | ✅ |
| 8 | Cloudflare Tunnel/Produção | ✅ |
| 9 | PWA/Mobile | ✅ |
| 10 | Backup Automático | ✅ |

---

## 👨‍💻 Desenvolvido para

**Allisson Sousa**  
Advogado Correspondente  
📧 allissonsousa.adv@gmail.com  
🌐 sistema.allissonsousa.adv.br

---

*Versão 1.0 - Dezembro 2025*
