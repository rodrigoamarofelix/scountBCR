<x-filament-panels::page>
    {{ $this->form }}

  @if(!$this->hasPoints)
    <div class="mt-6 rounded-xl border border-dashed border-amber-500/40 bg-amber-500/5 p-6 text-sm text-zinc-300">
      <p class="font-medium text-amber-400">Ainda não há arremessos com posição na quadra.</p>
      <p class="mt-2">No <strong>scouting ao vivo</strong>, ative «Marcar posição na quadra», toque no mapa e depois registre o arremesso — assim você obtém mapas como nas referências (pontos azul/vermelho e zonas).</p>
    </div>
  @endif

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-filament::section class="xl:col-span-2" heading="Dispersão na quadra">
            <x-shot-court-map :points="$this->scatter" />
            <p class="mt-3 text-xs text-gray-500">{{ count($this->scatter) }} arremessos com coordenadas no filtro atual.</p>
        </x-filament::section>

        <x-filament::section heading="Aproveitamento por região">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2">Zona</th>
                        <th>FG</th>
                        <th>Arremessos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->zones as $z)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2">{{ $z['label'] }}</td>
                            <td>{{ $z['percentage'] === null ? 'N/A' : $z['percentage'].'%' }}</td>
                            <td>{{ $z['attempts'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <canvas id="chart-zones" class="mt-4 max-h-48"></canvas>
        </x-filament::section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function scountZoneChart(cfg) {
            const el = document.getElementById('chart-zones');
            if (!el || typeof Chart === 'undefined') return;
            if (el._chart) el._chart.destroy();
            el._chart = new Chart(el, { type: 'bar', data: cfg, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } } });
        }
        document.addEventListener('DOMContentLoaded', () => scountZoneChart(@js($this->zoneChart)));
        window.addEventListener('shot-charts-refresh', (e) => scountZoneChart(e.detail.chart));
    </script>
</x-filament-panels::page>