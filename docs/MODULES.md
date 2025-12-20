# Especificação dos Módulos

Este documento detalha cada módulo do sistema LogísticaJus.

---

## Módulo: Clientes

**Status**: Sprint 2  
**Responsável**: Administrador

### Descrição

Gerenciamento completo de clientes (pessoas físicas e jurídicas) que contratam os serviços do advogado correspondente.

### Campos

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| nome | string(255) | Sim | Nome completo ou razão social |
| tipo_pessoa | enum | Sim | PF (Pessoa Física) ou PJ (Pessoa Jurídica) |
| cpf | string(14) | Condicional | CPF formatado (apenas PF) |
| cnpj | string(18) | Condicional | CNPJ formatado (apenas PJ) |
| rg | string(20) | Não | RG com órgão emissor |
| telefone | string(15) | Sim | Telefone principal |
| telefone_secundario | string(15) | Não | Telefone alternativo |
| email | string(255) | Não | Email para contato |
| cep | string(9) | Não | CEP para busca automática |
| endereco | string(255) | Não | Logradouro |
| numero | string(10) | Não | Número |
| complemento | string(100) | Não | Complemento |
| bairro | string(100) | Não | Bairro |
| cidade | string(100) | Não | Cidade |
| estado | string(2) | Não | UF (sigla) |
| observacoes | text | Não | Notas internas |
| ativo | boolean | Sim | Cliente ativo/inativo |
| whatsapp_optin | boolean | Sim | Aceita receber WhatsApp |

### Relacionamentos

- **User**: Pertence a um usuário (N:1)
- **Services**: Possui muitos serviços (1:N)
- **Financials**: Possui muitos registros financeiros (1:N)
- **Media**: Possui muitos documentos anexos (1:N via Media Library)

### Validações

- CPF: Validação de dígitos verificadores
- CNPJ: Validação de dígitos verificadores
- Email: Formato válido
- Telefone: Formato brasileiro
- CEP: 8 dígitos numéricos

### Funcionalidades

1. **CRUD Completo**: Criar, visualizar, editar, excluir (soft delete)
2. **Busca CEP**: Preenchimento automático via ViaCEP
3. **Upload de Documentos**: Procurações, contratos, comprovantes
4. **Filtros**: Tipo pessoa, ativo/inativo, cidade, estado
5. **Busca Global**: Por nome, CPF, CNPJ, email
6. **Histórico**: Lista de serviços vinculados

---

## Módulo: Serviços

**Status**: Sprint 3  
**Responsável**: Administrador

### Descrição

Gerenciamento de todos os tipos de diligências e serviços jurídicos prestados.

### Tipos de Serviço (Tabela `service_types`)

| ID | Nome | Ícone | Valor Padrão |
|----|------|-------|--------------|
| 1 | Audiência | ⚖️ | R$ 150,00 |
| 2 | Despacho | 📋 | R$ 80,00 |
| 3 | Protocolo | 📄 | R$ 50,00 |
| 4 | Cópia de Processo | 📑 | R$ 40,00 |
| 5 | Visita Penitenciária | 🏛️ | R$ 200,00 |
| 6 | Assinatura de Procuração | ✍️ | R$ 100,00 |
| 7 | Diligência Externa | 🚗 | R$ 120,00 |
| 8 | Outros | 📌 | R$ 0,00 |

### Campos (Tabela `services`)

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| client_id | FK | Sim | Cliente vinculado |
| service_type_id | FK | Sim | Tipo de serviço |
| user_id | FK | Sim | Usuário responsável |
| titulo | string(255) | Sim | Título descritivo |
| descricao | text | Não | Detalhes do serviço |
| numero_processo | string(50) | Não | Número do processo (CNJ) |
| local | string(255) | Sim | Nome do local (Fórum, Cartório, etc) |
| endereco_completo | string(500) | Não | Endereço completo |
| data_agendada | date | Não | Data do serviço |
| hora_agendada | time | Não | Hora do serviço |
| prazo_fatal | date | Não | Prazo fatal (deadline) |
| status | enum | Sim | Status atual |
| valor | decimal(10,2) | Sim | Valor do serviço |
| valor_deslocamento | decimal(10,2) | Não | Valor adicional deslocamento |
| observacoes | text | Não | Notas internas |

### Status (Enum)

| Valor | Label | Cor |
|-------|-------|-----|
| pendente | Pendente | Cinza |
| agendado | Agendado | Azul |
| em_andamento | Em Andamento | Amarelo |
| concluido | Concluído | Verde |
| cancelado | Cancelado | Vermelho |

### Relacionamentos

- **Client**: Pertence a um cliente (N:1)
- **ServiceType**: Pertence a um tipo (N:1)
- **User**: Pertence a um usuário (N:1)
- **CalendarEvent**: Possui um evento de calendário (1:1)
- **Financial**: Possui um registro financeiro (1:1)
- **Media**: Possui documentos anexos (1:N)

### Funcionalidades

1. **CRUD Completo**: Com formulário intuitivo
2. **Kanban**: Visualização por status (arrastar e soltar)
3. **Listagem**: Com filtros avançados
4. **Valor Automático**: Preenche valor padrão do tipo
5. **Criação de Evento**: Ao salvar com data, cria evento na agenda
6. **Criação de Receita**: Ao concluir, gera registro financeiro
7. **Alertas**: Destaque para prazos fatais próximos

### Widgets Dashboard

- **Serviços Hoje**: Contador de serviços do dia
- **Próximos 7 Dias**: Lista dos próximos serviços
- **Prazos Fatais**: Alerta vermelho para prazos < 3 dias

---

## Módulo: Agenda

**Status**: Sprint 4  
**Responsável**: Administrador

### Descrição

Calendário integrado ao Google Calendar para visualização e sincronização de compromissos.

### Campos (Tabela `calendar_events`)

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| service_id | FK | Não | Serviço vinculado (se houver) |
| user_id | FK | Sim | Usuário proprietário |
| titulo | string(255) | Sim | Título do evento |
| descricao | text | Não | Descrição detalhada |
| local | string(255) | Não | Local do evento |
| data_inicio | datetime | Sim | Data/hora de início |
| data_fim | datetime | Sim | Data/hora de término |
| dia_inteiro | boolean | Sim | Evento de dia inteiro |
| google_event_id | string(255) | Não | ID do evento no Google |
| sincronizado_em | datetime | Não | Última sincronização |
| lembrete_24h_enviado | boolean | Sim | Flag de lembrete 24h |
| lembrete_1h_enviado | boolean | Sim | Flag de lembrete 1h |
| cor | string(7) | Não | Cor hexadecimal |

### Relacionamentos

- **Service**: Pertence a um serviço (N:1, opcional)
- **User**: Pertence a um usuário (N:1)

### Funcionalidades

1. **Visualização Calendário**: Mensal, semanal, diária
2. **Criar Evento Manual**: Sem vínculo com serviço
3. **Evento Automático**: Criado ao cadastrar serviço com data
4. **Sincronização Google**: Bidirecional
5. **Cores por Tipo**: Audiência=vermelho, Despacho=azul, etc
6. **Lembretes**: Email 24h e 1h antes
7. **Click para Detalhes**: Abre modal com informações

### Integração Google Calendar

- **OAuth 2.0**: Autenticação segura
- **Sync To Google**: Cria/atualiza evento no Google
- **Sync From Google**: Importa eventos do Google
- **Webhook**: (Futuro) Atualização em tempo real

---

## Módulo: Financeiro

**Status**: Sprint 5  
**Responsável**: Administrador

### Descrição

Controle de receitas e despesas, com geração automática a partir de serviços concluídos.

### Campos (Tabela `financials`)

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| service_id | FK | Não | Serviço vinculado |
| client_id | FK | Não | Cliente vinculado |
| user_id | FK | Sim | Usuário proprietário |
| tipo | enum | Sim | Receita ou Despesa |
| categoria | enum | Sim | Categoria do lançamento |
| descricao | string(255) | Sim | Descrição do lançamento |
| valor | decimal(10,2) | Sim | Valor monetário |
| data_competencia | date | Sim | Data de referência |
| data_vencimento | date | Sim | Data de vencimento |
| data_pagamento | date | Não | Data efetiva do pagamento |
| status | enum | Sim | Status do pagamento |
| forma_pagamento | enum | Não | Forma de pagamento |
| observacoes | text | Não | Notas internas |

### Tipo (Enum)

| Valor | Label |
|-------|-------|
| receita | Receita |
| despesa | Despesa |

### Categoria (Enum)

| Valor | Label | Tipo |
|-------|-------|------|
| honorarios | Honorários | Receita |
| deslocamento | Deslocamento | Receita |
| custas | Custas Processuais | Despesa |
| material | Material | Despesa |
| combustivel | Combustível | Despesa |
| estacionamento | Estacionamento | Despesa |
| alimentacao | Alimentação | Despesa |
| outros | Outros | Ambos |

### Status (Enum)

| Valor | Label | Cor |
|-------|-------|-----|
| pendente | Pendente | Amarelo |
| pago | Pago | Verde |
| atrasado | Atrasado | Vermelho |
| cancelado | Cancelado | Cinza |

### Forma de Pagamento (Enum)

| Valor | Label |
|-------|-------|
| pix | PIX |
| dinheiro | Dinheiro |
| transferencia | Transferência Bancária |
| boleto | Boleto |
| cartao_credito | Cartão de Crédito |
| cartao_debito | Cartão de Débito |

### Relacionamentos

- **Service**: Pertence a um serviço (N:1, opcional)
- **Client**: Pertence a um cliente (N:1, opcional)
- **User**: Pertence a um usuário (N:1)
- **Media**: Possui comprovantes anexos (1:N)

### Funcionalidades

1. **CRUD Completo**: Com formulário intuitivo
2. **Tabs**: Todas, Receitas, Despesas, Pendentes, Atrasadas
3. **Filtros**: Período, categoria, cliente, status
4. **Ação em Lote**: Marcar múltiplos como pago
5. **Receita Automática**: Gerada ao concluir serviço
6. **Upload Comprovante**: Anexar recibos/notas
7. **Relatórios PDF**: Extrato mensal, por cliente

### Widgets Dashboard

- **Faturamento do Mês**: Gráfico de receitas
- **A Receber**: Total pendente
- **Inadimplência**: Atrasados > 30 dias
- **Balanço**: Receitas - Despesas

### Relatórios

1. **Extrato Mensal**
   - Período selecionável
   - Receitas x Despesas
   - Balanço final
   - Lista detalhada

2. **Relatório por Cliente**
   - Serviços prestados
   - Valores recebidos
   - Valores pendentes

3. **Comprovante de Serviço**
   - Dados do cliente
   - Descrição do serviço
   - Valor e data
   - Assinatura digital (futuro)

---

## Observadores (Observers)

### ServiceObserver

Monitora mudanças na entidade `Service`.

```php
// Ao criar serviço com data agendada
public function created(Service $service)
{
    if ($service->data_agendada) {
        CalendarEvent::create([
            'service_id' => $service->id,
            'titulo' => $service->titulo,
            'data_inicio' => $service->data_agendada . ' ' . ($service->hora_agendada ?? '09:00'),
            // ...
        ]);
    }
}

// Ao concluir serviço
public function updated(Service $service)
{
    if ($service->wasChanged('status') && $service->status === 'concluido') {
        Financial::create([
            'service_id' => $service->id,
            'client_id' => $service->client_id,
            'tipo' => 'receita',
            'categoria' => 'honorarios',
            'valor' => $service->valor + ($service->valor_deslocamento ?? 0),
            'status' => 'pendente',
            // ...
        ]);
    }
}
```

---

## Próximos Módulos (Fase 2+)

### WhatsApp Notifications
- Templates aprovados Meta
- Envio automático de lembretes
- Confirmação de recebimento

### Asaas Payments
- Geração de cobranças PIX/Boleto
- Webhooks para atualização automática
- QR Code PIX inline

### Correspondentes (Marketplace)
- Perfis de advogados parceiros
- Validação OAB
- Geolocalização
- Sistema de avaliação
