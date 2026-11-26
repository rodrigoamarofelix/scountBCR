# Documentação ScountBCR

| Arquivo | Descrição |
|---------|-----------|
| [ScountBCR-Manual.pdf](./ScountBCR-Manual.pdf) | Manual completo (operação, Docker, Shield, relatórios, troubleshooting) |

## Regenerar o PDF

Na raiz do projeto (`scountBCR/`):

```bash
docker compose exec app php artisan scount:docs-pdf
```

O comando grava `docs/ScountBCR-Manual.pdf` a partir do template em `src/resources/views/docs/manual.blade.php`.

## Atualizar o conteúdo

Edite `src/resources/views/docs/manual.blade.php` e rode o comando acima.