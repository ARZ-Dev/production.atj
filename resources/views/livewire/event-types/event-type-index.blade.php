<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Event Types List</h5>
                    @can('eventType-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add New Event Type
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
                                <th>Has Recipe</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eventTypes as $eventType)
                            <tr>
                                <td>{{ $eventType->id }}</td>
                                <td>{{ $eventType->company->name }}</td>
                                <td>{{ $eventType->name }}</td>
                                <td>{{ $eventType->has_recipe ? 'Yes' : 'No' }}</td>
                                <td>{{ $eventType->duration }} Minute</td>
                                <td>
                                    @can('eventType-edit')
                                    <button type="button" wire:click="edit({{ $eventType->id }})"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endcan

                                    @can('eventType-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $eventType->id }}" data-bs-toggle="tooltip"
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
    <div class="modal fade" id="eventTypeModal" tabindex="-1" aria-labelledby="eventTypeModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTypeModalLabel">
                        {{ $editing ? 'Edit Event Type' : 'Create Event Type' }}
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
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" wire:model="name"
                                placeholder="Enter event type name">
                            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_recipe"
                                    wire:model.live="has_recipe">
                                <label class="form-check-label" for="has_recipe">Has Recipe</label>
                            </div>
                        </div>

                        @if($has_recipe)
                        <div class="col-12">
                            <label class="form-label" for="recipe_id">Recipe <span
                                    class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select wire:model="recipe_id" id="recipe_id" class="selectpicker w-100"
                                    title="Select Recipe" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($recipes as $recipe)
                                    <option value="{{ $recipe->id }}">
                                        {{ $recipe->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('recipe_id')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        @else
                        <div class="col-12">
                            <label for="duration" class="form-label">Duration (minutes) <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="duration" wire:model="duration"
                                placeholder="Enter duration in minutes" min="1">
                            @error('duration')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="submit">
                        {{ $editing ? 'Update Event Type' : 'Create Event Type' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const eventTypeModal = new bootstrap.Modal(document.getElementById('eventTypeModal'));

        $wire.on('openModal', () => {
            eventTypeModal.show();
            setTimeout(() => {
                let companyId = $wire.get('company_id');
                let recipeId = $wire.get('recipe_id');
                $('#company_id').selectpicker('val', companyId ? String(companyId) : '');
                $('#recipe_id').selectpicker('val', recipeId ? String(recipeId) : '');
            }, 300);
        });

        $wire.on('closeModal', () => {
            eventTypeModal.hide();
        });

        $wire.on('refreshRecipePicker', () => {
            setTimeout(() => {
                let recipeId = $wire.get('recipe_id');
                $('#recipe_id').selectpicker('destroy').selectpicker();
                $('#recipe_id').selectpicker('val', recipeId ? String(recipeId) : '');
            }, 300);
        });

        $('.selectpicker').selectpicker();

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });

        Livewire.hook('morph.added', ({el}) => {
            $(el).find('.selectpicker').selectpicker();
        });
    </script>
    @include('livewire.deleteConfirm')
    @endscript
</div>