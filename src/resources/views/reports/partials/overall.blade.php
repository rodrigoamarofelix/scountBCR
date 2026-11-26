@php $o = $summary['overall']; @endphp
<div class="summary">
    <span><strong>Arremessos:</strong> {{ $o['attempts'] }}</span>
    <span><strong>Pontos:</strong> {{ $o['points'] }}</span>
    <span><strong>Aproveitamento:</strong>
        {{ $o['percentage'] === null ? 'N/A' : number_format($o['percentage'], 1, ',', '.').'%' }}
    </span>
</div>