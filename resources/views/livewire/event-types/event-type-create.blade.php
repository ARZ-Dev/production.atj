<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ $editing ? 'Edit Event Type' : 'Create Event Type' }}</h5>
                        <a href="{{ route('event-types') }}" class="btn btn-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            @if(auth()->user()->hasRole('Super Admin'))
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="company_id">Company</label>
                                <div wire:ignore>
                                    <select wire:model="company_id" id="company_id" class="selectpicker w-100"
                                        title="Select Company" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected($company->id === $company_id)>
                                            {{ $company->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif

                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" wire:model="name"
                                    placeholder="Enter event type name">
                                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="has_recipe"
                                        wire:model.live="has_recipe">
                                    <label class="form-check-label" for="has_recipe">Has Recipe</label>
                                </div>
                            </div>

                            @if($has_recipe)
                            <div class="col-md-6">
                                <label class="form-label" for="recipe_id">Recipe <span
                                        class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select wire:model="recipe_id" id="recipe_id" class="selectpicker w-100"
                                        title="Select Recipe" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($recipes as $recipe)
                                        <option value="{{ $recipe->id }}" @selected($recipe->id == $recipe_id)>
                                            {{ $recipe->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('recipe_id')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            @else
                            <div class="col-md-6">
                                <label for="duration" class="form-label">Duration (minutes) <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="duration" name="duration"
                                    wire:model="duration" placeholder="Enter duration in minutes" min="1">
                                @error('duration')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            @endif

                        </div>
                    </div>
                </div>

                <div class="text-end mt-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="submit">
                        {{ $editing ? 'Update Event Type' : 'Create Event Type' }}
                    </button>
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

        Livewire.hook('morph.added', ({ el }) => {
            $('.selectpicker').selectpicker();
        });
    </script>
    @endscript
</div>