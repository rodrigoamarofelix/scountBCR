@php
    $athlete = $this->selectedAthlete;
    $stats = $this->athleteStats;
    $mStats = $this->matchStats;
    $homeTeam = $this->match->team;
    $away = $this->match->opponent;
    $awayLogo = $away?->resolvedLogoPath();
@endphp
<div
    class="min-h-screen bg-zinc-950 text-zinc-100"
    x-data="scoutTablet({ matchId: @js($matchId), csrf: @js(csrf_token()) })"
    @if($clockRunning) wire:poll.1s="tickClock" @endif
>
    <header class="sticky top-0 z-30 border-b border-zinc-800 bg-zinc-900 shadow-lg">
        <div class="mx-auto max-w-5xl px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">Scouting — modo tablet</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                        @if($homeTeam->logo_path)
                            <img src="{{ Storage::disk('public')->url($homeTeam->logo_path) }}" alt="" class="h-9 w-9 rounded-full border border-zinc-600 object-cover">
                        @endif
                        <span class="font-bold">{{ $homeTeam->short_name }}</span>
                        <span class="text-zinc-500">vs</span>
                        @if($awayLogo)
                            <img src="{{ Storage::disk('public')->url($awayLogo) }}" alt="" class="h-9 w-9 rounded-full border border-zinc-600 object-cover">
                        @endif
                        <span class="font-bold">{{ $away?->short_name ?? $away?->name }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="rounded-xl bg-zinc-950 px-4 py-2 text-center ring-1 ring-amber-500/40">
                        <p class="text-[10px] uppercase text-zinc-500">Período</p>
                        <p class="text-2xl font-black text-amber-400">{{ $period }}º</p>
                    </div>
                    <div class="min-w-[5.5rem] rounded-xl bg-zinc-950 px-4 py-2 text-center ring-1 ring-zinc-700">
                        <p class="text-[10px] uppercase text-zinc-500">Relógio</p>
                        <p class="font-mono text-2xl font-bold tabular-nums" wire:key="clk-{{ $clockRemaining }}">{{ sprintf('%d:%02d', intdiv($clockRemaining, 60), $clockRemaining % 60) }}</p>
                    </div>
                    <button type="button" wire:click="toggleClock" class="min-h-11 rounded-xl bg-cyan-600 px-3 text-sm font-bold text-white">
                        {{ $clockRunning ? 'Pausar' : 'Iniciar' }}
                    </button>
                    <button type="button" wire:click="endPeriod" class="min-h-11 rounded-xl bg-violet-600 px-3 text-sm font-bold text-white">
                        Fim período
                    </button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <div class="inline-flex items-baseline gap-2 rounded-xl bg-zinc-800 px-3 py-1.5">
                    <span class="text-xs text-zinc-400">{{ $homeTeam->short_name }}</span>
                    <span class="text-2xl font-black tabular-nums leading-none text-emerald-400 sm:text-3xl">{{ $this->match->team_score }}</span>
                </div>
                <span class="text-zinc-500">×</span>
                <label class="inline-flex items-baseline gap-2 rounded-xl bg-zinc-800 px-3 py-1.5">
                    <span class="text-xs text-zinc-400">{{ $away?->short_name ?? 'ADV' }}</span>
                    <input
                        type="number"
                        min="0"
                        inputmode="numeric"
                        wire:model.blur="opponentScore"
                        class="w-10 min-w-0 border-0 bg-transparent p-0 text-center text-2xl font-black leading-none tabular-nums text-sky-400 focus:ring-0 sm:w-11 sm:text-3xl [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                    >
                </label>

                <div class="flex gap-1">
                    @foreach([1, 2, 3, 4] as $p)
                        <button type="button" wire:click="setPeriod({{ $p }})"
                            class="h-11 w-11 rounded-lg text-sm font-bold {{ $period === $p ? 'bg-amber-500 text-zinc-900' : 'bg-zinc-800 text-zinc-300' }}">
                            {{ $p }}
                        </button>
                    @endforeach
                </div>

                <span x-show="!online" class="rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-300">Offline</span>
                <span x-show="queueCount > 0" class="rounded-full bg-violet-500/20 px-3 py-1 text-xs font-semibold text-violet-200" x-text="`${queueCount} na fila`"></span>

                <button type="button" @click="toggleSound()" class="ml-auto text-xs text-zinc-500 underline" x-text="soundOn ? 'Som ligado' : 'Som mudo'"></button>

                <a href="{{ route('filament.admin.resources.game-matches.index') }}" class="rounded-xl border border-zinc-600 px-3 py-2 text-sm">Partidas</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl space-y-4 px-4 py-4 pb-28">
        <section>
            <h2 class="mb-2 text-xs font-semibold uppercase text-zinc-500">1 · Toque no atleta</h2>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5">
                @foreach($this->match->athletes as $a)
                    <button type="button" wire:click="selectAthlete({{ $a->id }})" wire:key="ath-{{ $a->id }}"
                        class="flex min-h-[5rem] items-center gap-2 rounded-2xl border-2 px-2 py-2 transition active:scale-[0.97]
                            {{ $selectedAthleteId === $a->id ? 'border-amber-500 bg-amber-500/20 ring-2 ring-amber-500/50' : 'border-zinc-700 bg-zinc-900' }}">
                        <span class="text-3xl font-black text-amber-400">#{{ $a->jersey_number }}</span>
                        <span class="line-clamp-2 text-left text-sm font-semibold leading-tight">{{ $a->sport_name ?? $a->full_name }}</span>
                        @if($a->photo_path)
                            <img src="{{ Storage::disk('public')->url($a->photo_path) }}" alt="" class="ml-auto h-14 w-14 shrink-0 rounded-full object-cover">
                        @endif
                    </button>
                @endforeach
            </div>
        </section>

        @if($athlete)
            <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-3">
                <p class="text-xs text-zinc-500">Selecionado: <strong class="text-zinc-200">#{{ $athlete->jersey_number }} {{ $athlete->full_name }}</strong></p>
            </section>

            <section>
                <h2 class="mb-2 text-xs font-semibold uppercase text-zinc-500">2 · Um toque = registra</h2>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" wire:loading.class="opacity-60">
                    @foreach([
                        ['2PT', 'made', '+2', 'bg-emerald-600'],
                        ['2PT', 'missed', '2 errado', 'bg-zinc-700'],
                        ['3PT', 'made', '+3', 'bg-sky-600'],
                        ['3PT', 'missed', '3 errado', 'bg-zinc-700'],
                        ['FT', 'made', '+1 LL', 'bg-violet-600'],
                        ['FT', 'missed', 'LL errado', 'bg-zinc-700'],
                    ] as [$type, $res, $label, $color])
                        <button type="button" @click="tryRegister('{{ $type }}', '{{ $res }}')"
                            class="min-h-[4.5rem] rounded-2xl {{ $color }} text-lg font-black text-white shadow-lg active:scale-[0.98]">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            <div class="flex gap-2">
                <button type="button"
                    class="flex-1 rounded-xl border-2 border-dashed border-zinc-600 py-4 text-center text-sm font-semibold text-zinc-400"
                    @mousedown="startHoldUndo()" @mouseup="cancelHoldUndo()" @mouseleave="cancelHoldUndo()"
                    @touchstart.prevent="startHoldUndo()" @touchend="cancelHoldUndo()">
                    Segure ~0,6s para desfazer o último
                </button>
            </div>
        @else
            <p class="rounded-2xl border border-dashed border-zinc-600 p-8 text-center text-zinc-400">Selecione um atleta.</p>
        @endif

        <details class="rounded-2xl border border-zinc-800 bg-zinc-900/80">
            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-zinc-300">Mapa na quadra (opcional)</summary>
            <div class="border-t border-zinc-800 p-4">
                <label class="mb-3 flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="showCourtMap" class="rounded border-zinc-600 bg-zinc-800 text-amber-500">
                    Mostrar mapa
                </label>
                @if($showCourtMap)
                    <label class="mb-3 flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="capturePosition" class="rounded border-zinc-600 bg-zinc-800 text-amber-500">
                        Marcar posição antes do arremesso
                    </label>
                    <x-shot-court-map
                        :points="$this->courtPreviewPoints"
                        :interactive="$capturePosition"
                        :marker-x="$courtX"
                        :marker-y="$courtY"
                        height-class="max-h-[220px]"
                    />
                @endif
            </div>
        </details>

        <section class="grid gap-3 sm:grid-cols-2 text-sm text-zinc-400">
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-3">
                Partida: {{ $mStats['overall']['points'] ?? 0 }} pts · {{ $mStats['overall']['attempts'] ?? 0 }} tent. · FG {{ $mStats['overall']['percentage'] ?? 'N/A' }}%
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 p-3 max-h-32 overflow-y-auto">
                @forelse($this->recentAttempts as $item)
                    <div class="flex justify-between border-b border-zinc-800 py-1" wire:key="r-{{ $item->id }}">
                        <span>#{{ $item->athlete->jersey_number }} {{ $item->shot_type->label() }} {{ $item->result->label() }}</span>
                        <button type="button" wire:click="promptDelete({{ $item->id }})" class="text-red-400 text-xs">Excluir</button>
                    </div>
                @empty
                    <span class="text-zinc-600">Nenhum lançamento.</span>
                @endforelse
            </div>
        </section>
    </main>

    <div id="scout-toast" class="fixed bottom-20 left-1/2 z-50 hidden -translate-x-1/2 rounded-full px-4 py-2 text-sm font-semibold shadow-lg"></div>
    <div wire:loading.flex class="fixed bottom-4 left-1/2 z-40 -translate-x-1/2 rounded-full bg-amber-500 px-4 py-2 text-sm font-bold text-zinc-900">Salvando…</div>

    @if($confirmDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-zinc-900 p-6">
                <p class="font-bold">Excluir lançamento?</p>
                <div class="mt-4 flex gap-2">
                    <button type="button" wire:click="cancelDelete" class="flex-1 rounded-xl bg-zinc-800 py-3">Cancelar</button>
                    <button type="button" wire:click="confirmDelete" class="flex-1 rounded-xl bg-red-600 py-3 text-white">Excluir</button>
                </div>
            </div>
        </div>
    @endif
</div>