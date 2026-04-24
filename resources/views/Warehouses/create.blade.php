<x-layouts.app title="Warehouses Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="warehouseForm">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

            <div class="card shadow-sm border-0">

                {{-- Card Header --}}
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $editing ? "Edit" : "Create" }} Warehouse</h6>
                    <a href="{{ route('warehouses.index') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>

                <div class="card-body p-0">

                    {{-- Validation Errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show m-4">
                            <strong><i class="bi bi-exclamation-triangle"></i> Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- ── Section 01: Basic Information ─────────────── --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.7rem;letter-spacing:.08em;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">01</span>
                            Basic Information
                        </p>
                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-medium">
                                    Warehouse Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. Central Distribution Hub"
                                    value="{{ old('name', $warehouse['name'] ?? '') }}"
                                    autocomplete="off"
                                />
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Shortname --}}
                            <div class="col-md-6">
                                <label for="shortname" class="form-label fw-medium">
                                    Short Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="shortname"
                                    name="shortname"
                                    class="form-control fw-semibold @error('shortname') is-invalid @enderror"
                                    placeholder="CDH"
                                    value="{{ old('shortname', $warehouse['shortname'] ?? '') }}"
                                    maxlength="50"
                                    autocomplete="off"
                                />
                                @error('shortname')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ── Section 02: Classification ──────────────────── --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.7rem;letter-spacing:.08em;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">02</span>
                            Classification
                        </p>
                        <div class="row g-3">

                            {{-- Warehouse Type --}}
                            <div class="col-md-6">
                                <label for="type_id" class="form-label fw-medium">
                                    Warehouse Type <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="type_id"
                                    name="type_id"
                                    class="selectpicker form-control @error('type_id') is-invalid @enderror"
                                    title="Select type…"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
                                >
                                    @foreach($warehouse_types as $type)
                                        <option
                                            value="{{ $type['id'] }}"
                                            {{ old('type_id', $warehouse['type_id'] ?? '') == $type['id'] ? 'selected' : '' }}
                                        >{{ $type['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('type_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Department --}}
                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-medium">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="department_id"
                                    name="department_id"
                                    class="selectpicker form-control @error('department_id') is-invalid @enderror"
                                    title="Select department…"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
                                >
                                    @foreach($departments as $department)
                                        <option
                                            value="{{ $department['id'] }}"
                                            {{ old('department_id', $warehouse['department_id'] ?? '') == $department['id'] ? 'selected' : '' }}
                                        >{{ $department['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Client Category --}}
                            <div class="col-md-6">
                                <label for="client_category_id" class="form-label fw-medium">
                                    Client Category
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal ms-1">Optional</span>
                                </label>
                                <select
                                    id="client_category_id"
                                    name="client_category_id"
                                    class="selectpicker form-control @error('client_category_id') is-invalid @enderror"
                                    title="Select client category…"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
                                >
                                    @foreach($client_categories as $category)
                                        <option
                                            value="{{ $category['id'] }}"
                                            {{ old('client_category_id', $warehouse['client_category_id'] ?? '') == $category['id'] ? 'selected' : '' }}
                                        >{{ $category['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('client_category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ── Section 03: Allowed Item Types ─────────────── --}}
                    <div class="p-4">
                        <p class="text-uppercase text-muted fw-semibold mb-3" style="font-size:.7rem;letter-spacing:.08em;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">03</span>
                            Allowed Item Types
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal ms-1">Optional</span>
                        </p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="items_type_id" class="form-label fw-medium d-flex justify-content-between">
                                    <span>Item Types</span>
                                    <small class="text-muted fw-normal fst-italic">Select all that apply</small>
                                </label>
                                @php
                                    $selectedItemTypes = old('items_type_id', $warehouse['items_type_id'] ?? []);
                                @endphp
                                <select
                                    id="items_type_id"
                                    name="items_type_id[]"
                                    class="selectpicker item-type-select form-control @error('items_type_id') is-invalid @enderror"
                                    multiple
                                    data-live-search="true"
                                    data-width="100%"
                                    data-style="btn-default"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
                                    data-actions-box="true"
                                    title="Select item types…"
                                >
                                    @foreach($item_types as $item)
                                        <option
                                            value="{{ $item['id'] }}"
                                            {{ in_array($item['id'], $selectedItemTypes) ? 'selected' : '' }}
                                        >{{ $item['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('items_type_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>{{-- end card-body --}}

                {{-- Card Footer --}}
                <div class="card-footer bg-light d-flex justify-content-end align-items-center px-4 py-3">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle"></i>
                        {{ $editing ? 'Update Warehouse' : 'Save Warehouse' }}
                    </button>
                </div>

            </div>{{-- end card --}}
        </form>
    </div>

</x-layouts.app>

<script>
    $(document).ready(function () {
        $('.selectpicker').selectpicker();

        // When department changes, fetch matching item types
        $('#department_id').on('change', function () {
            let departmentId = $(this).val();
            if (!departmentId) return;

            $.get(`/warehouses/department/${departmentId}`, function (response) {
                let itemTypes = response.data ?? [];
                let $select = $('.item-type-select');
                let selected = $select.val() ?? [];

                $select.empty();
                $.each(itemTypes, function (i, item) {
                    let isSelected = selected.includes(String(item.id));
                    $select.append(
                        $('<option>', {
                            value: item.id,
                            text: item.name,
                            selected: isSelected,
                        })
                    );
                });

                $select.selectpicker('destroy').selectpicker();
            });
        });
    });
</script>