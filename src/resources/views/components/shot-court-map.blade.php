@props([
    'points' => [],
    'interactive' => false,
    'markerX' => null,
    'markerY' => null,
    'heightClass' => 'max-h-[420px]',
])

<div
    @class(['relative w-full', $heightClass])
    @if($interactive)
        wire:click="pickCourtPosition($event.offsetX, $event.offsetY, $event.currentTarget.offsetWidth, $event.currentTarget.offsetHeight)"
        role="button"
        tabindex="0"
        title="Toque na quadra para marcar a origem do arremesso"
    @endif
>
    <svg viewBox="0 0 100 100" class="h-full w-full rounded-xl border border-zinc-700 bg-[#1a2e1a]" @if($interactive) style="cursor: crosshair" @endif>
        {{-- Linhas simplificadas meia quadra --}}
        <rect x="2" y="2" width="96" height="96" fill="none" stroke="#64748b" stroke-width="0.6"/>
        <path d="M 2 88 L 98 88" stroke="#94a3b8" stroke-width="0.5"/>
        <rect x="30" y="72" width="40" height="18" fill="none" stroke="#94a3b8" stroke-width="0.5"/>
        <circle cx="50" cy="88" r="2" fill="none" stroke="#94a3b8" stroke-width="0.5"/>
        <path d="M 6 88 A 44 44 0 0 1 94 88" fill="none" stroke="#cbd5e1" stroke-width="0.55"/>
        <circle cx="50" cy="90" r="1.2" fill="#fbbf24"/>
        @foreach($points as $p)
            <circle
                cx="{{ $p['x'] }}"
                cy="{{ $p['y'] }}"
                r="1.8"
                fill="{{ ($p['made'] ?? false) ? '#3b82f6' : '#ef4444' }}"
                fill-opacity="0.85"
            >
                @if(!empty($p['label']))
                    <title>{{ $p['label'] }}</title>
                @endif
            </circle>
        @endforeach
        @if($markerX !== null && $markerY !== null)
            <circle cx="{{ $markerX }}" cy="{{ $markerY }}" r="2.8" fill="none" stroke="#f59e0b" stroke-width="0.8"/>
        @endif
    </svg>
</div>
<div class="mt-2 flex flex-wrap gap-4 text-xs text-zinc-400">
    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"></span> Convertido</span>
    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span> Errado</span>
</div>