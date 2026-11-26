<?php

namespace App\Http\Controllers\Scout;

use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\GameMatch;
use App\Services\MatchScoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoutShotController extends Controller
{
    public function storeBatch(Request $request, GameMatch $gameMatch): JsonResponse
    {
        $this->authorize('scout', $gameMatch);

        $validated = $request->validate([
            'shots' => ['required', 'array', 'min:1', 'max:50'],
            'shots.*.athlete_id' => ['required', 'integer'],
            'shots.*.shot_type' => ['required', 'in:2PT,3PT,FT'],
            'shots.*.result' => ['required', 'in:made,missed'],
            'shots.*.period' => ['required', 'integer', 'min:1', 'max:8'],
            'shots.*.court_x' => ['nullable', 'numeric'],
            'shots.*.court_y' => ['nullable', 'numeric'],
            'shots.*.client_id' => ['nullable', 'string', 'max:64'],
        ]);

        $service = app(MatchScoutingService::class);
        $processed = 0;

        foreach ($validated['shots'] as $row) {
            $athlete = Athlete::query()->findOrFail((int) $row['athlete_id']);
            $service->record(
                $gameMatch->fresh(),
                $athlete,
                ShotType::from($row['shot_type']),
                ShotResult::from($row['result']),
                (int) $row['period'],
                $request->user(),
                isset($row['court_x']) ? (float) $row['court_x'] : null,
                isset($row['court_y']) ? (float) $row['court_y'] : null,
            );
            $processed++;
            $gameMatch = $gameMatch->fresh();
        }

        return response()->json([
            'ok' => true,
            'processed' => $processed,
            'team_score' => $gameMatch->team_score,
        ]);
    }
}