#!/bin/bash

#--------------------------------------------------------------
# Script de Instalação do Docker para Ubuntu/Debian
# LogísticaJus - Ambiente de Desenvolvimento
#--------------------------------------------------------------

set -e

echo "=== Instalando Docker ==="

# Atualizar pacotes
sudo apt-get update

# Instalar dependências
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# Adicionar chave GPG oficial do Docker
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Adicionar repositório Docker
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Atualizar e instalar Docker
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Adicionar usuário atual ao grupo docker (evita usar sudo)
sudo usermod -aG docker $USER

echo ""
echo "=== Docker instalado com sucesso! ==="
echo ""
echo "IMPORTANTE: Faça logout e login novamente para aplicar as permissões do grupo docker."
echo "Ou execute: newgrp docker"
echo ""
echo "Após isso, execute:"
echo "  cd '/home/funil/Área de trabalho/VISUAL CODE/PROJETO 1/logisticajus'"
echo "  docker compose up -d --build"
echo ""
