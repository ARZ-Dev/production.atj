<x-layouts.app title="Item Types Management">

  <div class="row">
    <form action="{{ $route }}" method="POST" id="itemTypeForm">
      @csrf
    

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">{{ $editing ? "Edit" : "Create" }} Item Type</h6>
          <a href="{{ route('item-types.index') }}" class="btn btn-light-light text-muted">
            <i class="bi bi-arrow-left me-1"></i>Back
          </a>
        </div>

        <div class="card-body">

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
              <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
              <input type="text"
                     class="form-control @error('name') is-invalid @enderror"
                     name="name"
                     placeholder="Item type name"
                     value="{{ old('name', $item_type['name'] ?? '') }}">
              @error('name')
                <small class="text-danger">{{ $message }}</small>
              @enderror
            </div>

            {{-- Group Entity Relation --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Group Entity Relation <span class="text-danger">*</span></label>
              <select class="selectpicker w-100"
                      name="group_entity_relation_id"
                      id="group_entity_relation_id"
                      title="Select Relation"
                      data-style="btn-default"
                      data-live-search="true"
                      data-icon-base="ti"
                      data-size="5"
                      data-tick-icon="ti-check text-white"
                      required>
                @foreach($group_entity_relations as $relation)
                  <option value="{{ $relation['id'] }}"
                    @selected(old('group_entity_relation_id', $item_type['group_entity_relation_id'] ?? '') == $relation['id'])>
                    {{ getRelationName($relation) }}
                  </option>
                @endforeach
              </select>
              @error('group_entity_relation_id')
                <small class="text-danger">{{ $message }}</small>
              @enderror
            </div>

            {{-- Features --}}
            <div class="col-12">
              <hr>
              <h6 class="fw-semibold">Features</h6>
            </div>

            {{-- Has POS Suppliers --}}
            <div class="col-md-4">
              <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       name="has_pos_suppliers"
                       id="has_pos_suppliers"
                       @checked(old('has_pos_suppliers', $item_type['has_pos_suppliers'] ?? false))>
                <label class="form-check-label fw-medium" for="has_pos_suppliers">
                  Has POS Suppliers
                </label>
              </div>
            </div>
            {{-- Can Be Consumed In Recipe --}}
            <div class="col-md-4">
              <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       name="can_be_consumed_in_recipe"
                       id="can_be_consumed_in_recipe"
                       @checked(old('can_be_consumed_in_recipe', $item_type['can_be_consumed_in_recipe'] ?? false))>
                <label class="form-check-label fw-medium" for="can_be_consumed_in_recipe">
                  Can Be Consumed In Recipe
                </label>
              </div>
            </div>

            {{-- Sub Types --}}
            <div class="col-12">
              <hr>
              <h6 class="fw-semibold">Sub Types</h6>
            </div>

            <div class="col-12">
              <div class="row g-3" id="subTypesContainer">

                @php
                  $subTypeRows = old('subTypes', $item_type['sub_types'] ?? []);
                @endphp

                @foreach($subTypeRows as $index => $subType)
                  <div class="col-md-4 sub-type-row">
                    <label class="form-label fw-semibold">Sub Type Name</label>
                    <div class="input-group">
                      @if(!empty($subType['id']))
                        <input type="hidden"
                               name="subTypes[{{ $index }}][id]"
                               value="{{ $subType['id'] }}">
                      @endif
                      <input type="text"
                             class="form-control @error("subTypes.$index.name") is-invalid @enderror"
                             name="subTypes[{{ $index }}][name]"
                             placeholder="Sub type name"
                             value="{{ $subType['name'] ?? '' }}">
                      <button type="button" class="btn btn-danger remove-sub-type">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                    @error("subTypes.$index.name")
                      <small class="text-danger">{{ $message }}</small>
                    @enderror
                  </div>
                @endforeach

              </div>

              <div class="mt-3">
                <button type="button" class="btn btn-secondary btn-sm" id="addSubTypeBtn">
                  <i class="bi bi-plus-lg me-1"></i> Add Sub Type
                </button>
              </div>
            </div>

          </div>
        </div>

        <div class="card-footer">
          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('item-types.index') }}" class="btn btn-light-light text-muted">
              Cancel
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle me-1"></i> Submit
            </button>
          </div>
        </div>
      </div>

    </form>
  </div>

  <script>
    // ── Add new sub type row ───────────────────────────────────────────────
    document.getElementById('addSubTypeBtn').addEventListener('click', function () {
      const container = document.getElementById('subTypesContainer');
      const index     = container.querySelectorAll('.sub-type-row').length;

      const col     = document.createElement('div');
      col.className = 'col-md-4 sub-type-row';
      col.innerHTML = `
        <label class="form-label fw-semibold">Sub Type Name</label>
        <div class="input-group">
          <input type="text"
                 class="form-control"
                 name="subTypes[${index}][name]"
                 placeholder="Sub type name">
          <button type="button" class="btn btn-danger remove-sub-type">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      `;
      container.appendChild(col);
    });

    // ── Remove row (event delegation) ─────────────────────────────────────
    document.getElementById('subTypesContainer').addEventListener('click', function (e) {
      const btn = e.target.closest('.remove-sub-type');
      if (!btn) return;

      btn.closest('.sub-type-row').remove();

      // Re-index all rows so subTypes[0][name], [1][name]… stay sequential
      document.querySelectorAll('#subTypesContainer .sub-type-row').forEach((row, i) => {
        row.querySelectorAll('input').forEach(input => {
          input.name = input.name.replace(/subTypes\[\d+\]/, `subTypes[${i}]`);
        });
      });
    });

    // ── Bootstrap-select init ─────────────────────────────────────────────
    $('.selectpicker').selectpicker();
  </script>

</x-layouts.app>