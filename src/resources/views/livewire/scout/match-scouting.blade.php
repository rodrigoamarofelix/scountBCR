@php
    $athlete = $this->selectedAthlete;
    $stats = $this->athleteStats;
    $mStats = $this->matchStats;
@endphp
<div class="min-h-screen bg-zinc-950 text-zinc-100">
    {{-- Cabeçalho fixo --}}
    <header class="sticky top-0 z-30 border-b border-zinc-800 bg-zinc-900 shadow-lg">
        <div class="mx-auto max-w-5xl px-4 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">Scouting ao vivo</p>
                    <h1 class="mt-1 text-xl font-bold sm:text-2xl">
                        {{ $this->match->team->short_name }}
                        <span class="text-zinc-500">vs</span>
                        {{ $this->match->opponent->short_name ?? $this->match->opponent->name }}
                    </h1>
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ $this->match->match_date->format('d/m/Y') }}
                        @if($this->match->competition)
                            · {{ $this->match->competition->name }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-xl bg-zinc-800 px-5 py-2 text-center min-w-[7rem]">
                        <p class="text-xs text-zinc-400">Placar</p>
                        <p class="text-3xl font-black tabular-nums leading-tight">
                            {{ $this->match->team_score }}
                            <span class="text-zinc-500 text-xl">×</span>
                            {{ $this->match->opponent_score }}
                        </p>
                    </div>

                    <div class="flex gap-1.5">
                        @foreach([1, 2, 3, 4] as $p)
                            <button
                                type="button"
                                wire:click="setPeriod({{ $p }})"
                                class="flex h-12 w-12 items-center justify-center rounded-xl text-sm font-bold transition
                                    {{ $period === $p ? 'bg-amber-500 text-zinc-900' : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700' }}"
                            >
                                {{ $p }}º
                            </button>
                        @endforeach
                    </div>

                    <a href="{{ route('filament.admin.resources.game-matches.index') }}"
                        class="inline-flex h-12 items-center rounded-xl border border-zinc-600 px-4 text-sm font-medium text-zinc-200 hover:bg-zinc-800">
                        Voltar às partidas
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl space-y-6 px-4 py-6">
        {{-- Atletas --}}
        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-400">
                1. Toque no atleta
            </h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                @foreach($this->match->athletes as $a)
                    <button
                        type="button"
                        wire:click="selectAthlete({{ $a->id }})"
                        wire:key="athlete-{{ $a->id }}"
                        class="flex min-h-[5.5rem] items-stretch gap-2 rounded-2xl border-2 px-2 py-2 text-left transition active:scale-[0.98]
                            {{ $selectedAthleteId === $a->id ? 'border-amber-500 bg-amber-500/15 ring-2 ring-amber-500/40' : 'border-zinc-700 bg-zinc-900 hover:border-zinc-600' }}"
                    >
                        <div class="flex min-w-0 flex-1 flex-col justify-center pl-1">
                            <span class="text-2xl font-black text-amber-400">#{{ $a->jersey_number }}</span>
                            <span class="mt-0.5 line-clamp-2 text-sm font-medium leading-tight">
                                {{ $a->sport_name ?? $a->full_name }}
                            </span>
                        </div>
                        @if($a->photo_path)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($a->photo_path) }}"
                                alt="{{ $a->full_name }}"
                                class="my-auto h-14 w-14 shrink-0 rounded-full border-2 border-zinc-600 object-cover bg-zinc-800 shadow-inner
                                    {{ $selectedAthleteId === $a->id ? 'border-amber-400' : '' }}"
                            >
                        @else
                            <div class="my-auto flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-zinc-600 bg-zinc-800 text-base font-bold text-amber-400
                                {{ $selectedAthleteId === $a->id ? 'border-amber-400' : '' }}">
                                {{ $a->jersey_number }}
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </section>

        @if($athlete)
            <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                <p class="text-xs font-semibold uppercase text-zinc-500">Atleta selecionado</p>
                <p class="mt-1 text-lg font-bold">#{{ $athlete->jersey_number }} — {{ $athlete->full_name }}</p>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-xl bg-zinc-800 p-3">
                        <p class="text-zinc-400">2 pts</p>
                        <p class="text-lg font-bold">{{ $stats['two_point']['made'] ?? 0 }}/{{ $stats['two_point']['attempts'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-800 p-3">
                        <p class="text-zinc-400">3 pts</p>
                        <p class="text-lg font-bold">{{ $stats['three_point']['made'] ?? 0 }}/{{ $stats['three_point']['attempts'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-800 p-3">
                        <p class="text-zinc-400">LL</p>
                        <p class="text-lg font-bold">{{ $stats['free_throw']['made'] ?? 0 }}/{{ $stats['free_throw']['attempts'] ?? 0 }}</p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-400">
                    2. Registre o arremesso
                </h2>
                <div class="space-y-4" wire:loading.class="pointer-events-none opacity-70">
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                        <h3 class="mb-3 font-semibold text-emerald-400">2 pontos</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="register('2PT', 'made')"
                                class="min-h-[4.5rem] rounded-2xl bg-emerald-600 text-lg font-bold text-white shadow-md active:scale-[0.98]">
                                +2 convertido
                            </button>
                            <button type="button" wire:click="register('2PT', 'missed')"
                                class="min-h-[4.5rem] rounded-2xl bg-zinc-700 text-lg font-bold text-white active:scale-[0.98]">
                                Errou
                            </button>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                        <h3 class="mb-3 font-semibold text-sky-400">3 pontos</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="register('3PT', 'made')"
                                class="min-h-[4.5rem] rounded-2xl bg-sky-600 text-lg font-bold text-white shadow-md active:scale-[0.98]">
                                +3 convertido
                            </button>
                            <button type="button" wire:click="register('3PT', 'missed')"
                                class="min-h-[4.5rem] rounded-2xl bg-zinc-700 text-lg font-bold text-white active:scale-[0.98]">
                                Errou
                            </button>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                        <h3 class="mb-3 font-semibold text-violet-400">Lance livre</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="register('FT', 'made')"
                                class="min-h-[4.5rem] rounded-2xl bg-violet-600 text-lg font-bold text-white shadow-md active:scale-[0.98]">
                                +1 convertido
                            </button>
                            <button type="button" wire:click="register('FT', 'missed')"
                                class="min-h-[4.5rem] rounded-2xl bg-zinc-700 text-lg font-bold text-white active:scale-[0.98]">
                                Errou
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="undoLast"
                    wire:confirm="Desfazer o último lançamento desta partida?"
                    class="mt-4 w-full rounded-xl border border-zinc-600 bg-zinc-800 py-3 text-sm font-semibold hover:bg-zinc-700 sm:w-auto sm:px-6">
                    Desfazer último lançamento
                </button>
            </section>
        @else
            <p class="rounded-2xl border border-dashed border-zinc-600 bg-zinc-900/50 p-8 text-center text-zinc-400">
                Selecione um atleta acima para liberar os botões de arremesso.
            </p>
        @endif

        <section class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                <h3 class="text-sm font-semibold text-zinc-400">Totais da partida</h3>
                <p class="mt-2 text-sm">
                    {{ $mStats['overall']['points'] ?? 0 }} pontos ·
                    {{ $mStats['overall']['attempts'] ?? 0 }} tentativas ·
                    aproveitamento:
                    {{ $mStats['overall']['percentage'] !== null ? $mStats['overall']['percentage'] . '%' : 'N/A' }}
                </p>
            </div>
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
                <h3 class="text-sm font-semibold text-zinc-400">Últimos lançamentos</h3>
                <ul class="mt-2 max-h-40 space-y-2 overflow-y-auto text-sm">
                    @forelse($this->recentAttempts as $item)
                        <li class="flex items-center justify-between gap-2 rounded-lg bg-zinc-800 px-3 py-2" wire:key="att-{{ $item->id }}">
                            <span class="truncate">
                                #{{ $item->athlete->jersey_number }}
                                {{ $item->shot_type->label() }}
                                — {{ $item->result->label() }}
                                ({{ $item->period }}º)
                            </span>
                            <button type="button" wire:click="promptDelete({{ $item->id }})" class="shrink-0 text-xs text-red-400">
                                Excluir
                            </button>
                        </li>
                    @empty
                        <li class="text-zinc-500">Nenhum registro ainda.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </main>

    @if($confirmDeleteId)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/80 p-4 sm:items-center">
            <div class="w-full max-w-md rounded-2xl border border-zinc-700 bg-zinc-900 p-6 shadow-2xl">
                <h4 class="text-lg font-bold">Excluir lançamento?</h4>
                <p class="mt-2 text-sm text-zinc-400">O placar será ajustado se o arremesso tiver sido convertido.</p>
                <div class="mt-6 flex gap-3">
                    <button type="button" wire:click="cancelDelete" class="flex-1 rounded-xl bg-zinc-800 py-3 font-medium">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmDelete" class="flex-1 rounded-xl bg-red-600 py-3 font-semibold text-white">
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div wire:loading.flex class="fixed bottom-4 left-1/2 z-40 -translate-x-1/2 items-center gap-2 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-zinc-900 shadow-lg">
        Salvando…
    </div>
</div>