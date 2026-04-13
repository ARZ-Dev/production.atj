<x-layouts.app title="Warehouse Types Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="warehouseTypeForm">
            @csrf

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $editing ? "Edit" : "Create" }} Warehouse Type</h6>
                    <a href="{{ route('warehouse-types.index') }}" class="btn btn-light-light text-muted">
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
                            <input type="text" class="form-control"
                                   name="name"
                                   placeholder="Warehouse type name"
                                   value="{{ old('name', $warehouse_type['name'] ?? '') }}"
                            >
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="col-12">
                            <hr>
                            <h6 class="fw-semibold">Permissions & Features</h6>
                        </div>

                        @php
                            $switches = [
                                'can_receive_transfer' => 'Receive Transfer',
                                'can_receive_local_incoming_order' => 'Receive Local Incoming Order',
                                'can_receive_international_incoming_order' => 'Receive International Incoming Order',
                                'can_receive_production' => 'Receive Production',
                                'can_send_transfer' => 'Send Transfer',
                                'can_send_order' => 'Send Order',
                                'can_request_product' => 'Request Product',
                                'have_pos' => 'Has POS',
                                'can_distribute' => 'Can Distribute',
                                'can_return_distribute' => 'Can Return Distribute',
                            ];
                        @endphp

                        @foreach($switches as $field => $label)
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="{{ $field }}"
                                           id="{{ $field }}"
                                           @if(old($field)) checked @endif
                                           @if(isset($warehouse_type) && $warehouse_type[$field]) checked @endif
                                    >
                                    <label class="form-check-label fw-medium" for="{{ $field }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="card-footer">
                    {{-- Submit --}}
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

