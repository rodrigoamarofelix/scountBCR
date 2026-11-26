# Diagnóstico e proposta — ScountBCR

## Diagnóstico

| Item | Situação |
|------|----------|
| Pasta `scountBCR` | Projeto novo (vazio no início desta etapa) |
| Projeto legado `bcrScount` | Laravel 11 + Filament + Shield + Docker similar; referência de stack |
| WSL PHP local | 8.1 — desenvolvimento via Docker PHP 8.3 |
| Workspace Cursor (`real_app`) | Outro projeto (Godot); sem integração direta |

## Stack adotada

- Laravel 11, Filament 3 (painel `/admin`), Livewire 3
- Laravel Breeze (auth web: login, registro, senha)
- Filament Shield + Spatie Permission (perfis)
- PostgreSQL 16, Nginx, PHP-FPM 8.3 (Docker Compose)

## Portas (evitam conflito com `bcrScount`)

| Serviço | Porta |
|---------|-------|
| HTTP | 8082 |
| Postgres | 5435 |
| PHP-FPM | 9002 |
| Vite | 5174 |

## Estrutura de diretórios (resumo)

```
scountBCR/
├── docker-compose.yml, nginx/, php/
├── ARQUITETURA.md
└── src/                        # Laravel
    ├── app/
    │   ├── Enums/              # ShotType, ShotResult, MatchStatus, ...
    │   ├── Models/             # Team, Athlete, GameMatch, ShotAttempt, ...
    │   ├── Services/           # ShotStatisticsService (cálculos)
    │   ├── Filament/           # Resources/Pages/Widgets (etapas seguintes)
    │   ├── Livewire/           # Tela ao vivo de scouting (etapa 3)
    │   └── Policies/           # Controle por equipe (etapas seguintes)
    ├── database/migrations/    # Domínio + Spatie Permission (Shield)
    └── resources/views/        # Breeze + layouts customizados
```

## Modelagem (Etapa 1)

- `teams`, `athletes`, `opponents`, `competitions`, `matches`
- `match_athlete`, `shot_attempts` (eventos individuais + `court_x`/`court_y` para shot chart futuro)
- `team_user` (treinador/analista vinculado à equipe)

## Perfis iniciais

`administrador`, `treinador`, `analista`, `consulta` (seeder)

## Telas priorizadas (sugestão + prompt)

1. **Dashboard estatístico** — Filament + ApexCharts (etapa 4)
2. **Scouting ao vivo** — Livewire full-screen, botões grandes (etapa 3)
3. **Comparativo de atletas** — radar/barras (etapa 4)

## Etapa atual: 1 — concluída neste commit

- Infra Docker, Laravel, Breeze, Filament, Shield
- Migrations do domínio, models, enums, serviço de estatísticas base
- Usuário admin seed: `admin@scountbcr.local` / `password` (trocar em produção)

## Próxima etapa (2)

Filament Resources: equipes, atletas, competições, partidas, convocação por jogo.

## Etapa 2 — concluída

Resources Filament (menu **Cadastros** e **Competição**):

- **Equipes** — logo, categoria, técnico, situação
- **Adversários**
- **Competições**
- **Atletas** — CPF, classificação funcional, posição, foto
- **Partidas** — aba **Atletas convocados** (vincula atletas da equipe, flag titular)

Comandos executados: `storage:link`, `shield:generate --all --panel=admin`.

**Etapa 3:** tela Livewire de scouting ao vivo.
## Etapa 4A/4B — concluída

- Dashboard estatístico (cards, 3 gráficos, filtros equipe/atleta/período)
- Comparativo de atletas (tabela, barras, radar)

## Etapa 5 — concluída

- Central de relatórios (PDF inline, Excel)
- Perfil estatístico do atleta no Filament

## Etapa 6 — concluída

- Shield: permissões por página/recurso + seeder de papéis
- `GameMatchPolicy` (scout bloqueado para `consulta`)
- Testes automatizados do domínio (estatísticas, scouting, policy)
- Docker PHP com extensão `gd` para Excel
