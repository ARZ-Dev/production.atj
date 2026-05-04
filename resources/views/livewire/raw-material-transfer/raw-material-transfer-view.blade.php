<div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Transfer Details</h6>
          <a href="{{ route('transfers') }}" class="btn btn-light-light text-muted">
            <i class="bi bi-arrow-left me-1"></i>Back
          </a>
        </div>
        <div class="card-body">
          <div class="row justify-content-between mb-10">
            <div class="col-12 text-start">
              @if ($transfer->status =='pending')
              <span class="badge bg-warning mb-4">{{ $transfer->status }}</span>
              @else
              <span class="badge bg-success mb-4">{{ $transfer->status }}</span>
              @endif
              <h5 class="mb-0"># {{ $transfer->id }}</h5>
            </div>
          </div>
          <div class="row g-5 border-bottom border-dashed py-4">
            <div class="col-md-4">
              <h5 class="mb-4">Transfer Related To:</h5>
              <p><span class="fw-semibold">Company:</span> {{ $transfer->company->name }}</p>
              <p><span class="fw-semibold">From Warehouse:</span> {{ $transfer->warehouseFrom->name }}</p>
              <p><span class="fw-semibold">To Warehouse:</span> {{ $transfer->warehouseTo->name }}</p>
              <p><span class="fw-semibold">Created At:</span> {{ $transfer->created_at }}</p>
            </div>
          </div>
          @if ($transfer->reportItems->isNotEmpty())
          <div class="py-4">
            <div class="mt-4">
              <h5>Transfer Details</h5>
              <div class="table-responsive">
                <table class="table table-hover align-middle table-sm">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 60px;" class="text-center">#</th>
                      <th style="width: 200px;">Item</th>
                      <th style="width: 200px;">Unit</th>
                      <th>Quantity</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($transfer->reportItems as $index => $reportItem)
                    <tr>
                      <td class="text-center text-muted">{{ $index + 1 }}</td>
                      <td class="fw-semibold">{{ $reportItem->item->name }}</td>
                      <td>{{ $reportItem->itemUnit?->unit }}</td>
                      <td>{{ $reportItem->quantity }}</td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
</div>