<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recipe List</h5>
                    @hasPermission('production.recipe-create')
                    <a href="{{ route('recipes.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add New Recipe
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <div class="table-responsive" wire:ignore>
                        <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Recipe Type</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recipes as $r)
                                <tr>
                                    <td>{{ $r->id }}</td>
                                    <td>{{ $r->recipe_type == 1 ? 'Preparation' : ($r->recipe_type == 2 ? 'Production' : '-') }}</td>
                                    <td>{{ $r->name }}</td>
                                    <td>
                                        <span class="badge {{ $r->status ? 'bg-success' : 'bg-danger' }}">
                                            {{ $r->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        @hasPermission('production.recipe-view')
                                        <a href="{{ route('recipes.view', [$r->id, \App\Utils\Constants::VIEW_STATUS]) }}"
                                            class="btn btn-light-info icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('production.recipe-edit')
                                        <a href="{{ route('recipes.edit', $r->id) }}"
                                            class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('production.recipe-update-status')
                                        @if($r->is_active)
                                        <button type="button" wire:click="deactivate({{ $r->id }})"
                                            class="btn btn-light-danger icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Deactivate">
                                            <i class="bi bi-toggle-off"></i>
                                        </button>
                                        @else
                                        <button type="button" wire:click="activate({{ $r->id }})"
                                            class="btn btn-light-success icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Activate">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                        @endif
                                        @endhasPermission
                                        @hasPermission('admin.preperationItems.delete')
                                        <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                            data-id="{{ $r->id }}" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Delete">
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
    </div>

    @script
    @include('livewire.deleteConfirm')
    @endscript
</div>