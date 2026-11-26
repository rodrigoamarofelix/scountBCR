# ScountBCR — Sistema de Scouting Estatístico de Basquete

[![CI](https://github.com/rodrigoamarofelix/scountBCR/actions/workflows/ci.yml/badge.svg)](https://github.com/rodrigoamarofelix/scountBCR/actions/workflows/ci.yml)

Stack: Laravel 11, Filament 3, Livewire 3, PostgreSQL, Docker/Nginx.

## Subir ambiente

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install && npm run build
```

Acesso: http://localhost:8082 — Painel admin: `/admin`

## Portas

| Serviço | Porta host |
|---------|------------|
| Nginx   | 8082       |
| Postgres| 5435       |
| PHP-FPM | 9002       |
| Vite    | 5174       |

## Acesso rápido (após subir)

- **App (Breeze):** http://localhost:8082/login  
- **Painel Filament:** http://localhost:8082/admin  
- **Usuário inicial:** `admin@scountbcr.local` / `password` (alterar em produção)


## Integração contínua

A cada push ou pull request na branch `main`, o GitHub Actions executa:

```bash
php artisan test --testsuite=Domain
```

Workflow: [.github/workflows/ci.yml](.github/workflows/ci.yml)

## Documentação

Ver [ARQUITETURA.md](./ARQUITETURA.md) para diagnóstico, modelagem e roadmap por etapas.

## Etapa 1 — entregue

- Docker (Nginx, PHP 8.3, PostgreSQL 16)
- Laravel 11, Breeze, Filament 3, Shield (perfis Spatie)
- Migrations do domínio + `shot_attempts` com coordenadas para shot chart futuro
- Models, enums, `ShotStatisticsService`
- Seeder de perfis e administrador

**Etapas 2–5:** cadastros, scouting ao vivo, dashboard/comparativo, relatórios PDF/Excel.
## Etapa 6 — entregue

### Permissões (Shield)
- `php artisan shield:generate --all --panel=admin --option=permissions --no-interaction`
- Papéis: `super_admin`, `administrador`, `treinador`, `analista`, `consulta`
- Seeder `ShieldRolePermissionsSeeder` (roda no `db:seed`)
- Páginas de análise protegidas com `HasPageShield`
- Exportação PDF/Excel exige `page_ReportsHub` (ou perfil do atleta)

### Testes
```bash
docker compose exec app php artisan test
```
- SQLite em memória (`phpunit.xml`)
- Unit: `ShotStatisticsService`
- Feature: `MatchScoutingService`, `GameMatchPolicy`

### Docker
- Imagem PHP inclui extensão **gd** (relatórios Excel)
- Após mudar `php/Dockerfile`: `docker compose build app && docker compose up -d`

### Checklist rápido
```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
docker compose exec app npm run build
docker compose exec app php artisan test
```
