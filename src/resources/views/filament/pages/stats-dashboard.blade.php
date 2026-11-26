@php
    $cards = $this->cards;
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach([
            ['Partidas', $cards['matches']],
            ['Atletas', $cards['athletes']],
            ['Arremessos', $cards['attempts']],
            ['Pontos', $cards['points']],
            ['Aproveitamento', ($cards['fg_percentage'] ?? 'N/A').(is_numeric($cards['fg_percentage']) ? '%' : '')],
        ] as [$label, $value])
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-filament::section heading="Aproveitamento por tipo">
            <canvas id="chart-fg" class="max-h-64"></canvas>
        </x-filament::section>
        <x-filament::section heading="Tentativas vs convertidos">
            <canvas id="chart-attempts" class="max-h-64"></canvas>
        </x-filament::section>
        <x-filament::section heading="Evolução por partida">
            <canvas id="chart-evolution" class="max-h-64"></canvas>
        </x-filament::section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function scountRenderChart(canvasId, config, type = 'bar') {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === 'undefined') return;
    if (el._chart) { el._chart.destroy(); }
    el._chart = new Chart(el, { type, data: config, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#a1a1aa' } } }, scales: type !== 'radar' ? { x: { ticks: { color: '#a1a1aa' } }, y: { beginAtZero: true, ticks: { color: '#a1a1aa' } } } : {} } });
}
        document.addEventListener('DOMContentLoaded', () => {
            scountRenderChart('chart-fg', @js($this->chartFg));
            scountRenderChart('chart-attempts', @js($this->chartAttempts));
            scountRenderChart('chart-evolution', @js($this->chartEvolution), 'line');
        });
        window.addEventListener('analytics-charts-refresh', (e) => {
            scountRenderChart('chart-fg', e.detail.fg);
            scountRenderChart('chart-attempts', e.detail.attempts);
            scountRenderChart('chart-evolution', e.detail.evolution, 'line');
        });
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                setTimeout(() => {
                    scountRenderChart('chart-fg', @js($this->chartFg));
                    scountRenderChart('chart-attempts', @js($this->chartAttempts));
                    scountRenderChart('chart-evolution', @js($this->chartEvolution), 'line');
                }, 50);
            });
        });
    </script>
</x-filament-panels::page>
