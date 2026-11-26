<?php

namespace Tests\Support;

use App\Enums\MatchStatus;
use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\Opponent;
use App\Models\Team;
use App\Models\User;

trait CreatesBasketballDomain
{
    protected function createTeamWithMatch(): array
    {
        $team = Team::query()->create([
            'name' => 'Equipe Teste',
            'short_name' => 'TST',
            'category' => 'adulto',
            'is_active' => true,
        ]);

        $opponent = Opponent::query()->create(['name' => 'Rival', 'short_name' => 'RVL']);

        $match = GameMatch::query()->create([
            'team_id' => $team->id,
            'opponent_id' => $opponent->id,
            'match_date' => now()->toDateString(),
            'status' => MatchStatus::Scheduled,
            'team_score' => 0,
            'opponent_score' => 0,
        ]);

        $athlete = Athlete::query()->create([
            'team_id' => $team->id,
            'full_name' => 'Atleta Teste',
            'cpf' => '000.000.000-00',
            'jersey_number' => 7,
            'is_active' => true,
        ]);

        $match->athletes()->attach($athlete->id, ['is_starter' => true]);

        return compact('team', 'opponent', 'match', 'athlete');
    }

    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }
}