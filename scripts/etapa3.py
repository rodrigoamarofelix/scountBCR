#!/usr/bin/env python3
from pathlib import Path

SRC = Path.home() / "projetos/scountBCR/src"


def w(rel: str, content: str) -> None:
    p = SRC / rel
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content)
    print("wrote", rel)


w("app/Services/MatchScoutingService.php", r'''<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\ShotAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchScoutingService
{
    public function __construct(
        protected ShotStatisticsService $statistics
    ) {}

    public function ensureAthleteOnMatch(GameMatch $match, Athlete $athlete): void
    {
        if (! $match->athletes()->where('athletes.id', $athlete->id)->exists()) {
            throw ValidationException::withMessages([
                'athlete' => 'Atleta não está convocado nesta partida.',
            ]);
        }

        if ($athlete->team_id !== $match->team_id) {
            throw ValidationException::withMessages([
                'athlete' => 'Atleta não pertence à equipe desta partida.',
            ]);
        }
    }

    public function record(
        GameMatch $match,
        Athlete $athlete,
        ShotType $shotType,
        ShotResult $result,
        int $period,
        User $recorder,
    ): ShotAttempt {
        $this->ensureAthleteOnMatch($match, $athlete);

        return DB::transaction(function () use ($match, $athlete, $shotType, $result, $period, $recorder) {
            if ($match->status === MatchStatus::Scheduled) {
                $match->update(['status' => MatchStatus::InProgress]);
            }

            $attempt = ShotAttempt::create([
                'match_id' => $match->id,
                'athlete_id' => $athlete->id,
                'shot_type' => $shotType,
                'result' => $result,
                'period' => $period,
                'recorded_by' => $recorder->id,
                'occurred_at' => now(),
            ]);

            if ($result === ShotResult::Made) {
                $match->increment('team_score', $shotType->pointsValue());
            }

            return $attempt;
        });
    }

    public function undoLast(GameMatch $match): ?ShotAttempt
    {
        $attempt = ShotAttempt::query()
            ->where('match_id', $match->id)
            ->latest('id')
            ->first();

        if (! $attempt) {
            return null;
        }

        return DB::transaction(function () use ($match, $attempt) {
            if ($attempt->result === ShotResult::Made) {
                $match->decrement('team_score', $attempt->shot_type->pointsValue());
            }

            $attempt->delete();

            return $attempt;
        });
    }

    public function deleteAttempt(ShotAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $match = $attempt->match;

            if ($attempt->result === ShotResult::Made) {
                $match->decrement('team_score', $attempt->shot_type->pointsValue());
            }

            $attempt->delete();
        });
    }

    public function statsForAthlete(GameMatch $match, Athlete $athlete): array
    {
        $query = $this->statistics->queryForContext($match, $athlete);

        return $this->statistics->summarize($query);
    }

    public function statsForMatch(GameMatch $match): array
    {
        $query = $this->statistics->queryForContext($match);

        return $this->statistics->summarize($query);
    }
}
''')

w("app/Policies/GameMatchPolicy.php", r'''<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

class GameMatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrador', 'treinador', 'analista', 'consulta']);
    }

    public function view(User $user, GameMatch $match): bool
    {
        return $this->viewAny($user);
    }

    public function scout(User $user, GameMatch $match): bool
    {
        if ($user->hasRole('consulta')) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'administrador', 'treinador', 'analista']);
    }
}
''')

w("app/Livewire/Scout/MatchScouting.php", r'''<?php

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
''')

# Continue in part 2 - blade, layout, routes - file too long, append
blade = r'''@php
    $athlete = $this->selectedAthlete;
    $stats = $this->athleteStats;
    $mStats = $this->matchStats;
@endphp
<div class="min-h-screen bg-zinc-950 text-white" x-data x-on:scout-toast.window="alert($event.detail.message)">
    <header class="sticky top-0 z-20 border-b border-zinc-800 bg-zinc-900/95 backdrop-blur px-4 py-3">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-amber-400">Scouting ao vivo</p>
                <h1 class="text-lg font-bold md:text-xl">
                    {{ $this->match->team->short_name }} x {{ $this->match->opponent->short_name ?? $this->match->opponent->name }}
                </h1>
                <p class="text-sm text-zinc-400">
                    {{ $this->match->match_date->format('d/m/Y') }}
                    @if($this->match->competition)
                        · {{ $this->match->competition->name }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-xl bg-zinc-800 px-4 py-2 text-center">
                    <p class="text-xs text-zinc-400">Placar</p>
                    <p class="text-2xl font-black tabular-nums">
                        {{ $this->match->team_score }}
                        <span class="text-zinc-500">x</span>
                        {{ $this->match->opponent_score }}
                    </p>
                </div>
                <div class="flex gap-1">
                    @foreach([1,2,3,4] as $p)
                        <button type="button"
                            wire:click="setPeriod({{ $p }})"
                            class="min-h-11 min-w-11 rounded-lg text-sm font-semibold {{ $period === $p ? 'bg-amber-500 text-black' : 'bg-zinc-800 text-zinc-300' }}">
                            {{ $p }}º
                        </button>
                    @endforeach
                </div>
                <a href="{{ url('/admin/game-matches/'.$matchId.'/edit') }}"
                    class="rounded-lg border border-zinc-700 px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800">
                    Voltar
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto grid max-w-6xl gap-4 p-4 lg:grid-cols-12">
        <section class="lg:col-span-4">
            <h2 class="mb-2 text-sm font-semibold text-zinc-400">Atletas convocados</h2>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-2">
                @foreach($this->match->athletes as $a)
                    <button type="button" wire:click="selectAthlete({{ $a->id }})" wire:key="athlete-{{ $a->id }}"
                        class="min-h-[4.5rem] rounded-xl border-2 p-2 text-left transition {{ $selectedAthleteId === $a->id ? 'border-amber-500 bg-amber-500/10' : 'border-zinc-800 bg-zinc-900' }}">
                        <span class="text-xl font-black text-amber-400">#{{ $a->jersey_number }}</span>
                        <span class="block text-sm font-medium leading-tight">{{ $a->sport_name ?? $a->full_name }}</span>
                    </button>
                @endforeach
            </div>

            @if($athlete)
                <div class="mt-4 rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                    <p class="text-sm text-zinc-400">Selecionado</p>
                    <p class="text-lg font-bold">#{{ $athlete->jersey_number }} — {{ $athlete->full_name }}</p>
                    <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-lg bg-zinc-800 p-2">
                            <dt class="text-zinc-400">2pts</dt>
                            <dd class="font-bold">{{ $stats['two_point']['made'] ?? 0 }}/{{ $stats['two_point']['attempts'] ?? 0 }}</dd>
                        </div>
                        <div class="rounded-lg bg-zinc-800 p-2">
                            <dt class="text-zinc-400">3pts</dt>
                            <dd class="font-bold">{{ $stats['three_point']['made'] ?? 0 }}/{{ $stats['three_point']['attempts'] ?? 0 }}</dd>
                        </div>
                        <div class="rounded-lg bg-zinc-800 p-2">
                            <dt class="text-zinc-400">LL</dt>
                            <dd class="font-bold">{{ $stats['free_throw']['made'] ?? 0 }}/{{ $stats['free_throw']['attempts'] ?? 0 }}</dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-center text-sm text-amber-300">
                        {{ $stats['overall']['points'] ?? 0 }} pts ·
                        {{ $stats['overall']['percentage'] !== null ? $stats['overall']['percentage'].'%' : 'N/A' }} aprov.
                    </p>
                </div>
            @endif
        </section>

        <section class="lg:col-span-8">
            @if(!$athlete)
                <p class="rounded-xl border border-dashed border-zinc-700 p-8 text-center text-zinc-400">
                    Selecione um atleta para registrar arremessos.
                </p>
            @else
                <div class="space-y-4" wire:loading.class="opacity-60">
                    @foreach([
                        ['2PT', '2 pontos', 'emerald'],
                        ['3PT', '3 pontos', 'sky'],
                        ['FT', 'Lance livre', 'violet'],
                    ] as [$code, $label, $color])
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                            <h3 class="mb-3 text-base font-semibold">{{ $label }}</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" wire:click="register('{{ $code }}', 'made')"
                                    class="min-h-20 rounded-xl bg-{{ $color }}-600 text-lg font-bold text-white shadow-lg active:scale-[0.98]">
                                    +{{ $code === 'FT' ? 1 : ($code === '3PT' ? 3 : 2) }} convertido
                                </button>
                                <button type="button" wire:click="register('{{ $code }}', 'missed')"
                                    class="min-h-20 rounded-xl bg-zinc-700 text-lg font-bold text-white active:scale-[0.98]">
                                    Errou
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="undoLast" wire:confirm="Desfazer o último lançamento desta partida?"
                        class="rounded-lg bg-zinc-800 px-4 py-3 text-sm font-semibold hover:bg-zinc-700">
                        Desfazer último
                    </button>
                </div>
            @endif

            <div class="mt-6 rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <h3 class="mb-2 text-sm font-semibold text-zinc-400">Partida (arremessos monitorados)</h3>
                <p class="text-sm">
                    {{ $mStats['overall']['points'] ?? 0 }} pontos ·
                    {{ $mStats['overall']['attempts'] ?? 0 }} tentativas ·
                    {{ $mStats['overall']['percentage'] !== null ? $mStats['overall']['percentage'].'%' : 'N/A' }}
                </p>
            </div>

            <div class="mt-4 rounded-xl border border-zinc-800 bg-zinc-900 p-4">
                <h3 class="mb-3 text-sm font-semibold text-zinc-400">Últimos lançamentos</h3>
                <ul class="space-y-2 text-sm">
                    @forelse($this->recentAttempts as $item)
                        <li class="flex items-center justify-between gap-2 rounded-lg bg-zinc-800/80 px-3 py-2" wire:key="att-{{ $item->id }}">
                            <span>
                                #{{ $item->athlete->jersey_number }}
                                · {{ $item->shot_type->label() }}
                                · {{ $item->result->label() }}
                                · {{ $item->period }}º
                            </span>
                            <button type="button" wire:click="promptDelete({{ $item->id }})"
                                class="text-xs text-red-400 hover:text-red-300">Excluir</button>
                        </li>
                    @empty
                        <li class="text-zinc-500">Nenhum lançamento ainda.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </main>

    @if($confirmDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <div class="w-full max-w-sm rounded-2xl border border-zinc-700 bg-zinc-900 p-6 shadow-xl">
                <h4 class="text-lg font-bold">Excluir lançamento?</h4>
                <p class="mt-2 text-sm text-zinc-400">Esta ação ajustará o placar se o arremesso foi convertido.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete"
                        class="rounded-lg bg-zinc-800 px-4 py-2 text-sm">Cancelar</button>
                    <button type="button" wire:click="confirmDelete"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Excluir</button>
                </div>
            </div>
        </div>
    @endif
</div>
'''

# Fix tailwind dynamic classes - use explicit classes instead of bg-{{ $color }}-600
blade = blade.replace(
    "class=\"min-h-20 rounded-xl bg-{{ $color }}-600",
    "class=\"min-h-20 rounded-xl bg-emerald-600"
)
blade = blade.replace(
    "@foreach([\n                        ['2PT', '2 pontos', 'emerald'],\n                        ['3PT', '3 pontos', 'sky'],\n                        ['FT', 'Lance livre', 'violet'],\n                    ] as [$code, $label, $color])",
    "@foreach([\n                        ['2PT', '2 pontos', 'bg-emerald-600'],\n                        ['3PT', '3 pontos', 'bg-sky-600'],\n                        ['FT', 'Lance livre', 'bg-violet-600'],\n                    ] as [$code, $label, $btnClass])"
)
blade = blade.replace(
    'class="min-h-20 rounded-xl bg-emerald-600 text-lg font-bold text-white shadow-lg active:scale-[0.98]">',
    'class="min-h-20 rounded-xl {{ $btnClass }} text-lg font-bold text-white shadow-lg active:scale-[0.98]">'
)

w("resources/views/livewire/scout/match-scouting.blade.php", blade)

w("resources/views/layouts/scout.blade.php", r'''<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Scouting' }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-zinc-950 antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
''')

# routes
routes = (SRC / "routes/web.php").read_text()
if "scout.match" not in routes:
    insert = """
use App\\Livewire\\Scout\\MatchScouting;

Route::middleware(['auth'])->group(function () {
    Route::get('/partidas/{gameMatch}/scout', MatchScouting::class)
        ->name('scout.match');
});
"""
    routes = routes.replace(
        "use App\\Http\\Controllers\\ProfileController;",
        "use App\\Http\\Controllers\\ProfileController;\nuse App\\Livewire\\Scout\\MatchScouting;",
    )
    routes = routes.replace(
        "require __DIR__.'/auth.php';",
        insert + "\nrequire __DIR__.'/auth.php';",
    )
    (SRC / "routes/web.php").write_text(routes)

# Filament action on GameMatchResource
gr = SRC / "app/Filament/Resources/GameMatchResource.php"
text = gr.read_text()
if "scout.match" not in text:
    text = text.replace(
        "Tables\\Actions\\EditAction::make(),",
        "Tables\\Actions\\Action::make('scout')\n                    ->label('Scouting ao vivo')\n                    ->icon('heroicon-o-bolt')\n                    ->color('warning')\n                    ->url(fn (GameMatch \$record) => route('scout.match', \$record))\n                    ->openUrlInNewTab(),\n                Tables\\Actions\\EditAction::make(),",
    )
    if "use App\\Models\\GameMatch;" not in text.split("class")[0]:
        pass
    gr.write_text(text)

edit_page = SRC / "app/Filament/Resources/GameMatchResource/Pages/EditGameMatch.php"
edit_page.write_text(r'''<?php

namespace App\Filament\Resources\GameMatchResource\Pages;

use App\Filament\Resources\GameMatchResource;
use App\Models\GameMatch;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameMatch extends EditRecord
{
    protected static string $resource = GameMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scout')
                ->label('Scouting ao vivo')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->url(fn (GameMatch $record) => route('scout.match', $record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
''')

print("etapa 3 OK")