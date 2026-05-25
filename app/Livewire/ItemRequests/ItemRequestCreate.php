<?php

namespace App\Livewire\ItemRequests;

use App\Models\ItemRequest;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ItemRequestCreate extends Component
{
    use AuthorizesRequests;

    public $id;
    public $code;
    public $notes;
    public $items    = [];
    public $warehouses = [];
    public $warehouse_id;
    public $rawMaterials = [];
    public $rowUnits     = [];

    protected ApiService $api;

    protected $listeners = [
        'updateQuantity' => 'handleUpdateQuantity',
    ];

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, $id = null): void
    {
        authorizeRequest($id ? 'production.item-request-edit' : 'production.item-request-create');

        $this->warehouses = $api->get('/v1/warehouses', [
            'related_to_production' => true,
        ])['data'] ?? [];

        $this->rawMaterials = $api->get('/v1/items', [
            'item_type' => 'Raw Material',
            'is_active' => true,
        ])['data'] ?? [];

        if ($id) {
            $request            = ItemRequest::with('inputs')->findOrFail($id);
            $this->id           = $request->id;
            $this->code         = $request->code;
            $this->notes        = $request->notes;
            $this->warehouse_id = $request->warehouse_id;

            $this->items = $request->inputs->map(fn($input) => [
                'id'           => $input->id,
                'item_id'      => $input->item_id,
                'quantity'     => (float) $input->quantity,
                'item_unit_id' => $input->item_unit_id,
            ])->toArray();

            foreach ($this->items as $index => $item) {
                if ($item['item_id']) {
                    $this->rowUnits[$index] = $this->fetchUnitsForItem($item['item_id']);
                } else {
                    $this->rowUnits[$index] = [];
                }
            }
        }

        if (empty($this->items)) {
            $this->addRow();
        }
    }

    protected function fetchUnitsForItem(int $itemId): array
    {
        $response = $this->api->get("/v1/items/{$itemId}");
        return $response['data']['units'] ?? [];
    }

    public function handleUpdateQuantity(int $index, float $quantity): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = $quantity;
        }
    }

    public function updatedItems($value, $key): void
    {
        if (str_ends_with($key, '.item_id')) {
            $index = (int) explode('.', $key)[0];

            if ($value) {
                $units                  = $this->fetchUnitsForItem((int) $value);
                $this->rowUnits[$index] = $units;

                // Auto-select the basic unit, fallback to first
                $basicUnit = collect($units)->firstWhere('basic', true);
                $this->items[$index]['item_unit_id'] = $basicUnit['id'] ?? ($units[0]['id'] ?? null);
            } else {
                $this->rowUnits[$index]              = [];
                $this->items[$index]['item_unit_id'] = null;
            }
        }
    }

    public function addRow(): void
    {
        $this->items[]    = [
            'id'           => null,
            'item_id'      => null,
            'quantity'     => 0.0,
            'item_unit_id' => null,
        ];
        $this->rowUnits[] = [];
    }

    public function removeRow(int $index): void
    {
        unset($this->items[$index], $this->rowUnits[$index]);
        $this->items    = array_values($this->items);
        $this->rowUnits = array_values($this->rowUnits);
    }

    public function save()
    {
        $this->validate([
            'notes'                    => 'nullable|string',
            'warehouse_id'             => 'required|integer',
            'items'                    => 'required|array|min:1',
            'items.*.item_id'          => 'required|integer',
            'items.*.quantity'         => 'required|numeric|min:0.000001',
            'items.*.item_unit_id'     => 'required|integer',
        ]);

        if ($this->id) {
            $request = ItemRequest::findOrFail($this->id);
            $request->update([
                'notes'        => $this->notes,
                'warehouse_id' => $this->warehouse_id,
            ]);
            $message = 'Request updated successfully.';
        } else {
            $request = ItemRequest::create([
                'code'         => 'REQ-' . time(),
                'status'       => 'pending',
                'notes'        => $this->notes,
                'warehouse_id' => $this->warehouse_id,
                'requested_by' => auth()->id(),
                'requested_at' => now(),
            ]);
            $message = 'Request created successfully.';
        }

        $syncedIds = [];

        foreach ($this->items as $index => $item) {
            $material = collect($this->rawMaterials)->firstWhere('id', (int) $item['item_id']);
            abort_if(!$material, 422, 'Invalid item selected.');

            $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $item['item_unit_id']);
            abort_if(!$unit, 422, 'Invalid unit selected.');

            $baseQty = (float) $item['quantity'] * (float) ($unit['formula'] ?? 1);

            $data = [
                'item_id'       => $material['id'],
                'quantity'      => (float) $item['quantity'],
                'item_unit_id'  => $unit['id'],
                'base_quantity' => $baseQty,
                'base_unit_id'  => collect($this->rowUnits[$index])->firstWhere('basic', true)['id']
                                   ?? ($this->rowUnits[$index][0]['id'] ?? null),
            ];

            if (!empty($item['id'])) {
                $input = $request->inputs()->findOrFail($item['id']);
                $input->update($data);
            } else {
                $input = $request->inputs()->create($data);
            }

            $syncedIds[] = $input->id;
        }

        if ($this->id) {
            $request->inputs()->whereNotIn('id', $syncedIds)->delete();
        }

        return redirect()->route('item-requests')->with('success', $message);
    }

    public function render()
    {
        return view('livewire.item-requests.item-request-create', [
            'materials'  => $this->rawMaterials,
            'warehouses' => $this->warehouses,
            'rowUnits'   => $this->rowUnits,
        ]);
    }
}