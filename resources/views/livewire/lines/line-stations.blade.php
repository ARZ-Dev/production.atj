<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @php
        $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    @endphp

    <form wire:submit.prevent="save">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0">Stations — {{ $lineName }}</h5>
                    <small class="text-muted">The machines of this line, in the order the product passes them. They all run at once; the slowest stage sets the line's speed.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('stations') }}" class="btn btn-light-info" target="_blank">
                        <i class="bi bi-gear-wide-connected me-1"></i>All Stations
                    </a>
                    <a href="{{ route('lines') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-light border d-flex align-items-start gap-2 small">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        A recipe stage runs on this line's stations of its <strong>station type</strong> — a "Cooking" stage on the line's ovens.
                        Several stations of one type share the work: their rates add up. A station without a rate doesn't limit the line.
                        Stations and their rates are kept on the <a href="{{ route('stations') }}" target="_blank">Stations</a> page; only this line's department's stations are offered, and one can serve more than one line.
                    </div>
                </div>

                @error('rows')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="table-responsive">
                    <table class="table align-middle ls-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 72px">Order</th>
                                <th style="min-width: 260px">Station <span class="text-danger">*</span></th>
                                <th>Type</th>
                                <th>Rate</th>
                                <th>Also on</th>
                                <th style="width: 52px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $key => $row)
                            @php $station = $stations->get((int) ($row['station_id'] ?? 0)); @endphp
                            <tr wire:key="row-{{ $key }}">
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="ir-idx">{{ $loop->iteration }}</span>
                                        <div class="btn-group-vertical btn-group-sm">
                                            <button type="button" class="btn btn-link p-0 lh-1" title="Move up"
                                                wire:click="moveRow('{{ $key }}', -1)" @disabled($loop->first)>
                                                <i class="bi bi-chevron-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-link p-0 lh-1" title="Move down"
                                                wire:click="moveRow('{{ $key }}', 1)" @disabled($loop->last)>
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <select class="form-select @error("rows.{$key}.station_id") is-invalid @enderror" wire:model.live="rows.{{ $key }}.station_id">
                                        <option value="">Select a station</option>
                                        @foreach($byType as $group)
                                        <optgroup label="{{ $group->first()->type?->name }}">
                                            @foreach($group as $option)
                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                    @error("rows.{$key}.station_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </td>
                                <td>
                                    @if($station)
                                    <span class="badge bg-light-secondary text-secondary">{{ $station->type?->name }}</span>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($station)
                                    {!! $station->rateLabel() ? e($station->rateLabel()) : '<span class="text-muted">no rate</span>' !!}
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($station)
                                    {{ $station->lines->where('id', '!=', $lineId)->pluck('name')->implode(', ') ?: '—' }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-light-danger icon-btn-sm" title="Take it off this line"
                                        wire:click="removeRow('{{ $key }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No stations — this line runs the old way, from its capacities.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <button type="button" class="ir-add mt-3" wire:click="addRow">
                    <i class="bi bi-plus-lg"></i> Add station
                </button>

                @if($summary->isNotEmpty())
                <div class="mt-4">
                    <div class="fw-bold mb-2" style="font-size: 13px;">By station type</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($summary as $type)
                        <div class="border rounded px-3 py-2 small">
                            <div class="fw-semibold">{{ $type['type'] }}</div>
                            <div class="text-muted">
                                {{ $type['count'] }} {{ str('station')->plural($type['count']) }}
                                @if($type['rated'])
                                    · {{ $fmt($type['per_hour']) }}/h together{{ $type['rated'] < $type['count'] ? ' (' . $type['rated'] . ' rated)' : '' }}
                                @else
                                    · no rate
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Stations
                </button>
            </div>
        </div>
    </form>
</div>
