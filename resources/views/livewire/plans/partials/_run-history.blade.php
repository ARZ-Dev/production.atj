{{-- History modal: how a line run went — against what the line could have
     made, where the rest went, what each station counted, and the materials. --}}
@php
    $run     = $eventHistory['run'];
    $summary = $run['summary'];
    $fmt     = fn($n, $dp = 2) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, $dp), '0'), '.');
@endphp

<div class="border rounded p-3 mb-3">
    <div class="fw-bold mb-2" style="font-size: 13px;">
        <i class="bi bi-diagram-3 me-1 text-primary"></i> Line run
        @if($run['needs_review'])
        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-flag me-1"></i>Needs review</span>
        @endif
    </div>

    @if($summary)
    <div style="font-size: 12px;" class="mb-2">
        Made <strong>{{ $fmt($summary['good'], 0) }}</strong> of {{ $fmt($summary['planned'], 0) }} {{ $run['unit'] }} planned.
        In this run's time the line could have made {{ $fmt($summary['possible'], 0) }}
        at {{ $fmt($summary['per_hour'], 0) }}/h ({{ $summary['bottleneck'] }}):
        <strong>{{ $fmt($summary['oee'], 1) }} %</strong>
        = running {{ $fmt($summary['availability'], 1) }} % × speed {{ $fmt($summary['performance'], 1) }} % × good {{ $fmt($summary['quality'], 1) }} %.
    </div>
    <table class="table table-sm mb-3" style="font-size: 12px;">
        <thead><tr><th>Where the rest went</th><th>Station</th><th class="text-end">{{ $run['unit'] }}</th></tr></thead>
        <tbody>
            @foreach($summary['losses'] as $loss)
            <tr><td>{{ $loss['kind'] }}</td><td>{{ $loss['station'] }}</td><td class="text-end">{{ $fmt($loss['quantity'], 0) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($run['stages'])
    <table class="table table-sm mb-3" style="font-size: 12px;">
        <thead><tr><th>Station</th><th class="text-end">Made</th><th class="text-end">Rejects</th><th>Rejects went to</th><th class="text-end">Left over</th></tr></thead>
        <tbody>
            @foreach($run['stages'] as $stage)
            <tr>
                <td>{{ $stage['name'] }}</td>
                <td class="text-end">{{ $fmt($stage['made'], 3) }} {{ $stage['unit'] }}@unless($stage['counted'])<span class="text-muted"> (worked out)</span>@endunless</td>
                <td class="text-end">{{ $stage['rejects'] ? $fmt($stage['rejects'], 3) : '—' }}</td>
                <td>{{ $stage['rejects'] ? ucfirst($stage['disposition'] ?? '') . ($stage['reject_reason'] ? ' · ' . $stage['reject_reason'] : '') : '—' }}</td>
                <td class="text-end">{{ $stage['leftover'] ? $fmt($stage['leftover'], 3) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($run['inputs'])
    <table class="table table-sm mb-3" style="font-size: 12px;">
        <thead><tr><th>Material</th><th class="text-end">Recipe figure</th><th class="text-end">Used</th><th class="text-end">Variance</th><th class="text-end">Left on line</th></tr></thead>
        <tbody>
            @foreach($run['inputs'] as $input)
            <tr>
                <td>{{ $input['item'] }} <span class="text-muted">· {{ $input['stage'] }}</span></td>
                <td class="text-end">{{ $fmt($input['expected'], 3) }} {{ $input['unit'] }}</td>
                <td class="text-end">{{ $fmt($input['used'], 3) }}</td>
                <td class="text-end">{{ $input['variance'] === null ? '—' : (($input['variance'] > 0 ? '+' : '') . $fmt($input['variance'], 1) . ' %') }}</td>
                <td class="text-end">{{ $input['left'] ? $fmt($input['left'], 3) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($run['counts'])
    <table class="table table-sm mb-0" style="font-size: 12px;">
        <thead><tr><th>Hourly count</th><th class="text-end">Counter</th><th class="text-end">Made</th><th class="text-end">Target</th><th class="text-end">Pace</th><th>Reason</th></tr></thead>
        <tbody>
            @foreach($run['counts'] as $count)
            <tr>
                <td>{{ $count['at'] }}</td>
                <td class="text-end">{{ $fmt($count['counter'], 0) }}</td>
                <td class="text-end">{{ $fmt($count['quantity'], 0) }}</td>
                <td class="text-end">{{ $fmt($count['target'], 0) }}</td>
                <td class="text-end">{{ $count['pace'] === null ? '—' : $fmt($count['pace'], 1) . ' %' }}</td>
                <td class="text-muted">{{ $count['reason'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
