<?php

namespace Tests\Unit;

use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Models\ShotAttempt;
use App\Models\User;
use App\Services\ShotStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBasketballDomain;
use Tests\TestCase;

class ShotStatisticsServiceTest extends TestCase
{
    use CreatesBasketballDomain;
    use RefreshDatabase;

    public function test_summarize_calculates_percentages_and_points(): void
    {
        ['match' => $match, 'athlete' => $athlete] = $this->createTeamWithMatch();
        $user = User::factory()->create();

        foreach (
            [
                [ShotType::TwoPoint, ShotResult::Made],
                [ShotType::TwoPoint, ShotResult::Missed],
                [ShotType::ThreePoint, ShotResult::Made],
            ] as [$type, $result]
        ) {
            ShotAttempt::query()->create([
                'match_id' => $match->id,
                'athlete_id' => $athlete->id,
                'shot_type' => $type,
                'result' => $result,
                'period' => 1,
                'recorded_by' => $user->id,
                'occurred_at' => now(),
            ]);
        }

        $summary = app(ShotStatisticsService::class)->summarize(ShotAttempt::query());

        $this->assertSame(50.0, $summary['two_point']['percentage']);
        $this->assertSame(100.0, $summary['three_point']['percentage']);
        $this->assertSame(5, $summary['overall']['points']);
        $this->assertSame(3, $summary['overall']['attempts']);
    }
}