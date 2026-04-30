<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Raw Material Request List</h5>
                    @hasPermission('rawMaterialRequest-create')
                    <a href="{{ route('raw-material-requests.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Request
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Requested By</th>
                                <th>Requested At</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->code }}</td>
                                <td>
                                    @php
                                    $statusClass = match($request->status) {
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'completed' => 'bg-info',
                                    default => 'bg-secondary',
                                    };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($request->status) }}</span>
                                </td>
                                <td>{{ $request->items->count() }}</td>
                                <td>{{ $request->requested_by?->name ?? 'N/A' }}</td>
                                <td>{{ $request->requested_at ?? 'N/A' }}</td>
                                <td>{{ $request->notes ?? 'N/A' }}</td>
                                <td>
                                    @hasPermission('rawMaterialRequest-status')
                                    @if ($request->status === 'pending')
                                    <button type="button" class="btn btn-light-success icon-btn-sm update-status-button"
                                        data-id="{{ $request->id }}" data-status="approved" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Approve">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                    <button type="button" class="btn btn-light-danger icon-btn-sm update-status-button"
                                        data-id="{{ $request->id }}" data-status="rejected" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Reject">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    @elseif ($request->status === 'approved')
                                    <button type="button" class="btn btn-light-info icon-btn-sm update-status-button"
                                        data-id="{{ $request->id }}" data-status="completed" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Mark as Completed">
                                        <i class="bi bi-check2-all"></i>
                                    </button>
                                    @endif
                                    @endhasPermission
                                    @if ($request->status === 'pending')
                                    @hasPermission('rawMaterialRequest-edit')
                                    <a href="{{ route('raw-material-requests.edit', $request->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endhasPermission

                                    @endif
                                    @if ($request->status !== 'completed')

                                    @hasPermission('rawMaterialRequest-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $request->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endhasPermission
                                    @endif
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

    @script
    <script>
        $(document).on('click', '.update-status-button', function () {
        let id = $(this).data('id');
        let status = $(this).data('status');

        let config = {
            approved: {
                title: 'Approve Request?',
                text: 'This will mark the request as approved.',
                icon: 'warning',
                confirmButtonText: 'Yes, approve it!',
            },
            rejected: {
                title: 'Reject Request?',
                text: 'This will mark the request as rejected.',
                icon: 'warning',
                confirmButtonText: 'Yes, reject it!',
            },
            completed: {
                title: 'Mark as Completed?',
                text: 'This will mark the request as completed.',
                icon: 'warning',
                confirmButtonText: 'Yes, complete it!',
            },
        };

        let { title, text, icon, confirmButtonText } = config[status];

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.dispatch('updateStatus', { id: id, status: status });
            }
        });
    });
    </script>
    @endscript
</div>