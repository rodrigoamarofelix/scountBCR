@php
    $report = $this->report;
    $athlete = $this->athlete;
    $summary = $report['summary']['overall'];
@endphp

<x-filament-panels::page>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            @if($athlete->photo_path)
                <img src="{{ \Storage::disk('public')->url($athlete->photo_path) }}" alt="" class="h-24 w-24 rounded-full object-cover">
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-200 text-2xl font-bold dark:bg-gray-800">#{{ $athlete->jersey_number }}</div>
            @endif
            <div>
                <p class="text-lg font-bold">{{ $athlete->full_name }}</p>
                <p class="text-sm text-gray-500">{{ $athlete->team?->name }} · #{{ $athlete->jersey_number }}</p>
                @if($athlete->sport_name)
                    <p class="text-sm text-gray-500">Nome esportivo: {{ $athlete->sport_name }}</p>
                @endif
            </div>
        </div>

        <div class="grid flex-1 grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach([
                ['Arremessos', $summary['attempts']],
                ['Pontos', $summary['points']],
                ['Aproveitamento', ($summary['percentage'] ?? null) === null ? 'N/A' : number_format($summary['percentage'], 1).'%'],
                ['Jogos c/ dados', count($report['match_rows'])],
            ] as [$label, $value])
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-xl font-bold">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{ $this->form }}

    <x-filament::section class="mt-6" heading="Desempenho por partida">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2">Data</th>
                        <th>Adversário</th>
                        <th>Pontos</th>
                        <th>Arremessos</th>
                        <th>2 pts</th>
                        <th>3 pts</th>
                        <th>LL</th>
                        <th>Aproveit.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['match_rows'] as $row)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2">{{ $row['date'] }}</td>
                            <td>{{ $row['opponent'] }}</td>
                            <td>{{ $row['points'] }}</td>
                            <td>{{ $row['attempts'] }}</td>
                            <td colspan="3" class="text-gray-400">—</td>
                            <td>{{ $row['fg'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-4 text-gray-500">Sem partidas no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>