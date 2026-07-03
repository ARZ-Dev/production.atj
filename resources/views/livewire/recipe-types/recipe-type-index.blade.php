<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recipe Type List</h5>
                    @hasPermission('production.recipeType-create')
                    <a href="{{ route('recipe-types.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add New Recipe Type
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <div class="table-responsive" wire:ignore>
                        <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Item Types</th>
                                    <th>Side Product Item Types</th>
                                    <th>Output Item Types</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($types as $type)
                                <tr>
                                    <td>{{ $type->id }}</td>
                                    <td>{{ $type->name }}</td>
                                    <td>
                                        @php
                                            $names = collect((array) $type->item_type_ids)
                                                ->map(fn($id) => $itemTypesMap[$id] ?? "ID:$id")
                                                ->implode(', ');
                                        @endphp
                                        {{ $names ?: '—' }}
                                    </td>
                                    <td>
                                        @php
                                            $sideNames = collect((array) $type->side_item_type_ids)
                                                ->map(fn($id) => $itemTypesMap[$id] ?? "ID:$id")
                                                ->implode(', ');
                                        @endphp
                                        {{ $sideNames ?: '—' }}
                                    </td>
                                    <td>
                                        @php
                                            $outputNames = collect((array) $type->output_item_type_ids)
                                                ->map(fn($id) => $itemTypesMap[$id] ?? "ID:$id")
                                                ->implode(', ');
                                        @endphp
                                        {{ $outputNames ?: '—' }}
                                    </td>

                                    <td>
                                        @hasPermission('production.recipeType-view')
                                        <a href="{{ route('recipe-types.view', $type->id) }}"
                                            class="btn btn-light-info icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('production.recipeType-edit')
                                        <a href="{{ route('recipe-types.edit', $type->id) }}"
                                            class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('admin.recipeTypes.delete')
                                        <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                            data-id="{{ $type->id }}" data-bs-toggle="tooltip"
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