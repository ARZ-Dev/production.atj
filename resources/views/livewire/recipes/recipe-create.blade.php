<div>
    <form wire:submit.prevent="submit">
        {{-- View mode: every input is read-only (selectpickers also carry their
             own @disabled — bootstrap-select ignores a disabled fieldset). --}}
        <fieldset class="border-0 p-0 m-0" @disabled($viewing)>
        <div class="row">
            <div class="col-xl">

                {{-- ── Card 1: Recipe Header ──────────────────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $viewing ? 'View' : ($editing ? 'Edit' : 'Add') }} Recipe</h5>
                        <a href="{{ route('recipes') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="name">
                                    Recipe Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" class="form-control" wire:model="name"
                                    placeholder="Enter recipe name">
                                @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Recipe Type (from DB) --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="recipe_type">
                                    Recipe Type <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select @disabled($viewing) id="recipe_type" wire:model="recipe_type_id" class="selectpicker w-100"
                                        title="Select Type" data-style="btn-default" data-icon-base="ti"
                                        data-tick-icon="ti-check text-white">
                                        @foreach($recipeTypes as $rt)
                                        <option value="{{ $rt['id'] }}" @selected($recipe_type_id==$rt['id'])>
                                            {{ $rt['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('recipe_type_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($recipe_type_id)

                            {{-- Output Item Type --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="output_item_type">
                                    Output Item Type <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select @disabled($viewing) id="output_item_type" wire:model="output_item_type_id"
                                        class="selectpicker w-100" title="Select Item Type" data-style="btn-default"
                                        data-live-search="true" data-icon-base="ti" data-size="5"
                                        data-tick-icon="ti-check text-white">
                                        @foreach($itemTypes as $type)
                                        <option value="{{ $type['id'] }}" @selected($output_item_type_id==$type['id'])>
                                            {{ $type['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- @if($output_item_type_id) --}}

                            {{-- Output Item --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="header_item">
                                    Output Item <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select @disabled($viewing) id="header_item" wire:model="item_id" class="selectpicker w-100"
                                        title="Select Item" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($headerItems as $item)
                                        <option value="{{ $item['id'] }}" @selected($item_id==$item['id'])>
                                            {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('item_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Output Unit --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="header_unit">
                                    Output Unit <span class="text-danger">*</span>
                                </label>
                                <select @disabled($viewing) id="header_unit" wire:model="item_unit_id" class="selectpicker w-100"
                                    title="Select Unit" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($headerUnits as $unit)
                                    <option value="{{ $unit['id'] }}" @selected($item_unit_id==$unit['id'])>
                                        {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('item_unit_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- @endif --}}

                            {{-- Batch --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="batch">
                                    Batch Size
                                </label>
                                <input type="number" id="batch" class="form-control" wire:model="batch" value="1"
                                    disabled>
                                @error('batch')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Quantity Per Batch --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="quantity_per_batch">
                                    Quantity Per Batch <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="quantity_per_batch" class="form-control cleave-input"
                                    wire:model="quantity_per_batch" placeholder="e.g. 100">
                                @error('quantity_per_batch')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Batch Weight --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="batch_weight">Batch Weight (kg)</label>
                                <input type="text" id="batch_weight" class="form-control cleave-input" wire:model="batch_weight"
                                    placeholder="Optional">
                                @error('batch_weight')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Batch Volume --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="batch_volume">Batch Volume (M³)</label>
                                <input type="text" id="batch_volume" class="form-control cleave-input" wire:model="batch_volume"
                                    placeholder="Optional">
                                @error('batch_volume')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" id="recipe-status" wire:model="status">
                                    <label class="form-check-label" for="status">
                                        {{ $status ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea class="form-control" id="notes" wire:model="notes" rows="2"
                                    placeholder="Recipe notes (optional)"></textarea>
                            </div>

                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Line stages (optional): run the recipe as one event across a line ── --}}
                @if($recipe_type_id)
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0">Line Stages</h5>
                            <span class="badge bg-light-secondary text-muted">Optional</span>
                            @if($stages)<span class="ir-count">{{ count($stages) }}</span>@endif
                        </div>
                        <small class="text-muted">
                            Give the recipe stages to run it as <strong>one event across a line</strong> — cutting, cooking, packaging and packing all running at once.
                            Each stage runs on the line's station of the same name; each ingredient is used at one stage.
                        </small>
                    </div>
                    <div class="card-body">
                        @error('stages')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        @if($stages)
                        <div class="table-responsive">
                            <table class="table align-middle rs-table mb-2">
                                <thead>
                                    <tr>
                                        <th style="width: 72px">Order</th>
                                        <th>Stage <span class="text-danger">*</span></th>
                                        <th style="width: 120px">Makes per batch <span class="text-danger">*</span></th>
                                        <th style="width: 110px">Unit</th>
                                        <th style="width: 150px">Is the product in</th>
                                        <th style="width: 130px">Rejects go to</th>
                                        <th>Rework into</th>
                                        <th style="width: 100px">Rework per reject</th>
                                        <th style="width: 52px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stages as $key => $stage)
                                    <tr wire:key="stage-{{ $key }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="ir-idx">{{ $loop->iteration }}</span>
                                                @unless($viewing)
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <button type="button" class="btn btn-link p-0 lh-1" title="Move up"
                                                        wire:click="moveStage('{{ $key }}', -1)" @disabled($loop->first)>
                                                        <i class="bi bi-chevron-up"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-link p-0 lh-1" title="Move down"
                                                        wire:click="moveStage('{{ $key }}', 1)" @disabled($loop->last)>
                                                        <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                </div>
                                                @endunless
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" wire:model="stages.{{ $key }}.name" placeholder="e.g. Cooking">
                                            @error("stages.{$key}.name")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control cleave-input" wire:model="stages.{{ $key }}.output_quantity" placeholder="e.g. 1.2">
                                            @error("stages.{$key}.output_quantity")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" wire:model="stages.{{ $key }}.output_unit" placeholder="e.g. kg">
                                        </td>
                                        <td>
                                            <select class="form-select" wire:model="stages.{{ $key }}.output_item_unit_id" title="When this stage's output is the product itself in one of its units, what's left over at End can go into stock.">
                                                <option value="">— not the product —</option>
                                                @foreach($headerUnits as $unit)
                                                <option value="{{ $unit['id'] }}" @selected(($stage['output_item_unit_id'] ?? null) == $unit['id'])>{{ $unit['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select" wire:model.live="stages.{{ $key }}.reject_action">
                                                <option value="waste">Waste</option>
                                                <option value="rework">Rework</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select" wire:model="stages.{{ $key }}.rework_item_id" @disabled(($stage['reject_action'] ?? 'waste') !== 'rework')>
                                                <option value="">— none —</option>
                                                @foreach($reworkItems as $item)
                                                <option value="{{ $item['id'] }}" @selected(($stage['rework_item_id'] ?? null) == $item['id'])>{{ $item['name'] }}{{ $item['unit_name'] ? ' · ' . $item['unit_name'] : '' }}</option>
                                                @endforeach
                                            </select>
                                            @error("stages.{$key}.rework_item_id")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control cleave-input" wire:model="stages.{{ $key }}.rework_factor" placeholder="1">
                                        </td>
                                        <td class="text-end">
                                            @unless($viewing)
                                            <button type="button" class="btn btn-light-danger icon-btn-sm" title="Remove stage"
                                                wire:click="removeStage('{{ $key }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endunless
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Makes per batch</strong> is what the stage puts out for one batch of this recipe, in the unit its station's rate is in (kg through the oven, packets off the wrapper …).
                            The last stage is counted at End in the recipe's own output unit — that is what goes into stock.
                            <strong>Rework per reject</strong> is how much of the rework item one reject gives (e.g. 0.036 kg of powder from one 36 g packet).
                        </p>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="balance_tolerance">Tolerance at End (%)</label>
                                <input type="text" id="balance_tolerance" class="form-control cleave-input" wire:model="balance_tolerance" placeholder="1">
                                <div class="form-text">A material used more than this far from the recipe flags the run for review.</div>
                                @error('balance_tolerance')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="reject_allowance">Reject allowance (%)</label>
                                <input type="text" id="reject_allowance" class="form-control cleave-input" wire:model="reject_allowance" placeholder="0">
                                <div class="form-text">Extra dough planned on top of the recipe when mixes are planned for a run.</div>
                                @error('reject_allowance')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @endif

                        @unless($viewing)
                        <button type="button" class="ir-add mt-3" wire:click="addStage">
                            <i class="bi bi-plus-lg"></i> Add stage
                        </button>
                        @endunless
                    </div>
                </div>
                @endif

                {{-- ── Dynamic Ingredient Sections (one per item_type_id) ──────────── --}}
                @error('sections')
                <div class="alert alert-danger mb-4">{{ $message }}</div>
                @enderror
                @foreach($sections as $sectionIndex => $section)
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0">{{ $section['title'] }}</h5>
                            <span class="ir-count">{{ count($section['rows']) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="ir-editor">

                            {{-- Column header (desktop) --}}
                            <div class="ir-head {{ $stages ? 'ir-cols-stage' : 'ir-cols-notes' }}">
                                <div class="ir-idx-h">#</div>
                                <div>Item</div>
                                <div>Unit</div>
                                <div>Quantity{{ $stages ? ' per batch' : '' }}</div>
                                @if($stages)<div>Stage</div>@endif
                                <div>Notes</div>
                                <div></div>
                            </div>

                            @forelse($section['rows'] as $rowIndex => $row)
                            <div class="ir-row {{ $stages ? 'ir-cols-stage' : 'ir-cols-notes' }}" wire:key="section-{{ $sectionIndex }}-row-{{ $rowIndex }}">

                                {{-- Index --}}
                                <div class="ir-idx-cell"><span class="ir-idx">{{ $rowIndex + 1 }}</span></div>

                                {{-- Item --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Item</span>
                                    <div wire:ignore>
                                        <select @disabled($viewing) id="section_item_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.item_id"
                                            class="selectpicker w-100 section-item-select section-items-{{ $sectionIndex }}" title="Select Item"
                                            data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                            data-size="5" data-tick-icon="ti-check text-white"
                                            data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                            @foreach($sectionItems[$sectionIndex] ?? [] as $item)
                                            <option value="{{ $item['id'] }}"
                                                @selected($row['item_id']==$item['id'])>
                                                {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error("sections.{$sectionIndex}.rows.{$rowIndex}.item_id")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Unit --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Unit</span>
                                    <select @disabled($viewing) id="section_unit_{{ $sectionIndex }}_{{ $rowIndex }}"
                                        class="selectpicker w-100" title="Select Unit" data-style="btn-default"
                                        data-live-search="true" data-icon-base="ti" data-size="5"
                                        data-tick-icon="ti-check text-white"
                                        wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.item_unit_id"
                                        data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                        @foreach($section['rowUnits'][$rowIndex] ?? [] as $unit)
                                        <option value="{{ $unit['id'] }}"
                                            @selected($row['item_unit_id']==$unit['id'])>
                                            {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error("sections.{$sectionIndex}.rows.{$rowIndex}.item_unit_id")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Quantity --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Quantity</span>
                                    <input type="text"
                                        wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.quantity"
                                        id="section_qty_{{ $sectionIndex }}_{{ $rowIndex }}"
                                        class="form-control cleave-input" placeholder="Enter quantity">
                                    @error("sections.{$sectionIndex}.rows.{$rowIndex}.quantity")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Stage (recipes with line stages) --}}
                                @if($stages)
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Stage</span>
                                    <select class="form-select" wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.stage_key">
                                        <option value="">Select stage</option>
                                        @foreach($stages as $stageKey => $stage)
                                        <option value="{{ $stageKey }}" @selected(($row['stage_key'] ?? null) === $stageKey)>{{ $stage['name'] ?: 'Stage ' . $loop->iteration }}</option>
                                        @endforeach
                                    </select>
                                    @error("sections.{$sectionIndex}.rows.{$rowIndex}.stage_key")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif

                                {{-- Notes --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Notes</span>
                                    <input type="text"
                                        wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.notes"
                                        id="section_notes_{{ $sectionIndex }}_{{ $rowIndex }}"
                                        class="form-control" placeholder="Optional">
                                </div>

                                {{-- Remove --}}
                                <div class="ir-remove-cell">
                                    <button type="button" class="ir-remove" title="Remove item"
                                        wire:click="removeRowFromSection({{ $sectionIndex }}, {{ $rowIndex }})"
                                        @disabled(count($section['rows']) <= 1)>
                                        <i class="bi bi-trash"></i><span class="ir-remove-text">Remove</span>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="ir-empty">No items added yet.</div>
                            @endforelse

                            {{-- Add (bottom) --}}
                            @unless($viewing)
                            <button type="button" class="ir-add" wire:click="addRowToSection({{ $sectionIndex }})">
                                <i class="bi bi-plus-lg"></i> Add {{ $section['title'] }}
                            </button>
                            @endunless
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- ── Dynamic Side Product Sections (one per side_item_type_id) ───── --}}
                @foreach($sideSections as $sectionIndex => $section)
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0">{{ $section['title'] }}</h5>
                            <span class="badge bg-light-info text-info">Side Product</span>
                            <span class="badge bg-light-secondary text-muted">Optional</span>
                            <span class="ir-count">{{ count($section['rows']) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Side products are optional — leave a row empty to skip it.
                        </p>
                        <div class="ir-editor">

                            {{-- Column header (desktop) --}}
                            <div class="ir-head ir-cols-notes">
                                <div class="ir-idx-h">#</div>
                                <div>Item</div>
                                <div>Unit</div>
                                <div>Quantity</div>
                                <div>Notes</div>
                                <div></div>
                            </div>

                            @forelse($section['rows'] as $rowIndex => $row)
                            <div class="ir-row ir-cols-notes" wire:key="side-section-{{ $sectionIndex }}-row-{{ $rowIndex }}">

                                {{-- Index --}}
                                <div class="ir-idx-cell"><span class="ir-idx">{{ $rowIndex + 1 }}</span></div>

                                {{-- Item --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Item</span>
                                    <div wire:ignore>
                                        <select @disabled($viewing) id="side_section_item_{{ $sectionIndex }}_{{ $rowIndex }}"
                                            class="selectpicker w-100 side-section-item-select" title="Select Item"
                                            data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                            data-size="5" data-tick-icon="ti-check text-white"
                                            data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                            @foreach($sideSectionItems[$sectionIndex] ?? [] as $item)
                                            <option value="{{ $item['id'] }}"
                                                @selected($row['item_id']==$item['id'])>
                                                {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error("sideSections.{$sectionIndex}.rows.{$rowIndex}.item_id")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Unit --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Unit</span>
                                    <select @disabled($viewing) id="side_section_unit_{{ $sectionIndex }}_{{ $rowIndex }}"
                                        class="selectpicker w-100" title="Select Unit" data-style="btn-default"
                                        data-live-search="true" data-icon-base="ti" data-size="5"
                                        data-tick-icon="ti-check text-white"
                                        wire:model="sideSections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.item_unit_id"
                                        data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                        @foreach($section['rowUnits'][$rowIndex] ?? [] as $unit)
                                        <option value="{{ $unit['id'] }}"
                                            @selected($row['item_unit_id']==$unit['id'])>
                                            {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error("sideSections.{$sectionIndex}.rows.{$rowIndex}.item_unit_id")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Quantity --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Quantity</span>
                                    <input type="text"
                                        wire:model="sideSections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.quantity"
                                        id="side_section_qty_{{ $sectionIndex }}_{{ $rowIndex }}"
                                        class="form-control cleave-input" placeholder="Enter quantity">
                                    @error("sideSections.{$sectionIndex}.rows.{$rowIndex}.quantity")
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Notes --}}
                                <div class="ir-cell">
                                    <span class="ir-cell-label">Notes</span>
                                    <input type="text"
                                        wire:model="sideSections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.notes"
                                        id="side_section_notes_{{ $sectionIndex }}_{{ $rowIndex }}"
                                        class="form-control" placeholder="Optional">
                                </div>

                                {{-- Remove --}}
                                <div class="ir-remove-cell">
                                    <button type="button" class="ir-remove" title="Remove item"
                                        wire:click="removeRowFromSideSection({{ $sectionIndex }}, {{ $rowIndex }})"
                                        @disabled(count($section['rows']) <= 1)>
                                        <i class="bi bi-trash"></i><span class="ir-remove-text">Remove</span>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="ir-empty">No side products added.</div>
                            @endforelse

                            {{-- Add (bottom) --}}
                            @unless($viewing)
                            <button type="button" class="ir-add" wire:click="addRowToSideSection({{ $sectionIndex }})">
                                <i class="bi bi-plus-lg"></i> Add {{ $section['title'] }}
                            </button>
                            @endunless
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Submit --}}
                @if($recipe_type_id && !$viewing)
                <div class="col-12 text-end mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $editing ? 'Update Recipe' : 'Save Recipe' }}
                    </button>
                </div>
                @endif

            </div>
        </div>
        </fieldset>
    </form>

    @script
    <script>
        // ── Boot ─────────────────────────────────────────────────────────────
        $('.selectpicker').selectpicker();
        triggerCleave();

        // ── New DOM nodes (new rows / cards added by Livewire) ───────────────
        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('[id^="section_item_"], [id^="section_unit_"], [id^="side_section_item_"], [id^="side_section_unit_"], #header_item, #header_unit, #output_item_type').selectpicker();
            triggerCleave();
        });

        // ── After Livewire round-trip: re-init non-wire:ignore unit pickers ───
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                $nextTick(() => {
                    $('[id^="section_unit_"], [id^="side_section_unit_"], #header_unit').each(function () {
                        $(this).selectpicker('destroy').selectpicker();
                    });
                });
            });
        });

        // ── Recipe type changed: rebuild output item type picker options ──────
        $wire.on('output-item-types-changed', ({ itemTypes }) => {
            $nextTick(() => {
                if (typeof setOptions === 'function') {
                    setOptions($('#output_item_type'), itemTypes);
                }
            });
        });

        // ── Output item type changed: update header item picker options ────────
        $wire.on('output-type-changed', ({ headerItems }) => {
            $nextTick(() => {
                if (typeof setOptions === 'function') {
                    setOptions($('#header_item'), headerItems);
                }
            });
        });

        // ── Sync wire:ignore selectpickers to Livewire via wire:model attr ────
        $(document).on('change', '.selectpicker', function () {
            let wireModel = $(this).attr('wire:model');
            if (wireModel) {
                $wire.set(wireModel, $(this).val());
            }
        });

        $(document).on('change', '#output_item_type', function () {
            let value = $(this).val();
            if (value) {
                $wire.dispatch('getHeaderItems', { value: parseInt(value)});
            }
        });

        // ── Header item change: fetch units from server ───────────────────────
        $(document).on('change', '#header_item', function () {
            $wire.dispatch('getHeaderUnits', { itemId: $(this).val() });
        });

        $wire.on('setHeaderUnits', function (params) {
            let headerUnits = params[0];
            if (typeof setOptions === 'function') {
                setOptions($('#header_unit'), headerUnits);
            }
        });

        $wire.on('setSectionsItems', function (params) {
            let sectionItems = params[0];

            // loop over section items and update each section's item picker options
            for (const [sectionIndex, items] of Object.entries(sectionItems)) {
                $(`.section-items-${sectionIndex}`).each(function () {
                    setOptions($(`.section-items-${sectionIndex}`), items);
                });
            }

        });

        // ── Section item change: load units for that row ──────────────────────
        $(document).on('change', '.section-item-select', function () {
            const sectionIndex = parseInt($(this).data('section'));
            const rowIndex     = parseInt($(this).data('row'));
            const val          = $(this).val();

            if (isNaN(sectionIndex) || isNaN(rowIndex)) return;

            $wire.call('onSectionItemChanged', sectionIndex, rowIndex, val ? parseInt(val) : null);
        });

        // ── After row removed from a side section: re-sync picker values ─────
        $wire.on('side-section-rows-removed', ({ sectionIndex, rows }) => {
            $nextTick(() => {
                rows.forEach(function (itemId, i) {
                    $(`#side_section_item_${sectionIndex}_${i}`)
                        .selectpicker('val', itemId ? String(itemId) : '');
                });
            });
        });

        // ── Side section item change: load units for that row ─────────────────
        $(document).on('change', '.side-section-item-select', function () {
            const sectionIndex = parseInt($(this).data('section'));
            const rowIndex     = parseInt($(this).data('row'));
            const val          = $(this).val();

            if (isNaN(sectionIndex) || isNaN(rowIndex)) return;

            $wire.call('onSideSectionItemChanged', sectionIndex, rowIndex, val ? parseInt(val) : null);
        });
    </script>
    @endscript
</div>
