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
              <label class="form-label fw-semibold">Name</label>
              <input type="text" class="form-control" name="name" placeholder="Item type name"
                value="{{ old('name', $item_type['name'] ?? '') }}">
              @error('name') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Group Entity Relation ID --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold">Group Entity Relation </label>
              <select class="selectpicker w-100" name="group_entity_relation_id" id="group_entity_relation_id"
                title="Select Relation" data-style="btn-default" data-live-search="true" data-icon-base="ti"
                data-size="5" data-tick-icon="ti-check text-white" required>
                @foreach($group_entity_relations as $relation)
                <option value="{{ $relation['id'] }}" @selected(old('group_entity_relation_id',
                  $item_type['group_entity_relation_id'] ?? '' )==$relation['id'])>
                  {{ $relation['name'] }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <hr>
              <h6 class="fw-semibold">Features</h6>
            </div>

            {{-- Has POS Suppliers --}}
            <div class="col-md-4">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="has_pos_suppliers" id="has_pos_suppliers"
                  @if(old('has_pos_suppliers')) checked @endif @if(isset($item_type) && $item_type['has_pos_suppliers'])
                  checked @endif>
                <label class="form-check-label fw-medium" for="has_pos_suppliers">
                  Has POS Suppliers
                </label>
              </div>
            </div>

          </div>
        </div>
        <div class="card-footer">
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
  <script>
        $('.selectpicker').selectpicker();

        $(document).on('change', '.selectpicker', function () {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });
  </script>