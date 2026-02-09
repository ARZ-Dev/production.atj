<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Machine Type</h6>
                        <a href="{{ route('machine-types') }}" class="btn btn-light-light text-muted"><i
                                class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                              @if(auth()->user()->hasRole('Super Admin'))
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
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" wire:model="name"
                                    placeholder="Enter machine type name">
                                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 text-end mb-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="submit">Submit</button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
       $('.selectpicker').selectpicker()

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val())
        })


    
    </script>
    @endscript

</div>