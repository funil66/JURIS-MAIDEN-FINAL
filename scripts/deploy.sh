#!/bin/bash
# ============================================
# LogísticaJus - Script de Deploy para Produção
# ============================================

set -e

echo "================================================"
echo "  LogísticaJus - Deploy para Produção"
echo "================================================"
echo ""

cd "$(dirname "$0")/.."

# 1. Verificar se estamos no diretório correto
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Erro: Execute este script do diretório raiz do projeto"
    exit 1
fi

# 2. Fazer backup do banco de dados
echo "📦 Criando backup do banco de dados..."
docker exec logisticajus_app php artisan backup:run --only-db 2>/dev/null || echo "⚠️ Backup pulado (primeiro deploy)"

# 3. Parar containers
echo "🛑 Parando containers..."
docker-compose down

# 4. Copiar configurações de produção
echo "📋 Aplicando configurações de produção..."
if [ -f "src/.env.production" ]; then
    cp src/.env src/.env.backup
    cp src/.env.production src/.env
    echo "✅ .env.production aplicado"
fi

# 5. Rebuild dos containers
echo "🔨 Reconstruindo containers..."
docker-compose build --no-cache

# 6. Iniciar containers
echo "🚀 Iniciando containers..."
docker-compose up -d

# 7. Aguardar containers ficarem prontos
echo "⏳ Aguardando containers..."
sleep 10

# 8. Executar migrações
echo "📊 Executando migrações..."
docker exec logisticajus_app php artisan migrate --force

# 9. Limpar e otimizar cache
echo "⚡ Otimizando aplicação..."
docker exec logisticajus_app php artisan optimize
docker exec logisticajus_app php artisan filament:cache-components
docker exec logisticajus_app php artisan icons:cache
docker exec logisticajus_app php artisan view:cache

# 10. Verificar status
echo ""
echo "================================================"
echo "  ✅ Deploy concluído!"
echo "================================================"
echo ""
echo "📋 Status dos containers:"
docker-compose ps
echo ""
echo "🌐 Sistema disponível em: https://sistema.allissonsousa.adv.br"
echo ""
