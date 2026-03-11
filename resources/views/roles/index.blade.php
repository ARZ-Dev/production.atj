@extends('layouts.app')

@section('title', 'Roles Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="bi bi-shield-lock"></i> Roles Management
            </h2>
            <p class="text-muted mb-0">Manage production module roles and permissions</p>
        </div>
        <div>
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Role
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="rolesTable" class="table table-striped table-hover w-100">
                <thead class="table-dark">
                <tr>
                    <th>Role Name</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Created</th>
                    <th class="text-center" style="width: 100px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td>
                            @foreach($role->permissions as $permission)
                                <span class="badge bg-info text-dark">{{ $permission->name }}</span>
                            @endforeach
                        </td>
                        <td>{{ $role->users_count }}</td>
                        <td>{{ $role->created_at->format('Y-m-d') }}</td>
                        <td class="text-center">
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-warning" title="Edit Role">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" title="Delete Role"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Delete Role
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the role <strong id="deleteRoleName"></strong>?</p>
                    <p class="text-danger mb-0">
                        <small>This action cannot be undone.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')

@endpush
