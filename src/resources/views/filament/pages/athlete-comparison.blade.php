@php
    $rows = $this->comparisonRows;
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    @if(count($rows) < 2)
        <x-filament::section class="mt-6">
            <p class="text-gray-600 dark:text-gray-400">Selecione pelo menos <strong>dois atletas</strong> da mesma equipe para comparar.</p>
        </x-filament::section>
    @else
        <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Indicador</th>
                        @foreach($rows as $row)
                            <th class="px-4 py-3">#{{ $row['athlete']->jersey_number }} {{ $row['athlete']->full_name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach([
                        'two_point' => '2 pontos (%)',
                        'three_point' => '3 pontos (%)',
                        'free_throw' => 'Lances livres (%)',
                        'points' => 'Total de pontos',
                        'attempts' => 'Tentativas',
                    ] as $key => $label)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $label }}</td>
                            @foreach($rows as $row)
                                <td class="px-4 py-3 tabular-nums">
                                    @if(in_array($key, ['two_point', 'three_point', 'free_throw'], true))
                                        {{ $row[$key] !== null ? $row[$key].'%' : 'N/A' }}
                                    @else
                                        {{ $row[$key] }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-filament::section heading="Comparativo de aproveitamento">
                <canvas id="chart-compare-bar" class="max-h-80"></canvas>
            </x-filament::section>
            <x-filament::section heading="Radar (2pts / 3pts / LL)">
                <canvas id="chart-compare-radar" class="max-h-80"></canvas>
            </x-filament::section>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function scountRenderChart(canvasId, config, type = 'bar') {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === 'undefined') return;
    if (el._chart) { el._chart.destroy(); }
    el._chart = new Chart(el, { type, data: config, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#a1a1aa' } } }, scales: type !== 'radar' ? { x: { ticks: { color: '#a1a1aa' } }, y: { beginAtZero: true, ticks: { color: '#a1a1aa' } } } : {} } });
}
        function refreshComparisonCharts() {
            scountRenderChart('chart-compare-bar', @js($this->barChart));
            scountRenderChart('chart-compare-radar', @js($this->radarChart), 'radar');
        }
        document.addEventListener('DOMContentLoaded', refreshComparisonCharts);
        Livewire.hook('commit', ({ succeed }) => { succeed(() => setTimeout(refreshComparisonCharts, 50)); });
    </script>
</x-filament-panels::page>
