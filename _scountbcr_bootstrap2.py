from pathlib import Path

SRC = Path.home() / "projetos/scountBCR/src"
APP = SRC / "app"

models = {
    "Team.php": """<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'short_name', 'logo_path', 'category', 'head_coach', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
""",
    "Opponent.php": """<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Opponent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'logo_path'];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }
}
""",
    "Competition.php": """<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Competition extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'season', 'description'];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }
}
""",
    "Athlete.php": """<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'full_name', 'cpf', 'sport_name', 'jersey_number', 'functional_classification',
        'photo_path', 'birth_date', 'height_cm', 'weight_kg', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'functional_classification' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function shotAttempts(): HasMany
    {
        return $this->hasMany(ShotAttempt::class);
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(GameMatch::class, 'match_athlete')
            ->withPivot('is_starter')
            ->withTimestamps();
    }
}
""",
    "GameMatch.php": """<?php

namespace App\\Models;

use App\\Enums\\MatchStatus;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'team_id', 'opponent_id', 'competition_id', 'match_date', 'match_time', 'location',
        'phase', 'match_type', 'team_score', 'opponent_score', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'date',
            'status' => MatchStatus::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Opponent::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'match_athlete', 'match_id', 'athlete_id')
            ->withPivot('is_starter')
            ->withTimestamps();
    }

    public function shotAttempts(): HasMany
    {
        return $this->hasMany(ShotAttempt::class, 'match_id');
    }
}
""",
    "ShotAttempt.php": """<?php

namespace App\\Models;

use App\\Enums\\ShotResult;
use App\\Enums\\ShotType;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\SoftDeletes;

class ShotAttempt extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'match_id', 'athlete_id', 'shot_type', 'result', 'period', 'court_x', 'court_y',
        'recorded_by', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'shot_type' => ShotType::class,
            'result' => ShotResult::class,
            'occurred_at' => 'datetime',
            'court_x' => 'decimal:2',
            'court_y' => 'decimal:2',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
""",
}

model_dir = APP / "Models"
for name, body in models.items():
    (model_dir / name).write_text(body)

svc_dir = APP / "Services"
svc_dir.mkdir(parents=True, exist_ok=True)
(svc_dir / "ShotStatisticsService.php").write_text("""<?php

namespace App\\Services;

use App\\Enums\\ShotResult;
use App\\Enums\\ShotType;
use App\\Models\\Athlete;
use App\\Models\\GameMatch;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Support\\Collection;

class ShotStatisticsService
{
    public function queryForContext(?GameMatch $match = null, ?Athlete $athlete = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Builder
    {
        $query = \\App\\Models\\ShotAttempt::query();

        if ($match) {
            $query->where('match_id', $match->id);
        }

        if ($athlete) {
            $query->where('athlete_id', $athlete->id);
        }

        if ($from) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('occurred_at', '<=', $to);
        }

        return $query;
    }

    public function summarize(Builder $query): array
    {
        $attempts = (clone $query)->get();

        return [
            'two_point' => $this->summarizeType($attempts, ShotType::TwoPoint),
            'three_point' => $this->summarizeType($attempts, ShotType::ThreePoint),
            'free_throw' => $this->summarizeType($attempts, ShotType::FreeThrow),
            'overall' => $this->summarizeOverall($attempts),
        ];
    }

  private function summarizeType(Collection $attempts, ShotType $type): array
    {
        $subset = $attempts->where('shot_type', $type);
        $made = $subset->where('result', ShotResult::Made)->count();
        $missed = $subset->where('result', ShotResult::Missed)->count();
        $total = $made + $missed;

        return [
            'attempts' => $total,
            'made' => $made,
            'missed' => $missed,
            'points' => $made * $type->pointsValue(),
            'percentage' => $this->percentage($made, $total),
        ];
    }

    private function summarizeOverall(Collection $attempts): array
    {
        $made = $attempts->where('result', ShotResult::Made)->count();
        $total = $attempts->count();
        $points = $attempts
            ->where('result', ShotResult::Made)
            ->sum(fn ($row) => $row->shot_type->pointsValue());

        return [
            'attempts' => $total,
            'made' => $made,
            'missed' => $total - $made,
            'points' => $points,
            'percentage' => $this->percentage($made, $total),
        ];
    }

    public function percentage(int $made, int $attempts): ?float
    {
        if ($attempts === 0) {
            return null;
        }

        return round(($made / $attempts) * 100, 1);
    }
}
""")

(user_path := model_dir / "User.php")
user_body = user_path.read_text()
if "HasRoles" not in user_body:
    user_body = user_body.replace(
        "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Foundation\\Auth\\User as Authenticatable;",
        "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;\nuse Illuminate\\Foundation\\Auth\\User as Authenticatable;\nuse Spatie\\Permission\\Traits\\HasRoles;",
    )
    user_body = user_body.replace("    use HasFactory, Notifiable;", "    use HasFactory, HasRoles, Notifiable;")
    if "teams(): BelongsToMany" not in user_body:
        insert = """
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }
"""
        user_body = user_body.replace("\n    protected function casts(): array", f"\n{insert}\n    protected function casts(): array")
    user_path.write_text(user_body)

seed_path = SRC / "database/seeders/RoleAndAdminSeeder.php"
seed_path.write_text("""<?php

namespace Database\\Seeders;

use App\\Models\\User;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\Hash;
use Spatie\\Permission\\Models\\Role;

class RoleAndAdminSeeder extends Seeder
{
    public const ROLES = [
        'administrador',
        'treinador',
        'analista',
        'consulta',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@scountbcr.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        $admin->syncRoles(['administrador']);
    }
}
""")

db_seeder = SRC / "database/seeders/DatabaseSeeder.php"
text = db_seeder.read_text()
if "RoleAndAdminSeeder" not in text:
    text = text.replace(
        "        // User::factory(10)->create();\n",
        "        $this->call([\n            RoleAndAdminSeeder::class,\n        ]);\n\n        // User::factory(10)->create();\n",
    )
    db_seeder.write_text(text)

arch = Path.home() / "projetos/scountBCR/ARQUITETURA.md"
arch.write_text("""# Diagnóstico e proposta — ScountBCR

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
""")

print("models, service, seeders, architecture doc OK")