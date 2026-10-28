{{-- End of a line run: what each station counted, its rejects and where they
     go, its leftovers, and what was used of each material. "Check figures"
     works it out without saving; End checks again and moves the stock. --}}
@php
    $fmt   = fn($n, $dp = 3) => $n === null || $n === '' ? '—' : rtrim(rtrim(number_format((float) $n, $dp), '0'), '.');
    $check = $runEndCheck;
@endphp

<div class="alert alert-info py-2 px-3 mb-3" style="font-size: 12px;">
    <i class="bi bi-diagram-3 me-1"></i>
    Planned <strong>{{ $fmt($eventAction['planned']) }} {{ $eventAction['unit'] }}</strong> on <strong>{{ $eventAction['line_name'] }}</strong>.
    Enter what the counters and the scale say. A station you don't count is worked out from the next one.
    @if($eventAction['mixes'])
    Dough fed is filled in from the {{ $eventAction['mixes'] }} mix{{ $eventAction['mixes'] === 1 ? '' : 'es' }} that ended for this run.
    @endif
</div>

@error('runEnd')
<div class="alert alert-danger py-2 px-3" style="font-size: 12px;">{{ $message }}</div>
@enderror

<div class="fw-bold mb-1" style="font-size: 13px;"><i class="bi bi-diagram-3 me-1 text-primary"></i> Stations</div>
<div class="table-responsive">
<table class="table table-sm align-middle aqt-table mb-3 re-table" style="font-size: 12px;">
    <thead>
        <tr>
            <th>Station</th>
            <th style="width: 150px;">Made</th>
            <th style="width: 120px;">Rejects</th>
            <th>Reject reason</th>
            <th style="width: 150px;">Rejects go to</th>
            <th style="width: 130px;">Left over → stock</th>
        </tr>
    </thead>
    <tbody>
        @foreach($runEnd['stages'] ?? [] as $key => $stage)
        @php $figures = $check['stages'][$key] ?? null; @endphp
        <tr wire:key="re-stage-{{ $key }}">
            <td>
                <div class="fw-semibold">{{ $stage['name'] }}</div>
                <div class="text-muted" style="font-size: 11px;">{{ $stage['count_unit'] }}{{ $stage['is_last'] ? ' — into stock' : '' }}</div>
            </td>
            <td>
                <input type="text" inputmode="decimal" class="form-control form-control-sm @error("runEnd.stages.{$key}.made") is-invalid @enderror"
                    wire:model="runEnd.stages.{{ $key }}.made" placeholder="{{ $stage['is_last'] ? 'Required' : 'Counter, if any' }}">
                @error("runEnd.stages.{$key}.made")<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($figures && $figures['made_derived'])
                <div class="text-muted" style="font-size: 11px;">worked out: {{ $fmt($figures['made']) }}</div>
                @endif
            </td>
            <td>
                <input type="text" inputmode="decimal" class="form-control form-control-sm @error("runEnd.stages.{$key}.rejects") is-invalid @enderror"
                    wire:model="runEnd.stages.{{ $key }}.rejects" placeholder="0">
                @error("runEnd.stages.{$key}.rejects")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" wire:model="runEnd.stages.{{ $key }}.reject_reason" placeholder="e.g. broken, burnt">
            </td>
            <td>
                <select class="form-select form-select-sm" wire:model="runEnd.stages.{{ $key }}.reject_action">
                    <option value="waste">Waste</option>
                    @if($stage['can_rework'])
                    <option value="rework">Rework → {{ $stage['rework_label'] }}</option>
                    @endif
                </select>
            </td>
            <td>
                @if($stage['has_leftover'])
                <input type="text" inputmode="decimal" class="form-control form-control-sm @error("runEnd.stages.{$key}.leftover") is-invalid @enderror"
                    wire:model="runEnd.stages.{{ $key }}.leftover" placeholder="0">
                @error("runEnd.stages.{$key}.leftover")<div class="invalid-feedback">{{ $message }}</div>@enderror
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>

<div class="fw-bold mb-1" style="font-size: 13px;"><i class="bi bi-box-seam me-1 text-primary"></i> Materials</div>
<div class="table-responsive">
<table class="table table-sm align-middle aqt-table mb-2 re-table" style="font-size: 12px;">
    <thead>
        <tr>
            <th>Material</th>
            <th class="text-end" style="width: 110px;">Recipe figure</th>
            <th style="width: 120px;">Fed</th>
            <th style="width: 190px;">Left on the line</th>
            <th style="width: 120px;">Used</th>
            <th class="text-end" style="width: 90px;">Variance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($runEnd['inputs'] ?? [] as $key => $input)
        @php $figures = $check['inputs'][$key] ?? null; @endphp
        <tr wire:key="re-input-{{ $key }}">
            <td>
                <div>{{ $input['item_name'] }} <span class="text-muted">({{ $input['unit_name'] }})</span></div>
                <div class="text-muted" style="font-size: 11px;">{{ $input['stage_name'] }}{{ $input['warehouse_name'] ? ' · ' . $input['warehouse_name'] : '' }}</div>
            </td>
            <td class="text-end">{{ $figures ? $fmt($figures['expected']) : '—' }}</td>
            <td>
                <input type="text" inputmode="decimal" class="form-control form-control-sm @error("runEnd.inputs.{$key}.fed") is-invalid @enderror"
                    wire:model="runEnd.inputs.{{ $key }}.fed" placeholder="{{ $input['from_mixes'] ? '' : 'If measured' }}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" inputmode="decimal" class="form-control @error("runEnd.inputs.{$key}.left") is-invalid @enderror"
                        wire:model="runEnd.inputs.{{ $key }}.left" placeholder="0">
                    <select class="form-select" style="max-width: 90px;" wire:model="runEnd.inputs.{{ $key }}.left_action">
                        <option value="keep">Keep</option>
                        <option value="waste">Waste</option>
                    </select>
                </div>
            </td>
            <td>
                <input type="text" inputmode="decimal" class="form-control form-control-sm @error("runEnd.inputs.{$key}.used") is-invalid @enderror"
                    wire:model="runEnd.inputs.{{ $key }}.used" placeholder="{{ $figures ? $fmt($figures['used']) : 'Fed − left' }}">
                @if($figures)
                <div class="text-muted" style="font-size: 11px;">{{ ['entered' => 'as entered', 'fed' => 'fed − left', 'recipe' => 'recipe figure'][$figures['source']] }}</div>
                @endif
            </td>
            <td class="text-end">
                @if($figures && $figures['variance'] !== null)
                <span class="{{ $figures['over_tolerance'] ? 'text-warning fw-semibold' : 'text-muted' }}">{{ $figures['variance'] > 0 ? '+' : '' }}{{ $fmt($figures['variance'], 1) }} %</span>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
<div class="text-muted mb-3" style="font-size: 11px;">
    <i class="bi bi-info-circle me-1"></i>
    Used = what you enter, else fed − left, else the recipe figure for what was made.
    Dough left on the line is kept in its warehouse, or written off on a Waste.
    A material more than {{ $fmt($eventAction['tolerance'], 1) }} % off the recipe flags the run for review — it doesn't stop it.
</div>

<div class="d-flex align-items-center gap-2 mb-3">
    <button type="button" class="btn btn-light-primary btn-sm" wire:click="checkRunEnd" wire:loading.attr="disabled" wire:target="checkRunEnd">
        <i class="bi bi-calculator me-1"></i> Check figures
    </button>
    <span class="text-muted" style="font-size: 11px;">Works it out without saving. End checks again.</span>
</div>

@if($check)
<div class="border rounded p-3 mb-3 {{ $check['errors'] ? 'border-danger' : '' }}" style="font-size: 12px;">
    @if($check['errors'])
    <div class="text-danger fw-semibold mb-1"><i class="bi bi-x-circle me-1"></i>These must be corrected before the run can end:</div>
    <ul class="text-danger mb-2 ps-3">
        @foreach($check['errors'] as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    @else
    <div class="text-success fw-semibold mb-1"><i class="bi bi-check-circle me-1"></i>The counts balance.</div>
    @endif
    <div>
        Into stock: <strong>{{ $fmt($check['output']) }} {{ $eventAction['unit'] }}</strong>
        of {{ $fmt($check['planned']) }} planned
        @if($check['planned'] > 0)({{ $fmt($check['output'] / $check['planned'] * 100, 1) }} %)@endif.
    </div>
    @if($check['flagged'])
    <div class="text-warning mt-1"><i class="bi bi-flag me-1"></i>A material is off the recipe by more than the tolerance — the run will be flagged for review.</div>
    @endif
</div>
@endif

<div class="row g-3 mt-0">
    <div class="col-md-6">
        <label class="form-label">End Time <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control @error('actionTime') is-invalid @enderror" wire:model="actionTime">
        @error('actionTime')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" wire:model="actionNotes" placeholder="Optional notes about the run…"></textarea>
    </div>
</div>
