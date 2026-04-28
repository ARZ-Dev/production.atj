<x-layouts.app title="Suppliers Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="supplierForm">
            @csrf

            <div class="card shadow-sm border-0">

                {{-- Card Header --}}
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $editing ? 'Edit' : 'Create' }} Supplier</h6>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-light-light text-muted">
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

                    {{-- ── Section 01: Company Information ──────────────── --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase text-muted fw-semibold mb-3"
                            style="font-size:.7rem;letter-spacing:.08em;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">01</span>
                            Company Information
                        </p>
                        <div class="row g-3">

                            {{-- Department --}}
                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-medium">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <select id="department_id" name="department_id"
                                    class="selectpicker form-control @error('department_id') is-invalid @enderror"
                                    title="Select department…" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($departments as $department)
                                    <option value="{{ $department['id'] }}" {{ old('department_id',
                                        $warehouse['department_id'] ?? '' )==$department['id'] ? 'selected' : '' }}>{{
                                        $department['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>


                            {{-- Company Name --}}
                            <div class="col-md-6">
                                <label for="company_name" class="form-label fw-medium">
                                    Company Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="company_name" name="company_name"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    placeholder="e.g. Acme Corporation"
                                    value="{{ old('company_name', $supplier['company_name'] ?? '') }}"
                                    autocomplete="off" />
                                @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Registration Number --}}
                            <div class="col-md-6">
                                <label for="company_registration_number" class="form-label fw-medium">
                                    Registration Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="company_registration_number" name="company_registration_number"
                                    class="form-control @error('company_registration_number') is-invalid @enderror"
                                    placeholder="e.g. REG-123456"
                                    value="{{ old('company_registration_number', $supplier['company_registration_number'] ?? '') }}"
                                    autocomplete="off" />
                                @error('company_registration_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Company Website --}}
                            <div class="col-md-6">
                                <label for="company_website" class="form-label fw-medium">
                                    Company Website
                                </label>
                                <input type="text" id="company_website" name="company_website"
                                    class="form-control @error('company_website') is-invalid @enderror"
                                    placeholder="https://example.com"
                                    value="{{ old('company_website', $supplier['company_website'] ?? '') }}"
                                    autocomplete="off" />
                                @error('company_website')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Company Phone --}}
                            <div class="col-md-6">
                                <label for="company_phone" class="form-label fw-medium">
                                    Company Phone <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="company_phone" name="company_phone"
                                    class="form-control @error('company_phone') is-invalid @enderror"
                                    placeholder="e.g. +1 234 567 8900"
                                    value="{{ old('company_phone', $supplier['company_phone'] ?? '') }}"
                                    autocomplete="off" />
                                @error('company_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ── Section 02: Location ─────────────────────────── --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase text-muted fw-semibold mb-3"
                            style="font-size:.7rem;letter-spacing:.08em;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">02</span>
                            Location
                        </p>
                        <div class="row g-3">

                            {{-- Country --}}
                            <div class="col-md-6">
                                <label for="country_id" class="form-label fw-medium">
                                    Country <span class="text-danger">*</span>
                                </label>
                                <select id="country_id" name="country_id"
                                    class="selectpicker form-control @error('country_id') is-invalid @enderror"
                                    title="Select country…" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($countries as $country)
                                    <option value="{{ $country['id'] }}" {{ old('country_id', $supplier['country_id']
                                        ?? '' )==$country['id'] ? 'selected' : '' }}>{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Province --}}
                            <div class="col-md-6">
                                <label for="province_id" class="form-label fw-medium">Province</label>
                                <select id="province_id" name="province_id"
                                    class="selectpicker form-control @error('province_id') is-invalid @enderror"
                                    title="Select province…" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province['id'] }}" {{ old('province_id', $supplier['province_id']
                                        ?? '' )==$province['id'] ? 'selected' : '' }}>{{ $province['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('province_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Municipality --}}
                            <div class="col-md-6">
                                <label for="municipality_id" class="form-label fw-medium">Municipality</label>
                                <select id="municipality_id" name="municipality_id"
                                    class="selectpicker form-control @error('municipality_id') is-invalid @enderror"
                                    title="Select municipality…" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($municipalities ?? [] as $municipality)
                                    <option value="{{ $municipality['id'] }}" {{ old('municipality_id',
                                        $supplier['municipality_id'] ?? '' )==$municipality['id'] ? 'selected' : '' }}>
                                        {{ $municipality['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('municipality_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Neighborhood --}}
                            <div class="col-md-6">
                                <label for="neighborhood_id" class="form-label fw-medium">Neighborhood</label>
                                <select id="neighborhood_id" name="neighborhood_id"
                                    class="selectpicker form-control @error('neighborhood_id') is-invalid @enderror"
                                    title="Select neighborhood…" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($neighborhoods ?? [] as $neighborhood)
                                    <option value="{{ $neighborhood['id'] }}" {{ old('neighborhood_id',
                                        $supplier['neighborhood_id'] ?? '' )==$neighborhood['id'] ? 'selected' : '' }}>
                                        {{ $neighborhood['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('neighborhood_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Company Address --}}
                            <div class="col-12">
                                <label for="company_address" class="form-label fw-medium">
                                    Company Address <span class="text-danger">*</span>
                                </label>
                                <textarea id="company_address" name="company_address"
                                    class="form-control @error('company_address') is-invalid @enderror"
                                    placeholder="Enter full company address"
                                    rows="3">{{ old('company_address', $supplier['company_address'] ?? '') }}</textarea>
                                @error('company_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Latitude --}}
                            <div class="col-md-6">
                                <label for="latitude" class="form-label fw-medium">
                                    Latitude <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="any" id="latitude" name="latitude"
                                    class="form-control @error('latitude') is-invalid @enderror"
                                    placeholder="e.g. 33.8886"
                                    value="{{ old('latitude', $supplier['latitude'] ?? '') }}" />
                                @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Longitude --}}
                            <div class="col-md-6">
                                <label for="longitude" class="form-label fw-medium">
                                    Longitude <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="any" id="longitude" name="longitude"
                                    class="form-control @error('longitude') is-invalid @enderror"
                                    placeholder="e.g. 35.4955"
                                    value="{{ old('longitude', $supplier['longitude'] ?? '') }}" />
                                @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ── Section 03: Point of Contact ─────────────────── --}}
                    <div class="p-4">
                        <p class="text-uppercase text-muted fw-semibold mb-3"
                            style="font-size:.7rem;letter-spacing:.08em;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">03</span>
                            Point of Contact
                        </p>
                        <div class="row g-3">

                            {{-- Contact Name --}}
                            <div class="col-md-6">
                                <label for="poc_name" class="form-label fw-medium">
                                    Contact Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="poc_name" name="poc_name"
                                    class="form-control @error('poc_name') is-invalid @enderror"
                                    placeholder="e.g. John Doe"
                                    value="{{ old('poc_name', $supplier['poc_name'] ?? '') }}" autocomplete="off" />
                                @error('poc_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Contact Email --}}
                            <div class="col-md-6">
                                <label for="poc_email" class="form-label fw-medium">
                                    Contact Email
                                </label>
                                <input type="text" id="poc_email" name="poc_email"
                                    class="form-control @error('poc_email') is-invalid @enderror"
                                    placeholder="e.g. john@example.com"
                                    value="{{ old('poc_email', $supplier['poc_email'] ?? '') }}" autocomplete="off" />
                                @error('poc_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Contact Phone --}}
                            <div class="col-md-6">
                                <label for="poc_phone" class="form-label fw-medium">
                                    Contact Phone <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="poc_phone" name="poc_phone"
                                    class="form-control @error('poc_phone') is-invalid @enderror"
                                    placeholder="e.g. +1 234 567 8900"
                                    value="{{ old('poc_phone', $supplier['poc_phone'] ?? '') }}" autocomplete="off" />
                                @error('poc_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                </div>{{-- end card-body --}}

                {{-- Card Footer --}}
                <div class="card-footer bg-light d-flex justify-content-end align-items-center px-4 py-3">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle"></i>
                        {{ $editing ? 'Update Supplier' : 'Save Supplier' }}
                    </button>
                </div>

            </div>{{-- end card --}}
        </form>
    </div>

</x-layouts.app>

<script>
    $(document).ready(function () {
        $('.selectpicker').selectpicker();

        $(document).on('change', '#country_id', function() {
            let countryId = $(this).val();
            let provinceSelector = $('#province_id');
            let municipalitySelector = $('#municipality_id');
            let neighborhoodSelector = $('#neighborhood_id');

            municipalitySelector.html('<option value="">Select municipality…</option>').selectpicker('refresh');
            neighborhoodSelector.html('<option value="">Select neighborhood…</option>').selectpicker('refresh');

            if (countryId) {
                $.ajax({
                    url: "{{ route('get-provinces', '%countryId%') }}".replace('%countryId%', countryId),
                    type: 'GET',
                    success: function (data) {
                        setOptions(provinceSelector, data.provinces);
                    },
                    error: function () {
                        provinceSelector.html('<option value="">Error loading provinces</option>').selectpicker('refresh');
                    }
                });
            } else {
                provinceSelector.html('<option value="">Select province…</option>').selectpicker('refresh');
                municipalitySelector.html('<option value="">Select municipality…</option>').selectpicker('refresh');
                neighborhoodSelector.html('<option value="">Select neighborhood…</option>').selectpicker('refresh');
            }
        })

    });
</script>
