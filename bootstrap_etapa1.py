# Bootstrap script — run: wsl python3 /mnt/c/Users/Rodrigo/Projects/real/real_app/_scountbcr_bootstrap.py
from pathlib import Path

SRC = Path.home() / "projetos/scountBCR/src"
APP = SRC / "app"

enums = {
    "ShotType.php": """<?php

namespace App\\Enums;

enum ShotType: string
{
    case TwoPoint = '2PT';
    case ThreePoint = '3PT';
    case FreeThrow = 'FT';

    public function label(): string
    {
        return match ($this) {
            self::TwoPoint => '2 pontos',
            self::ThreePoint => '3 pontos',
            self::FreeThrow => 'Lance livre',
        };
    }

    public function pointsValue(): int
    {
        return match ($this) {
            self::TwoPoint => 2,
            self::ThreePoint => 3,
            self::FreeThrow => 1,
        };
    }
}
""",
    "ShotResult.php": """<?php

namespace App\\Enums;

enum ShotResult: string
{
    case Made = 'made';
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::Made => 'Convertido',
            self::Missed => 'Não convertido',
        };
    }
}
""",
    "MatchStatus.php": """<?php

namespace App\\Enums;

enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::InProgress => 'Em andamento',
            self::Finished => 'Finalizada',
        };
    }
}
""",
    "BasketballPosition.php": """<?php

namespace App\\Enums;

enum BasketballPosition: string
{
    case PointGuard = 'armador';
    case ShootingGuard = 'ala_armador';
    case SmallForward = 'ala';
    case PowerForward = 'ala_pivo';
    case Center = 'pivo';

    public function label(): string
    {
        return match ($this) {
            self::PointGuard => 'Armador',
            self::ShootingGuard => 'Ala-armador',
            self::SmallForward => 'Ala',
            self::PowerForward => 'Ala-pivô',
            self::Center => 'Pivô',
        };
    }
}
""",
}

enum_dir = APP / "Enums"
enum_dir.mkdir(parents=True, exist_ok=True)
for name, body in enums.items():
    (enum_dir / name).write_text(body)

migs = {
    "2025_11_25_100001_create_teams_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 20);
            $table->string('logo_path')->nullable();
            $table->string('category')->nullable();
            $table->string('head_coach')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
""",
    "2025_11_25_100002_create_opponents_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opponents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 20)->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opponents');
    }
};
""",
    "2025_11_25_100003_create_competitions_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('season')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
""",
    "2025_11_25_100004_create_athletes_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('cpf', 14)->unique();
            $table->string('sport_name')->nullable();
            $table->unsignedSmallInteger('jersey_number')->nullable();
            $table->decimal('functional_classification', 2, 1)->nullable();
            $table->string('photo_path')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};
""",
    "2025_11_25_100005_create_matches_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opponent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->date('match_date');
            $table->time('match_time')->nullable();
            $table->string('location')->nullable();
            $table->string('phase')->nullable();
            $table->string('match_type')->nullable();
            $table->unsignedSmallInteger('team_score')->default(0);
            $table->unsignedSmallInteger('opponent_score')->default(0);
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'match_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
""",
    "2025_11_25_100006_create_match_athlete_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_athlete', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_starter')->default(false);
            $table->timestamps();

            $table->unique(['match_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_athlete');
    }
};
""",
    "2025_11_25_100007_create_shot_attempts_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shot_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->string('shot_type');
            $table->string('result');
            $table->unsignedTinyInteger('period')->default(1);
            $table->decimal('court_x', 5, 2)->nullable()->comment('Coordenada X na quadra (shot chart)');
            $table->decimal('court_y', 5, 2)->nullable()->comment('Coordenada Y na quadra (shot chart)');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['match_id', 'athlete_id']);
            $table->index(['match_id', 'shot_type']);
            $table->index(['athlete_id', 'shot_type', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shot_attempts');
    }
};
""",
    "2025_11_25_100008_create_team_user_table.php": """<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
""",
}

mig_dir = SRC / "database/migrations"
for name, body in migs.items():
    (mig_dir / name).write_text(body)

print("OK enums", len(enums), "migrations", len(migs))