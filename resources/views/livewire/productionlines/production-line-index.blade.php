<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Production List</h5>
                    @can('productionLine-create')
                    <a href="{{ route('production-lines.create', ['factoryId' => $factoryId]) }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Production
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Factory</th>
                                <th>Warehouses Name</th>
                                <th>Machines Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productions as $production)
                            <tr>
                                <td>{{ $production->id }}</td>
                                <td>{{ $production->factory->name }}</td>
                                <td>{{ $production->warehouses->pluck('name')->join(', ') ?: 'N/A' }}</td>
                                <td>{{ $production->machines->pluck('name')->join(', ') ?: 'N/A' }}</td>
                                <td>
                                    @can('productionLine-edit')
                                    <a href="{{ route('production-lines.edit', ['id' => $production->id, 'factoryId' => $factoryId]) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endcan

                                    @can('productionLine-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $production->id }}" data-bs-toggle="tooltip"
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

    @script
    @include('livewire.deleteConfirm')
    @endscript
</div>