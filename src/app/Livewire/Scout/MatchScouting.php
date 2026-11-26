<?php

namespace App\Livewire\Scout;

use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\ShotAttempt;
use App\Services\MatchScoutingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.scout')]
#[Title('Scouting ao vivo')]
class MatchScouting extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $matchId;

    public ?int $selectedAthleteId = null;

    public int $period = 1;

    public bool $showCourtMap = false;

    public bool $capturePosition = false;

    public ?float $courtX = null;

    public ?float $courtY = null;

    public ?int $confirmDeleteId = null;

    public int $opponentScore = 0;

    public int $clockRemaining = 600;

    public bool $clockRunning = false;

    public function mount(GameMatch $gameMatch): void
    {
        $this->authorize('scout', $gameMatch);
        $this->matchId = $gameMatch->id;
        $this->selectedAthleteId = $gameMatch->athletes()->orderBy('jersey_number')->value('athletes.id');
        $this->opponentScore = (int) $gameMatch->opponent_score;
        $length = (int) ($gameMatch->scout_period_length ?: 600);
        $this->clockRemaining = (int) ($gameMatch->scout_clock_remaining ?? $length);
        $this->clockRunning = $gameMatch->scout_clock_running_at !== null;
        if ($this->clockRunning && $gameMatch->scout_clock_running_at) {
            $elapsed = (int) now()->diffInSeconds($gameMatch->scout_clock_running_at);
            $this->clockRemaining = max(0, $this->clockRemaining - $elapsed);
        }
    }

    #[Computed]
    public function match(): GameMatch
    {
        return GameMatch::query()
            ->with(['team', 'opponent.linkedTeam', 'competition', 'athletes' => fn ($q) => $q->orderBy('jersey_number')])
            ->findOrFail($this->matchId);
    }

    #[Computed]
    public function selectedAthlete(): ?Athlete
    {
        if (! $this->selectedAthleteId) {
            return null;
        }

        return $this->match->athletes->firstWhere('id', $this->selectedAthleteId);
    }

    #[Computed]
    public function athleteStats(): array
    {
        $athlete = $this->selectedAthlete;
        if (! $athlete) {
            return [];
        }

        return app(MatchScoutingService::class)->statsForAthlete($this->match, $athlete);
    }

    #[Computed]
    public function matchStats(): array
    {
        return app(MatchScoutingService::class)->statsForMatch($this->match);
    }

    #[Computed]
    public function recentAttempts()
    {
        return ShotAttempt::query()
            ->with('athlete')
            ->where('match_id', $this->matchId)
            ->latest('id')
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function courtPreviewPoints(): array
    {
        return $this->recentAttempts
            ->filter(fn (ShotAttempt $s) => $s->court_x !== null && $s->court_y !== null)
            ->map(fn (ShotAttempt $s) => [
                'x' => (float) $s->court_x,
                'y' => (float) $s->court_y,
                'made' => $s->result === ShotResult::Made,
            ])
            ->values()
            ->all();
    }

    public function selectAthlete(int $athleteId): void
    {
        if (! $this->match->athletes->contains('id', $athleteId)) {
            return;
        }

        $this->selectedAthleteId = $athleteId;
        unset($this->athleteStats);
    }

    public function setPeriod(int $period): void
    {
        $this->period = max(1, min(8, $period));
        $this->persistClock();
    }

    public function endPeriod(): void
    {
        if ($this->period < 8) {
            $this->period++;
        }
        $this->clockRunning = false;
        $this->clockRemaining = (int) ($this->match->scout_period_length ?: 600);
        $this->persistClock();
        $this->dispatch('scout-toast', message: 'Período encerrado — '.$this->period.'º', type: 'success');
    }

    public function toggleClock(): void
    {
        $this->clockRunning = ! $this->clockRunning;
        $this->persistClock();
    }

    public function resetClock(): void
    {
        $this->clockRunning = false;
        $this->clockRemaining = (int) ($this->match->scout_period_length ?: 600);
        $this->persistClock();
    }

    public function tickClock(): void
    {
        if (! $this->clockRunning || $this->clockRemaining <= 0) {
            return;
        }

        $this->clockRemaining--;
        if ($this->clockRemaining <= 0) {
            $this->clockRunning = false;
            $this->dispatch('scout-toast', message: 'Tempo esgotado.', type: 'warning');
        }
        $this->persistClock();
    }

    protected function persistClock(): void
    {
        GameMatch::query()->whereKey($this->matchId)->update([
            'scout_clock_remaining' => $this->clockRemaining,
            'scout_clock_running_at' => $this->clockRunning ? now() : null,
        ]);
    }

    public function updatedOpponentScore($value): void
    {
        $score = max(0, (int) $value);
        $this->opponentScore = $score;
        GameMatch::query()->whereKey($this->matchId)->update(['opponent_score' => $score]);
        unset($this->match);
    }

    public function pickCourtPosition(float $offsetX, float $offsetY, float $width, float $height): void
    {
        if (! $this->capturePosition) {
            return;
        }

        $w = max(1.0, $width);
        $h = max(1.0, $height);
        $this->courtX = round(($offsetX / $w) * 100, 2);
        $this->courtY = round(($offsetY / $h) * 100, 2);
    }

    public function register(string $shotType, string $result, ?int $athleteId = null): void
    {
        $athleteId = $athleteId ?? $this->selectedAthleteId;
        if (! $athleteId) {
            $this->dispatch('scout-toast', message: 'Selecione um atleta.', type: 'warning');

            return;
        }

        $athlete = $this->match->athletes->firstWhere('id', $athleteId);
        if (! $athlete) {
            $this->dispatch('scout-toast', message: 'Atleta inválido.', type: 'warning');

            return;
        }

        $courtX = null;
        $courtY = null;

        if ($this->capturePosition) {
            if ($this->courtX === null || $this->courtY === null) {
                $this->dispatch('scout-toast', message: 'Toque na quadra para marcar a posição.', type: 'warning');

                return;
            }
            $courtX = $this->courtX;
            $courtY = $this->courtY;
        }

        app(MatchScoutingService::class)->record(
            $this->match,
            $athlete,
            ShotType::from($shotType),
            ShotResult::from($result),
            $this->period,
            auth()->user(),
            $courtX,
            $courtY,
        );

        if ($this->capturePosition) {
            $this->courtX = null;
            $this->courtY = null;
        }

        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts, $this->courtPreviewPoints);
        $this->dispatch('scout-toast', message: 'Lançamento registrado.', type: 'success');
        $this->dispatch('scout-feedback', kind: 'success');
    }

    #[On('flush-offline-queue')]
    public function flushOfflineQueue(): void
    {
        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts, $this->courtPreviewPoints);
        $this->dispatch('scout-feedback', kind: 'success');
        $this->dispatch('scout-toast', message: 'Fila offline sincronizada.', type: 'success');
    }

    public function undoLast(): void
    {
        $removed = app(MatchScoutingService::class)->undoLast($this->match->fresh());

        if (! $removed) {
            $this->dispatch('scout-toast', message: 'Nenhum lançamento para desfazer.', type: 'warning');

            return;
        }

        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts, $this->courtPreviewPoints);
        $this->dispatch('scout-toast', message: 'Último lançamento desfeito.', type: 'success');
        $this->dispatch('scout-feedback', kind: 'undo');
    }

    public function promptDelete(int $attemptId): void
    {
        $this->confirmDeleteId = $attemptId;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function confirmDelete(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $attempt = ShotAttempt::query()
            ->where('match_id', $this->matchId)
            ->findOrFail($this->confirmDeleteId);

        app(MatchScoutingService::class)->deleteAttempt($attempt);
        $this->confirmDeleteId = null;

        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts, $this->courtPreviewPoints);
        $this->dispatch('scout-toast', message: 'Lançamento excluído.', type: 'success');
    }

    public function render()
    {
        return view('livewire.scout.match-scouting');
    }
}