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

    public ?int $confirmDeleteId = null;

    public function mount(GameMatch $gameMatch): void
    {
        $this->authorize('scout', $gameMatch);
        $this->matchId = $gameMatch->id;
        $this->selectedAthleteId = $gameMatch->athletes()->orderBy('jersey_number')->value('athletes.id');
    }

    #[Computed]
    public function match(): GameMatch
    {
        return GameMatch::query()
            ->with(['team', 'opponent', 'competition', 'athletes' => fn ($q) => $q->orderBy('jersey_number')])
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
    }

    public function register(string $shotType, string $result): void
    {
        $athlete = $this->selectedAthlete;
        if (! $athlete) {
            $this->dispatch('scout-toast', message: 'Selecione um atleta.', type: 'warning');

            return;
        }

        app(MatchScoutingService::class)->record(
            $this->match,
            $athlete,
            ShotType::from($shotType),
            ShotResult::from($result),
            $this->period,
            auth()->user(),
        );

        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts);
        $this->dispatch('scout-toast', message: 'Lançamento registrado.', type: 'success');
    }

    public function undoLast(): void
    {
        $removed = app(MatchScoutingService::class)->undoLast($this->match->fresh());

        if (! $removed) {
            $this->dispatch('scout-toast', message: 'Nenhum lançamento para desfazer.', type: 'warning');

            return;
        }

        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts);
        $this->dispatch('scout-toast', message: 'Último lançamento desfeito.', type: 'success');
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

        unset($this->match, $this->matchStats, $this->athleteStats, $this->recentAttempts);
        $this->dispatch('scout-toast', message: 'Lançamento excluído.', type: 'success');
    }

    public function render()
    {
        return view('livewire.scout.match-scouting');
    }
}
