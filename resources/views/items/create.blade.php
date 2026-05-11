<x-layouts.app title="Items Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="itemForm" enctype="multipart/form-data">
            @csrf

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $editing ? 'Edit' : 'Create' }} Item</h6>
                    <a href="{{ route('items.index') }}" class="btn btn-light-light text-muted">
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

                    <div class="row g-4">

                        {{-- Item Type --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold" for="item_type_id">
                                Item Type <span class="text-danger">*</span>
                            </label>
                            <select class="selectpicker w-100"
                                    name="item_type_id"
                                    id="item_type_id"
                                    title="Select Item Type"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
                                    required>
                                @foreach($item_types as $type)
                                    <option value="{{ $type['id'] }}"
                                        @selected(old('item_type_id', $item['item_type_id'] ?? '') == $type['id'])>
                                        {{ $type['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_type_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Sub Type --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold" for="sub_type_id">
                                Sub Type
                            </label>
                            <select class="selectpicker w-100"
                                    name="sub_type_id"
                                    id="sub_type_id"
                                    title="Select Sub Type"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white">
                                @foreach($sub_types as $subType)
                                    <option value="{{ $subType['id'] }}"
                                        @selected(old('sub_type_id', $item['sub_type_id'] ?? '') == $subType['id'])>
                                        {{ $subType['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sub_type_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="code"
                                   placeholder="Item code"
                                   value="{{ old('code', $item['code'] ?? '') }}"
                                   required>
                            @error('code')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Name --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="name"
                                   placeholder="Item name"
                                   value="{{ old('name', $item['name'] ?? '') }}"
                                   required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Weight --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">Weight</label>
                            <input type="number"
                                   class="form-control"
                                   name="weight"
                                   placeholder="Weight"
                                   step="0.0001"
                                   min="0"
                                   value="{{ old('weight', $item['weight'] ?? '') }}">
                            @error('weight')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Volume --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">Volume</label>
                            <input type="number"
                                   class="form-control"
                                   name="volume"
                                   placeholder="Volume"
                                   step="0.0001"
                                   min="0"
                                   value="{{ old('volume', $item['volume'] ?? '') }}">
                            @error('volume')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- VAT --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">VAT (%)</label>
                            <input type="number"
                                   class="form-control"
                                   name="vat"
                                   placeholder="VAT percentage"
                                   step="0.01"
                                   min="0"
                                   max="100"
                                   value="{{ old('vat', $item['vat'] ?? '') }}">
                            @error('vat')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Image --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">Image</label>
                            <input type="file"
                                   class="form-control"
                                   name="image"
                                   accept="image/jpg,image/jpeg,image/png,image/webp">
                            @error('image')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            @if($editing && !empty($item['image']))
                                <div class="mt-2">
                                    <img src="{{ $item['image'] }}" alt="Current Image"
                                         width="80" height="80"
                                         class="rounded object-fit-cover border">
                                    <small class="text-muted d-block mt-1">Current image — upload a new one to replace it.</small>
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <hr>
                            <h6 class="fw-semibold">Features</h6>
                        </div>

                        {{-- With Formula --}}
                        <div class="col-lg-4 col-sm-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="with_formula"
                                       id="with_formula"
                                       @checked(old('with_formula', $item['with_formula'] ?? true))>
                                <label class="form-check-label fw-medium" for="with_formula">
                                    With Formula
                                </label>
                            </div>
                        </div>

                        {{-- Is Active --}}
                        <div class="col-lg-4 col-sm-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       @checked(old('is_active', $item['is_active'] ?? true))>
                                <label class="form-check-label fw-medium" for="is_active">
                                    Is Active
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

         $('#item_type_id').on('change', function () {
            const itemTypeId = $(this).val();
            const $subType   = $('#sub_type_id');

            $subType.empty().append('<option value="">Select Sub Type</option>');
            $subType.selectpicker('refresh');

            if (!itemTypeId) return;

            $.get('/api/v1/item-sub-types', { item_type_id: itemTypeId }, function (res) {
                if (res.success && res.data.length) {
                    res.data.forEach(function (st) {
                        $subType.append(`<option value="${st.id}">${st.name}</option>`);
                    });
                }
                $subType.selectpicker('refresh');
            });
        });
    </script>