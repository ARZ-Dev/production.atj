<x-layouts.app title="Items Management">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Item List</h5>
      @hasPermission('production.item-create')
      <a href="{{ route('items.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add New Item
      </a>
      @endhasPermission
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
          <thead>
            <tr>
              <th>ID</th>
              <th>Image</th>
              <th>Code</th>
              <th>Name</th>
              <th>Item Type</th>
              <th>Sub Type</th>
              <th>VAT %</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
            <tr>
              <td>{{ $item['id'] }}</td>
              <td>
                @if($item['image'])
                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" width="40" height="40"
                  class="rounded object-fit-cover">
                @else
                <span class="text-muted">—</span>
                @endif
              </td>
              <td>{{ $item['code'] }}</td>
              <td>{{ $item['name'] }}</td>
              <td>{{ $item['item_type']['name'] ?? '—' }}</td>
              <td>{{ $item['sub_type']['name'] ?? '—' }}</td>
              <td>{{ $item['vat'] ?? '—' }}</td>
              <td>
                @if($item['is_active'])
                <span class="badge bg-success">Active</span>
                @else
                <span class="badge bg-danger">Inactive</span>
                @endif
              </td>
              <td class="text-center">
                <a href="{{ route('items.edit', $item['id']) }}" class="btn btn-light-primary icon-btn-sm"
                  data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white" data-bs-placement="top"
                  data-bs-title="Edit">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <button type="button" class="btn btn-light-danger icon-btn-sm delete-item" data-bs-toggle="tooltip"
                  data-bs-custom-class="tooltip-white" data-bs-placement="top" data-bs-title="Delete"
                  data-id="{{ $item['id'] }}" data-name="{{ $item['name'] }}">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-layouts.app>

<script>
  $(document).on('click', '.delete-item', function () {
        let itemId   = $(this).data('id');
        let itemName = $(this).data('name');

        Swal.fire({
            title: 'Delete Item?',
            text: "You are about to delete \"" + itemName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('items.destroy', '%itemId%') }}".replace('%itemId%', itemId);
            }
        });
    });
</script>