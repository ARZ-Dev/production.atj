<?php

namespace App\Livewire\Waste;

use App\Models\Waste;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class WasteCreate extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public bool $editing  = false;
    public $id;
    public $viewStatus;
    public $notes;
    public $warehouse_id;

    public $warehouses   = [];
    public $allItems     = [];   // raw material items from API
    public $rawMaterials = [];   // row data
    public $rowUnits     = [];   // units per row keyed by index

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, $id = null, $viewStatus = null): void
    {
        authorizeRequest('production.rawMaterialWaste-create');
        $this->viewStatus = $viewStatus;

        $this->warehouses = $api->get('/v1/warehouses', [
            'related_to_production' => true,
        ])['data'] ?? [];

        $this->allItems = $api->get('/v1/items', [
            'item_type' => 'Raw Material',
            'is_active'  => true,
        ])['data'] ?? [];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $waste              = Waste::with('inputs')->findOrFail($id);
            $this->warehouse_id = $waste->warehouse_id;
            $this->notes        = $waste->notes;

            $this->rawMaterials = $waste->inputs->map(fn($input) => [
                'id'           => $input->id,
                'item_id'      => $input->item_id,
                'item_unit_id' => $input->item_unit_id,
                'quantity'     => $input->quantity,
            ])->toArray();

            // Pre-load units for each existing row
            foreach ($this->rawMaterials as $index => $row) {
                $this->rowUnits[$index] = $row['item_id']
                    ? $this->fetchUnitsForItem($row['item_id'])
                    : [];
            }
        } else {
            $this->addRow();
        }
    }

    protected function fetchUnitsForItem(int $itemId): array
    {
        $response = $this->api->get("/v1/items/{$itemId}");
        return $response['data']['units'] ?? [];
    }

    public function updatedRawMaterials($value, $key): void
    {
        if (str_ends_with($key, '.item_id')) {
            $index = (int) explode('.', $key)[0];

            if ($value) {
                $units                  = $this->fetchUnitsForItem((int) $value);
                $this->rowUnits[$index] = $units;

                // Auto-select basic unit, fallback to first
                $basicUnit = collect($units)->firstWhere('basic', true);
                $this->rawMaterials[$index]['item_unit_id'] = $basicUnit['id'] ?? ($units[0]['id'] ?? null);
            } else {
                $this->rowUnits[$index]                     = [];
                $this->rawMaterials[$index]['item_unit_id'] = null;
            }
        }
    }

    public function addRow(): void
    {
        $this->rawMaterials[] = [
            'id'           => null,
            'item_id'      => null,
            'item_unit_id' => null,
            'quantity'     => '',
        ];
        $this->rowUnits[] = [];
    }

    public function removeItem(int $index): void
    {
        if (count($this->rawMaterials) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!',
            ]);
            return;
        }

        unset($this->rawMaterials[$index], $this->rowUnits[$index]);
        $this->rawMaterials = array_values($this->rawMaterials);
        $this->rowUnits     = array_values($this->rowUnits);
    }

    public function submit()
    {
        $this->validate([
            'warehouse_id'                => 'required|integer',
            'rawMaterials'                => 'required|array|min:1',
            'rawMaterials.*.item_id'      => 'required|integer',
            'rawMaterials.*.item_unit_id' => 'required|integer',
            'rawMaterials.*.quantity'     => 'required|numeric|min:0.000001',
        ], [
            'rawMaterials.required'                => 'Please add at least one item.',
            'rawMaterials.min'                     => 'Please add at least one item.',
            'rawMaterials.*.item_id.required'      => 'Item is required.',
            'rawMaterials.*.item_unit_id.required' => 'Unit is required.',
            'rawMaterials.*.quantity.required'     => 'Quantity is required.',
            'rawMaterials.*.quantity.numeric'      => 'Quantity must be a number.',
            'rawMaterials.*.quantity.min'          => 'Quantity must be greater than 0.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $waste = Waste::findOrFail($this->id);
                $waste->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            } else {
                $waste = Waste::create([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            }

            $syncedIds = [];

            foreach ($this->rawMaterials as $index => $row) {
                $item = collect($this->allItems)->firstWhere('id', (int) $row['item_id']);
                abort_if(!$item, 422, 'Invalid item selected.');

                $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                abort_if(!$unit, 422, 'Invalid unit selected.');

                // Ensure inventory record exists
                WarehouseInventory::firstOrCreate(
                    [
                        'warehouse_id' => $this->warehouse_id,
                        'item_id'      => $item['id'],
                        'item_unit_id' => $unit['id'],
                    ],
                    ['quantity' => 0]
                );

                $data = [
                    'warehouse_id'  => $this->warehouse_id,
                    'item_id'       => $item['id'],
                    'item_unit_id'  => $unit['id'],
                    'quantity'      => (float) $row['quantity'],
                ];

                if (!empty($row['id'])) {
                    $input = $waste->reportItems()->findOrFail($row['id']);
                    $input->update($data);
                } else {
                    $input = $waste->reportItems()->create($data);
                }

                $syncedIds[] = $input->id;
            }

            if ($this->editing) {
                $waste->reportItems()->whereNotIn('id', $syncedIds)->delete();
            }

            DB::commit();

            return to_route('item-wastes')->with(
                'success',
                $this->editing ? 'Waste updated successfully!' : 'Waste created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.waste.waste-view');
        }

        return view('livewire.waste.waste-create', [
            'items'      => $this->allItems,
            'warehouses' => $this->warehouses,
            'rowUnits'   => $this->rowUnits,
        ]);
    }
}