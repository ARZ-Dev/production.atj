<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Event Types List</h5>
                    @can('eventType-create')
                    <a href="{{ route('event-types.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Event Type
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
                                    <a href="{{ route('event-types.edit', $eventType->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
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

    @script
    @include('livewire.deleteConfirm')
    @endscript
</div>