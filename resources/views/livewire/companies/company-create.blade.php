<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Add Company</h6>
                        <a href="{{ route('companies') }}" class="btn btn-light-light text-muted"><i
                                class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" wire:model="name"
                                    placeholder="Enter content title">
                                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input wire:model="phone" type="text" class="form-control cleave-input" name="phone"
                                    id="phone" placeholder="Enter Phone Number">
                                @error('phone')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            {{-- <h6 class="mb-0">Description</h6>
                            <div class="col-12 mb-10">
                                <div id="snowEditor"></div>
                            </div> --}}
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <input wire:model="address" type="text" name="address" class="form-control" id="address"
                                    placeholder="Enter Address">
                                @error('address')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea wire:model="description" name="description" id="description" rows="4"
                                    class="form-control" placeholder="Enter Description.."></textarea>
                                @error('description')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>


                        </div>
                    </div>
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="submit">Submit</button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
        triggerCleave();
    
        Livewire.hook('morph.added',  ({ el }) => {
            triggerCleave();
        })
    
    </script>
    @endscript
</div>