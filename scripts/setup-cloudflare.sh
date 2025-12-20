#!/bin/bash
# ============================================
# LogísticaJus - Script de Instalação Cloudflare Tunnel
# ============================================
# Este script configura o Cloudflare Tunnel para expor
# o sistema em sistema.allissonsousa.adv.br
#
# Pré-requisitos:
# 1. Conta Cloudflare com domínio allissonsousa.adv.br configurado
# 2. Docker rodando
# ============================================

set -e

echo "================================================"
echo "  LogísticaJus - Configuração Cloudflare Tunnel"
echo "================================================"
echo ""

# Verificar se cloudflared está instalado
if ! command -v cloudflared &> /dev/null; then
    echo "📥 Instalando cloudflared..."
    
    # Baixar e instalar cloudflared
    curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
    sudo dpkg -i cloudflared.deb
    rm cloudflared.deb
    
    echo "✅ cloudflared instalado com sucesso!"
else
    echo "✅ cloudflared já está instalado"
fi

echo ""
echo "📋 Próximos passos:"
echo ""
echo "1. Autenticar com Cloudflare:"
echo "   cloudflared tunnel login"
echo ""
echo "2. Criar o túnel:"
echo "   cloudflared tunnel create logisticajus"
echo ""
echo "3. Configurar o DNS (automático):"
echo "   cloudflared tunnel route dns logisticajus sistema.allissonsousa.adv.br"
echo ""
echo "4. Copiar o arquivo de configuração:"
echo "   cp cloudflared-config.yml ~/.cloudflared/config.yml"
echo ""
echo "5. Iniciar o túnel:"
echo "   cloudflared tunnel run logisticajus"
echo ""
echo "6. (Opcional) Instalar como serviço:"
echo "   sudo cloudflared service install"
echo ""
echo "================================================"
