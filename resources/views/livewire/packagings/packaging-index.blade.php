<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Packaging</h5>
                    @hasPermission('production.packaging-create')
                    <a href="{{ route('packagings.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Packaging
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="packagings-table" class="table table-nowrap table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Factory</th>
                                    <th>Event Types</th>
                                    <th>Capacities</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packagings as $packaging)
                                <tr wire:key="packaging-{{ $packaging->id }}">
                                    <td>{{ $packaging->id }}</td>
                                    <td>{{ $packaging->name }}</td>
                                    <td>{{ $departmentNames[$packaging->department_id] ?? '—' }}</td>
                                    <td>{{ $factoryNames[$packaging->factory_id] ?? '—' }}</td>
                                    <td>
                                        @if($packaging->eventTypes->count())
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($packaging->eventTypes as $et)
                                                <span class="badge" style="background-color: {{ $et->color ?? '#818cf8' }}">{{ $et->name }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($packaging->capacities->count())
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($packaging->capacities->groupBy('item_type_id') as $typeId => $rows)
                                                <span class="badge bg-primary bg-opacity-75"
                                                    title="{{ $rows->count() }} {{ \Illuminate\Support\Str::plural('unit', $rows->count()) }} with a capacity">
                                                    {{ $itemTypeNames[$typeId] ?? 'Item type #' . $typeId }} · {{ $rows->count() }}
                                                </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @hasPermission('production.packaging-edit')
                                        <a href="{{ route('packagings.capacity', $packaging->id) }}"
                                            class="btn btn-light-success icon-btn-sm"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Manage Capacity">
                                            <i class="bi bi-speedometer2"></i>
                                        </a>
                                        <a href="{{ route('packagings.edit', $packaging->id) }}"
                                            class="btn btn-light-primary icon-btn-sm"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @endhasPermission
                                        @hasPermission('production.packaging-delete')
                                        <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                            data-id="{{ $packaging->id }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endhasPermission
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No packaging defined yet.</td>
                                </tr>
                                @endforelse
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
