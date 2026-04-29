<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Unit List</h5>
                    @hasPermission('unit-create')
                    <a href="{{ route('units.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Unit
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Symbol</th>
                                <th>Base Unit</th>
                                <th>Conversion Factor</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($units as $unit)
                            <tr>
                                <td>{{ $unit->id }}</td>
                                <td>{{ $unit->name }}</td>
                                <td>{{ $unit->symbol ?? 'N/A' }}</td>
                                <td>{{ $unit->baseUnit?->name ?? 'N/A' }}</td>
                                <td>{{ number_format($unit->conversion_factor_to_base, 3) ?? 'N/A' }}</td>
                                <td>
                                    @hasPermission('unit-edit')
                                    <a href="{{ route('units.edit', $unit->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endhasPermission

                                    @hasPermission('unit-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $unit->id }}" data-bs-toggle="tooltip"
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

    @script
    @include('livewire.deleteConfirm')
    @endscript
</div>