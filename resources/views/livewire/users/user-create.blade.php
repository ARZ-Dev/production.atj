<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Add User</h6>
                        <a href="{{ route('users') }}" class="btn btn-light-light text-muted"><i
                                class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">

                            @if(authUser()->hasRole('Super Admin'))
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="company_id">Company</label>
                                <div wire:ignore>
                                    <select wire:model="company_id" id="company_id"
                                        class="selectpicker w-100" title="Select Company"
                                        data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                        data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected($company->id == $company_id)>{{
                                            $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                    wire:model="first_name" placeholder="Enter First Name">
                                @error('first_name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name <span
                                        class="text-danger">*</span></label>
                                <input wire:model="last_name" type="text" class="form-control" name="last_name"
                                    id="last_name" placeholder="Enter Last Name">
                                @error('last_name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span
                                        class="text-danger">*</span></label>
                                <input wire:model="username" type="text" class="form-control" name="username"
                                    id="username" placeholder="Enter Username">
                                @error('username')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input wire:model="email" type="text" class="form-control" name="email" id="email"
                                    placeholder="Enter Email">
                                @error('email')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="role">Role</label>
                                <div wire:ignore>
                                    <select wire:model="role_name" id="role" class="selectpicker w-100"
                                        title="Select Role" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($roles as $role)
                                        <option value="{{ $role->name }}" @selected($role->name == $role_name)>
                                            {{ $role->name }}
                                            @if(authUser()->hasRole('Super Admin'))
                                            @php($company = \App\Models\Company::find($role->company_id))
                                            / {{ $company->name }}
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('role_name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input wire:model="phone" type="text" class="form-control cleave-phone-input"
                                    name="phone" id="phone" placeholder="Enter Phone Number">
                                @error('phone')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <h6 class="mb-0">Password</h6>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span
                                        class="text-danger">*</span></label>
                                <input wire:model="password" type="password" class="form-control" name="password"
                                    id="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                @error('password')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password <span
                                        class="text-danger">*</span></label>
                                <input wire:model="password_confirmation" type="password" class="form-control"
                                    name="password_confirmation" id="password_confirmation"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                @error('password_confirmation')<div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>


                        </div>
                    </div>
                </div>
                @if(!$status)

                <div class="col-12 text-end mb-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="{{ $editing ? " update"
                        : "store" }}">Submit</button>
                </div>
                @endif

            </div>
        </div>
    </form>

    @script
    <script>
        triggerCleavePhone();

        Livewire.hook('morph.added',  ({ el }) => {
            triggerCleavePhone();
        })
        $('.selectpicker').selectpicker()

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val())
        })

        $(document).on('change', '#company_id', function () {
            $wire.dispatch('getRoles', {
                company_id: $(this).val()
            })
        })

        $wire.on('setRoles', function (params) {
            let roles = params[0];
            setOptions($('#role'), roles)
        })


    </script>
    @endscript
</div>
