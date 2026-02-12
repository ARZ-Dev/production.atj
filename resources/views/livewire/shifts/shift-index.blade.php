<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Shifts List</h5>
                    @can('shift-create')
                    <a href="{{ route('shifts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Shift
                    </a>
                    @endcan
                </div>

                <div class="card-body">
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
                                    <a href="{{ route('shifts.edit', $shift->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
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

    @script
    @include('livewire.deleteConfirm')
    @endscript
</div>