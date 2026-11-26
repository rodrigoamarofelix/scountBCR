<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Services\MatchScoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBasketballDomain;
use Tests\TestCase;

class MatchScoutingServiceTest extends TestCase
{
    use CreatesBasketballDomain;
    use RefreshDatabase;

    public function test_record_increments_score_and_sets_in_progress(): void
    {
        ['match' => $match, 'athlete' => $athlete] = $this->createTeamWithMatch();
        $user = $this->createUserWithRole('treinador');

        $attempt = app(MatchScoutingService::class)->record(
            $match->fresh(),
            $athlete,
            ShotType::ThreePoint,
            ShotResult::Made,
            1,
            $user,
        );

        $match->refresh();
        $this->assertSame(3, $match->team_score);
        $this->assertSame(MatchStatus::InProgress, $match->status);
        $this->assertSame($athlete->id, $attempt->athlete_id);
    }

    public function test_undo_last_reverses_score(): void
    {
        ['match' => $match, 'athlete' => $athlete] = $this->createTeamWithMatch();
        $user = $this->createUserWithRole('treinador');
        $service = app(MatchScoutingService::class);

        $service->record($match->fresh(), $athlete, ShotType::TwoPoint, ShotResult::Made, 1, $user);
        $this->assertSame(2, $match->fresh()->team_score);

        $service->undoLast($match->fresh());
        $this->assertSame(0, $match->fresh()->team_score);
    }
}