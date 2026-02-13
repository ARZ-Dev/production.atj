<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Plans List</h5>
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add New Plan
                    </button>
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Production Line</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                            <tr>
                                <td>{{ $plan->id }}</td>
                                <td>{{ $plan->company->name }}</td>
                                <td>Production Line #{{ $plan->productionLine->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($plan->date)->format('d/m/Y') }}</td>
                                <td>
                                    <button type="button" wire:click="edit({{ $plan->id }})"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $plan->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
    <div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planModalLabel">
                        {{ $editing ? 'Edit Plan' : 'Create Plan' }}
                    </h5>
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
                            <label class="form-label" for="production_line_id">Production Line <span
                                    class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select wire:model="production_line_id" id="production_line_id"
                                    class="selectpicker w-100 production-line-select" title="Select Production Line"
                                    data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                    data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($productionLines as $productionLine)
                                    <option value="{{ $productionLine->id }}">
                                        {{ $productionLine->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('production_line_id')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" wire:model="date">
                            @error('date')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="submit">
                        {{ $editing ? 'Update Plan' : 'Create Plan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const planModal = new bootstrap.Modal(document.getElementById('planModal'));

        $wire.on('openModal', () => {
            planModal.show();
            setTimeout(() => {
                let companyId = $wire.get('company_id');
                let productionLineId = $wire.get('production_line_id');
                $('#company_id').selectpicker('val', companyId ? String(companyId) : '');
                $('#production_line_id').selectpicker('val', productionLineId ? String(productionLineId) : '');
            }, 300);
        });

        $wire.on('closeModal', () => {
            planModal.hide();
        });

        $wire.on('setProductionLines', (params) => {
            let productionLines = params[0] || params;
            setOptions($('#production_line_id'), productionLines);
        });

        $('.selectpicker').selectpicker();

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });

        $(document).on('change', '#company_id', function() {
            $wire.dispatch('GetProductionLines', {
                company_id: $(this).val()
            });
        });
    </script>
    @include('livewire.deleteConfirm')
    @endscript
</div>