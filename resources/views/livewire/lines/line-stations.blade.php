<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0">Stations — {{ $lineName }}</h5>
                    <small class="text-muted">The machines of this line, in the order the product passes them. They all run at once; the slowest sets the line's speed.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('lines.capacity', $lineId) }}" class="btn btn-light-success">
                        <i class="bi bi-speedometer2 me-1"></i>Capacity
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
                        A recipe with stages runs on this line by matching each stage to the station of the <strong>same name</strong> —
                        a stage called "Cooking" runs on the station called "Cooking".
                        The <strong>rate</strong> is per machine, in the unit that stage counts its output in (kg, packets, boxes …);
                        a station without a rate doesn't limit the line.
                    </div>
                </div>

                @error('stations')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="table-responsive">
                    <table class="table align-middle ls-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 72px">Order</th>
                                <th>Station <span class="text-danger">*</span></th>
                                <th>Machine</th>
                                <th style="width: 110px">Machines</th>
                                <th style="width: 140px">Rate per machine</th>
                                <th style="width: 130px">Per</th>
                                <th style="width: 140px">Unit</th>
                                <th style="width: 52px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stations as $key => $station)
                            <tr wire:key="station-{{ $key }}">
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="ir-idx">{{ $loop->iteration }}</span>
                                        <div class="btn-group-vertical btn-group-sm">
                                            <button type="button" class="btn btn-link p-0 lh-1" title="Move up"
                                                wire:click="moveStation('{{ $key }}', -1)" @disabled($loop->first)>
                                                <i class="bi bi-chevron-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-link p-0 lh-1" title="Move down"
                                                wire:click="moveStation('{{ $key }}', 1)" @disabled($loop->last)>
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" wire:model="stations.{{ $key }}.name" placeholder="e.g. Cooking">
                                    @error("stations.{$key}.name")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </td>
                                <td>
                                    <input type="text" class="form-control" wire:model="stations.{{ $key }}.machine" placeholder="e.g. Tunnel oven">
                                </td>
                                <td>
                                    <input type="number" min="1" step="1" class="form-control" wire:model="stations.{{ $key }}.machine_count">
                                    @error("stations.{$key}.machine_count")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </td>
                                <td>
                                    <input type="number" min="0" step="any" class="form-control" wire:model="stations.{{ $key }}.rate" placeholder="No rate">
                                    @error("stations.{$key}.rate")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </td>
                                <td>
                                    <select class="form-select" wire:model="stations.{{ $key }}.rate_period">
                                        <option value="hour">per hour</option>
                                        <option value="minute">per minute</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" wire:model="stations.{{ $key }}.rate_unit" placeholder="e.g. kg">
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-light-danger icon-btn-sm" title="Remove station"
                                        wire:click="removeStation('{{ $key }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No stations — this line runs the old way, from its capacities.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <button type="button" class="ir-add mt-3" wire:click="addStation">
                    <i class="bi bi-plus-lg"></i> Add station
                </button>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Stations
                </button>
            </div>
        </div>
    </form>
</div>
