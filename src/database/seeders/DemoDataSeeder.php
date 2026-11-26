<?php

namespace Database\Seeders;

use App\Enums\BasketballPosition;
use App\Enums\MatchStatus;
use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Models\Athlete;
use App\Models\ShotAttempt;
use App\Models\User;
use App\Services\ShotChartService;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Opponent;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $competition = Competition::firstOrCreate(
            ['name' => 'Liga Municipal de Basquete'],
            [
                'season' => (string) now()->year,
                'description' => 'Competição de demonstração para testes do ScountBCR.',
            ]
        );

        $teamA = Team::firstOrCreate(
            ['short_name' => 'EQA'],
            [
                'name' => 'Equipe A',
                'category' => 'Adulto',
                'head_coach' => 'Carlos Mendes',
                'description' => 'Equipe de demonstração A.',
                'is_active' => true,
            ]
        );

        $teamB = Team::firstOrCreate(
            ['short_name' => 'EQB'],
            [
                'name' => 'Equipe B',
                'category' => 'Adulto',
                'head_coach' => 'Ana Souza',
                'description' => 'Equipe de demonstração B.',
                'is_active' => true,
            ]
        );

        $opponentB = Opponent::firstOrCreate(
            ['name' => 'Equipe B'],
            ['short_name' => 'EQB']
        );

        $this->seedAthletesForTeam($teamA, 'A');
        $this->seedAthletesForTeam($teamB, 'B');

        $match = GameMatch::firstOrCreate(
            [
                'team_id' => $teamA->id,
                'opponent_id' => $opponentB->id,
                'match_date' => now()->toDateString(),
            ],
            [
                'competition_id' => $competition->id,
                'match_time' => now()->setTime(20, 0)->format('H:i:s'),
                'location' => 'Ginásio Municipal ' . fake()->randomElement(['Central', 'Norte', 'Sul']),
                'phase' => fake()->randomElement(['Fase de grupos', 'Rodada 3', 'Quartas de final']),
                'match_type' => fake()->randomElement(['Regular', 'Amistoso', 'Playoff']),
                'team_score' => fake()->numberBetween(45, 78),
                'opponent_score' => fake()->numberBetween(42, 75),
                'status' => fake()->randomElement([
                    MatchStatus::Scheduled,
                    MatchStatus::InProgress,
                ]),
                'notes' => 'Partida de demonstração gerada automaticamente.',
            ]
        );

        $athletesA = Athlete::query()
            ->where('team_id', $teamA->id)
            ->pluck('id');

        $sync = [];
        foreach ($athletesA->values() as $index => $id) {
            $sync[$id] = ['is_starter' => $index < 2];
        }
        $match->athletes()->sync($sync);

        $this->seedShotAttempts($match, $athletesA);
    }

    private function seedShotAttempts(GameMatch $match, $athleteIds): void
    {
        if (ShotAttempt::query()->where('match_id', $match->id)->whereNotNull('court_x')->exists()) {
            return;
        }

        $existing = ShotAttempt::query()->where('match_id', $match->id)->whereNull('court_x')->get();
        foreach ($existing as $shot) {
            [$x, $y] = ShotChartService::randomCoordinatesForType($shot->shot_type->value);
            $shot->update(['court_x' => $x, 'court_y' => $y]);
        }
        if ($existing->isNotEmpty()) {
            return;
        }

        $recorder = User::query()->orderBy('id')->first();
        if (! $recorder) {
            $recorder = User::firstOrCreate(
                ['email' => 'admin@scountbcr.local'],
                [
                    'name' => 'Administrador',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                ]
            );
        }

        $types = [ShotType::TwoPoint, ShotType::ThreePoint, ShotType::FreeThrow];

        foreach (range(1, 48) as $i) {
            $athleteId = $athleteIds->random();
            $type = fake()->randomElement($types);
            $made = fake()->boolean(42);
            [$x, $y] = ShotChartService::randomCoordinatesForType($type->value);

            ShotAttempt::create([
                'match_id' => $match->id,
                'athlete_id' => $athleteId,
                'shot_type' => $type,
                'result' => $made ? ShotResult::Made : ShotResult::Missed,
                'period' => fake()->numberBetween(1, 4),
                'court_x' => $x,
                'court_y' => $y,
                'recorded_by' => $recorder->id,
                'occurred_at' => now()->subMinutes(60 - $i),
            ]);
        }
    }

    private function seedAthletesForTeam(Team $team, string $suffix): void
    {
        $positions = BasketballPosition::cases();
        $classifications = ['1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', '4.5'];

        for ($i = 1; $i <= 5; $i++) {
            $cpf = sprintf('%03d.%03d.%03d-%02d', 100 + $team->id, 200 + $i, 300 + $team->id * 10, 40 + $i);

            Athlete::firstOrCreate(
                ['cpf' => $cpf],
                [
                    'team_id' => $team->id,
                    'full_name' => fake('pt_BR')->name(),
                    'sport_name' => fake()->firstName(),
                    'jersey_number' => $i + ($team->id * 10),
                    'functional_classification' => fake()->randomElement($classifications),
                    'birth_date' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
                    'height_cm' => fake()->numberBetween(165, 205),
                    'weight_kg' => fake()->numberBetween(70, 110),
                    'position' => fake()->randomElement($positions)->value,
                    'is_active' => true,
                ]
            );
        }
    }
}