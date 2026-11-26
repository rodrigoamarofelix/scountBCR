@extends('reports.layout')

@section('content')
    @php $m = $match; @endphp
    <p><strong>{{ $m->team?->name }}</strong> x <strong>{{ $m->opponent?->name }}</strong></p>
    <p>Data: {{ $m->match_date->format('d/m/Y') }}
        @if($m->competition) · {{ $m->competition->name }} @endif
        @if($m->location) · {{ $m->location }} @endif
    </p>

    @include('reports.partials.overall', ['summary' => $summary])

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Atleta</th>
                <th>2 pts</th>
                <th>3 pts</th>
                <th>LL</th>
                <th>Pontos</th>
                <th>Arremessos</th>
                <th>Aproveit.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($athlete_rows as $row)
                <tr>
                    <td>{{ $row['jersey'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['two_point'] }}</td>
                    <td>{{ $row['three_point'] }}</td>
                    <td>{{ $row['free_throw'] }}</td>
                    <td>{{ $row['points'] }}</td>
                    <td>{{ $row['attempts'] }}</td>
                    <td>{{ $row['fg'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection