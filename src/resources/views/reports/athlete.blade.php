@extends('reports.layout')

@section('content')
    <p><strong>{{ $athlete->full_name }}</strong> · {{ $athlete->team?->name }} · #{{ $athlete->jersey_number }}</p>

    @include('reports.partials.overall', ['summary' => $summary])

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Adversário</th>
                <th>Pontos</th>
                <th>Arremessos</th>
                <th>Aproveit.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($match_rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['opponent'] }}</td>
                    <td>{{ $row['points'] }}</td>
                    <td>{{ $row['attempts'] }}</td>
                    <td>{{ $row['fg'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum jogo com arremessos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection