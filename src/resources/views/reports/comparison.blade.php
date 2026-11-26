@extends('reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Atleta</th>
                <th>2 pts</th>
                <th>3 pts</th>
                <th>LL</th>
                <th>Pontos</th>
                <th>Arremessos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comparison_rows as $row)
                @php $a = $row['athlete']; @endphp
                <tr>
                    <td>#{{ $a->jersey_number }} {{ $a->sport_name ?? $a->full_name }}</td>
                    <td>{{ $row['two_point'] === null ? 'N/A' : number_format($row['two_point'], 1, ',', '.').'%' }}</td>
                    <td>{{ $row['three_point'] === null ? 'N/A' : number_format($row['three_point'], 1, ',', '.').'%' }}</td>
                    <td>{{ $row['free_throw'] === null ? 'N/A' : number_format($row['free_throw'], 1, ',', '.').'%' }}</td>
                    <td>{{ $row['points'] }}</td>
                    <td>{{ $row['attempts'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection