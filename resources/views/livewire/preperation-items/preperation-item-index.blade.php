<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Preperation Items List</h5>
                    @hasPermission('preperationItem-create')
                    <a href="{{ route('preperation-items.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add New Preperation Item
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">


                    <div class="table-responsive" wire:ignore>
                        <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item Image</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preperationItems as $i)
                                <tr>
                                    <td>{{ $i->id }}</td>
                                    <td>
                                        <img class="rounded-circle"
                                            src="{{ $i->image ? asset('storage/' . $i->image) : asset('assets/img/item-default.png') }}"
                                            alt="item photo" style="width: 40px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td>{{ $i->name }}</td>
                                    <td>{{ $i->code }}</td>
                                    <td>{{ $i->price }}</td>
                                    <td>
                                        <span class="badge {{ $i->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $i->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        @hasPermission('admin.preperationItems.view')
                                        <a href="{{ route('preperation-items.view', [$i->id, \App\Utils\Constants::VIEW_STATUS]) }}"
                                            class="btn btn-light-info icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('admin.preperationItems.edit')
                                        <a href="{{ route('preperation-items.edit', $i->id) }}"
                                            class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('admin.preperationItems.update-status')
                                        @if($i->is_active)
                                        <button type="button" wire:click="deactivate({{ $i->id }})"
                                            class="btn btn-light-danger icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Deactivate">
                                            <i class="bi bi-toggle-off"></i>
                                        </button>
                                        @else
                                        <button type="button" wire:click="activate({{ $i->id }})"
                                            class="btn btn-light-success icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Activate">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                        @endif
                                        @endhasPermission

                                        {{-- @can('admin.preperationItems.view-policy')
                                        <a href="{{ route('item-policy', $i->id) }}"
                                            class="btn btn-light-secondary icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Policy">
                                            <i class="bi bi-bar-chart-line"></i>
                                        </a>
                                        @endcan --}}

                                        @hasPermission('admin.preperationItems.delete')
                                        <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                            data-id="{{ $i->id }}" data-bs-toggle="tooltip"
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