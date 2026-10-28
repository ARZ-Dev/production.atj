<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="card-title mb-0">Stations</h5>
                        <small class="text-muted">The machines, each with its own rate. A line picks its stations on its Stations page.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('station-types') }}" class="btn btn-light-info">
                            <i class="bi bi-tags me-1"></i>Station Types
                        </a>
                        @hasPermission('production.line-create')
                        <button type="button" class="btn btn-primary" wire:click="create" @disabled($types->isEmpty())
                            @if($types->isEmpty()) title="Add a station type first" @endif>
                            <i class="bi bi-plus-lg me-1"></i> Add Station
                        </button>
                        @endhasPermission
                    </div>
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Department</th>
                                <th>Factory</th>
                                <th>Manufacturer</th>
                                <th>Rate</th>
                                <th>Lines</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stations as $station)
                            <tr>
                                <td class="fw-semibold">{{ $station->name }}</td>
                                <td><span class="badge bg-light-secondary text-secondary">{{ $station->type?->name }}</span></td>
                                <td>{{ $station->department_id ? ($departmentNames[$station->department_id] ?? 'Department #' . $station->department_id) : '—' }}</td>
                                <td>{{ $station->factory_id ? ($factoryNames[$station->factory_id] ?? 'Warehouse #' . $station->factory_id) : '—' }}</td>
                                <td>{{ $station->manufacturer ?: '—' }}</td>
                                <td>{!! $station->rateLabel() ? e($station->rateLabel()) : '<span class="text-muted">no rate</span>' !!}</td>
                                <td>
                                    @forelse($station->lines as $line)
                                    <span class="badge bg-light-primary text-primary">{{ $line->name }}</span>
                                    @empty
                                    <span class="text-muted">not on a line</span>
                                    @endforelse
                                </td>
                                <td class="text-wrap small" style="min-width: 220px;">{{ $station->notes }}</td>
                                <td>
                                    @hasPermission('production.line-edit')
                                    <button type="button" wire:click="edit({{ $station->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission

                                    @hasPermission('production.line-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $station->id }}"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endhasPermission
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="stationModal" tabindex="-1" aria-labelledby="stationModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" wire:submit.prevent="submit">
                <div class="modal-header">
                    <h5 class="modal-title" id="stationModalLabel">{{ $editing ? 'Edit Station' : 'New Station' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="s_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="s_name" wire:model="name" placeholder="e.g. Cutting Machine 1">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="s_type" class="form-label">Station type <span class="text-danger">*</span></label>
                            <select class="form-select @error('station_type_id') is-invalid @enderror" id="s_type" wire:model.live="station_type_id">
                                <option value="">Select a type</option>
                                @foreach($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('station_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="s_department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="s_department" wire:model.live="department_id">
                                <option value="">Select a department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department['id'] }}">{{ $department['name'] }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="s_factory" class="form-label">Factory <span class="text-danger">*</span></label>
                            {{-- Keyed by department: Livewire sets the value before it adds new
                                 options, so a select whose options change is replaced, not patched. --}}
                            <select class="form-select @error('factory_id') is-invalid @enderror" id="s_factory" wire:model="factory_id" @disabled(!$department_id)
                                wire:key="s-factory-{{ $department_id ?: 'none' }}">
                                <option value="">{{ $department_id ? 'Select a factory' : 'Select a department first' }}</option>
                                @foreach($factories as $factory)
                                <option value="{{ $factory['id'] }}" @selected((string) $factory_id === (string) $factory['id'])>{{ $factory['name'] }}</option>
                                @endforeach
                            </select>
                            @error('factory_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="s_manufacturer" class="form-label">Manufacturer / model</label>
                            <input type="text" class="form-control @error('manufacturer') is-invalid @enderror"
                                id="s_manufacturer" wire:model="manufacturer" placeholder="e.g. Laser">
                            @error('manufacturer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-md-4">
                            <label for="s_rate" class="form-label">Rate</label>
                            <input type="number" min="0" step="any" class="form-control @error('rate') is-invalid @enderror"
                                id="s_rate" wire:model="rate" placeholder="No rate">
                            @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="s_period" class="form-label">Per</label>
                            <select class="form-select" id="s_period" wire:model="rate_period">
                                <option value="hour">per hour</option>
                                <option value="minute">per minute</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="s_unit" class="form-label">Unit</label>
                            <input type="text" class="form-control @error('rate_unit') is-invalid @enderror"
                                id="s_unit" wire:model="rate_unit" placeholder="{{ $typeUnit ?: 'e.g. kg' }}">
                            @error('rate_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-text mt-0">
                                What this one machine makes, in the unit its recipe stage counts (kg, packets, boxes …).
                                A station without a rate doesn't limit its line. Blank unit = the type's{{ $typeUnit ? ' (' . $typeUnit . ')' : '' }}.
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="s_notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" rows="2"
                                id="s_notes" wire:model="notes"></textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetForm">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @script
    <script>
        const stationModal = new bootstrap.Modal(document.getElementById('stationModal'));
        $wire.on('openModal', () => stationModal.show());
        $wire.on('closeModal', () => stationModal.hide());
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
