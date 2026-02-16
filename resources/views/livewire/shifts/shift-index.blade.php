<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Shifts List</h5>
                    @can('shift-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add New Shift
                    </button>
                    @endcan
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Name</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shifts as $shift)
                            <tr>
                                <td>{{ $shift->id }}</td>
                                <td>{{ $shift->company->name }}</td>
                                <td>{{ $shift->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($shift->from_time)->format('h:i A') }}</td>
                                <td>{{ \Carbon\Carbon::parse($shift->to_time)->format('h:i A') }}</td>
                                <td>
                                    @can('shift-edit')
                                    <button type="button" wire:click="edit({{ $shift->id }})"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endcan

                                    @can('shift-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $shift->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" id="shiftModal" tabindex="-1" aria-labelledby="shiftModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shiftModalLabel">{{ $editing ? 'Edit Shift' : 'Create Shift' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        @if(auth()->user()->hasRole('Super Admin'))
                        <div class="col-12">
                            <label class="form-label" for="company_id">Company</label>
                            <div wire:ignore>
                                <select wire:model="company_id" id="company_id" class="selectpicker w-100"
                                    title="Select Company" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($companies as $company)
                                    <option value="{{ $company->id }}">
                                        {{ $company->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('company_id')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        @endif

                        <div class="col-12">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" wire:model="name"
                                placeholder="Enter shift name">
                            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="from_time" class="form-label">From Time <span
                                    class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="from_time" wire:model="from_time">
                            @error('from_time')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="to_time" class="form-label">To Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="to_time" wire:model="to_time">
                            @error('to_time')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="submit">
                        {{ $editing ? 'Update Shift' : 'Create Shift' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const shiftModal = new bootstrap.Modal(document.getElementById('shiftModal'));

        $wire.on('openModal', () => {
            shiftModal.show();
                setTimeout(() => {
                    let companyId = $wire.get('company_id');
                    $('#company_id').selectpicker('val', companyId ? String(companyId) : '');
                }, 300);
        });

        $wire.on('closeModal', () => {
            shiftModal.hide();
        });

        $('.selectpicker').selectpicker();

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });
    </script>
    @include('livewire.deleteConfirm')
    @endscript
</div>