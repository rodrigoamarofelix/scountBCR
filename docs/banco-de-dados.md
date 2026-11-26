# Cuidado com o banco de dados

## O que APAGA tudo (partidas, relatórios, cadastros)

```bash
# NUNCA use em ambiente com dados reais sem backup:
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan db:wipe
```

Esses comandos **dropam todas as tabelas**. Não há como recuperar partidas antigas sem um backup do PostgreSQL.

## Se só não consegue logar (painel vazio / 403)

```bash
docker compose exec app php artisan scount:ensure-access
```

## Se o banco está vazio e você quer só a demonstração de novo

```bash
docker compose exec app php artisan scount:ensure-access
docker compose exec app php artisan scount:seed-demo
```

## Backup (faça antes de manutenção)

```bash
docker compose exec db pg_dump -U scount scountbcr > backup_$(date +%Y%m%d).sql
```

## Restaurar backup

```bash
cat backup_XXXX.sql | docker compose exec -T db psql -U scount scountbcr
```