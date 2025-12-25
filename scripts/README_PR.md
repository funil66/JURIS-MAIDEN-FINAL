# Criar PR automaticamente

Este script ajuda a empurrar o branch atual e criar um PR no GitHub.

1. Torne o script executável:

```bash
chmod +x scripts/push_and_create_pr.sh
```

2. Execute o script (padrões: branch `chore/stabilize-dashboard-agenda-tests`, base `main`):

```bash
./scripts/push_and_create_pr.sh
```

3. Opções:
- Se você tiver o `gh` (GitHub CLI) instalado, o script usará ele para criar o PR automaticamente.
- Caso não tenha `gh`, mas tiver a variável de ambiente `GITHUB_TOKEN` configurada (com scope `repo`), o script usará a API do GitHub para criar o PR.
- Caso contrário, o branch será empurrado e o script fornecerá a URL para você criar o PR manualmente.

Segurança: Nunca cole tokens no chat público. Use a variável de ambiente `GITHUB_TOKEN` localmente ou o `gh auth login`.
