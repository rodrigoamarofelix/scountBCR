@php
    $preview = $this->preview;
    $type = $this->filters['report_type'] ?? 'match';
@endphp

<x-filament-panels::page>
    {{ $this->form }}

    @if($preview)
        <x-filament::section class="mt-6" heading="Prévia">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $preview['title'] }} · {{ $preview['generated_at'] }}</p>

            @if($type === 'match' && isset($preview['match']))
                @php $m = $preview['match']; @endphp
                <p class="mt-2 font-medium">{{ $m->team?->name }} x {{ $m->opponent?->name }} — {{ $m->match_date->format('d/m/Y') }}</p>
            @endif

            @if(in_array($type, ['match', 'team'], true) && ! empty($preview['athlete_rows']))
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="py-2">#</th>
                                <th>Atleta</th>
                                <th>Pontos</th>
                                <th>Arremessos</th>
                                <th>Aproveit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['athlete_rows'] as $row)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="py-2">{{ $row['jersey'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['points'] }}</td>
                                    <td>{{ $row['attempts'] }}</td>
                                    <td>{{ $row['fg'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($type === 'athlete' && ! empty($preview['match_rows']))
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="py-2">Data</th>
                                <th>Adversário</th>
                                <th>Pontos</th>
                                <th>Aproveit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['match_rows'] as $row)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="py-2">{{ $row['date'] }}</td>
                                    <td>{{ $row['opponent'] }}</td>
                                    <td>{{ $row['points'] }}</td>
                                    <td>{{ $row['fg'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($type === 'comparison' && ! empty($preview['comparison_rows']))
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="py-2">Atleta</th>
                                <th>2 pts</th>
                                <th>3 pts</th>
                                <th>LL</th>
                                <th>Pontos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['comparison_rows'] as $row)
                                @php $a = $row['athlete']; @endphp
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="py-2">#{{ $a->jersey_number }} {{ $a->sport_name ?? $a->full_name }}</td>
                                    <td>{{ $row['two_point'] ?? 'N/A' }}%</td>
                                    <td>{{ $row['three_point'] ?? 'N/A' }}%</td>
                                    <td>{{ $row['free_throw'] ?? 'N/A' }}%</td>
                                    <td>{{ $row['points'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($type === 'evolution' && ! empty($preview['evolution_rows']))
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="py-2">Partida</th>
                                <th>Aproveit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['evolution_rows'] as $row)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="py-2">{{ $row['match'] }}</td>
                                    <td>{{ $row['fg'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @else
        <p class="mt-6 text-sm text-gray-500">Ajuste os filtros para ver a prévia do relatório.</p>
    @endif
</x-filament-panels::page>