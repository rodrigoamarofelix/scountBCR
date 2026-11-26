@extends('reports.layout')

@section('content')
    @include('reports.partials.overall', ['summary' => $summary])

    <table>
        <thead>
            <tr>
                <th>Partida</th>
                <th>Aproveitamento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evolution_rows as $row)
                <tr>
                    <td>{{ $row['match'] }}</td>
                    <td>{{ $row['fg'] }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Sem dados no período selecionado.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection