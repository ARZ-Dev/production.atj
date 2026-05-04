<div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Stock In Details</h6>
          <a href="{{ route('stock-ins') }}" class="btn btn-light-light text-muted">
            <i class="bi bi-arrow-left me-1"></i>Back
          </a>
        </div>
        <div class="card-body">
          <div class="row justify-content-between mb-10">
            <div class="col-12 text-start">
              @if ($stockIn->status =='pending')
              <span class="badge bg-warning mb-4">{{ $stockIn->status }}</span>
              @else
              <span class="badge bg-success mb-4">{{ $stockIn->status }}</span>
              @endif
              <h5 class="mb-0"># {{ $stockIn->id }}</h5>
            </div>
          </div>
          <div class="row g-5 border-bottom border-dashed py-4">
            <div class="col-md-4">
              <h5 class="mb-4">Stock In Related To:</h5>
              <p><span class="fw-semibold">Company:</span> {{ $stockIn->company->name }}</p>
              <p><span class="fw-semibold">Warehouse:</span> {{ $stockIn->warehouse->name }}</p>
              <p><span class="fw-semibold">Created At:</span> {{ $stockIn->created_at }}</p>
            </div>
          </div>
          @if ($stockIn->reportItems->isNotEmpty())
          <div class="py-4">
            <div class="mt-4">
              <h5>Stock In Details</h5>
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
                    @foreach ($stockIn->reportItems as $index => $reportItem)
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