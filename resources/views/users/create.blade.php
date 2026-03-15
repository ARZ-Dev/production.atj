<x-layouts.app title="Users Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="userForm">
            @csrf

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Add User</h6>
                    <a href="{{ route('users.index') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body">
                    {{-- Validation Errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong><i class="bi bi-exclamation-triangle"></i> Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row g-3">
                        {{-- Name --}}
                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   placeholder="Enter Name"
                                   required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-md-6">
                            <label for="username" class="form-label">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('username') is-invalid @enderror"
                                   name="username"
                                   id="username"
                                   value="{{ old('username') }}"
                                   placeholder="Enter Username"
                                   required>
                            @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email"
                                   id="email"
                                   value="{{ old('email') }}"
                                   placeholder="Enter Email"
                                   required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="col-md-6">
                            <label for="role_name" class="form-label">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('role_name') is-invalid @enderror"
                                    name="role_name"
                                    id="role_name"
                                    required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role['name'] }}"
                                        {{ old('role_name') == $role['name'] ? 'selected' : '' }}>
                                        {{ $role['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="col-md-6">
                            <label for="phone" class="form-label">
                                Phone <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   name="phone"
                                   id="phone"
                                   value="{{ old('phone') }}"
                                   placeholder="Enter Phone Number"
                                   required>
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Divider --}}
                        <div class="col-12">
                            <hr>
                            <h6 class="mb-0"><i class="bi bi-lock"></i> Password</h6>
                        </div>

                        {{-- Password --}}
                        <div class="col-md-6">
                            <label for="password" class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password"
                                   id="password"
                                   placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                   required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimum 8 characters</div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    {{-- Submit --}}
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Submit
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

</x-layouts.app>

