<?php

namespace App\Livewire\RawMaterialRequests;

use App\Models\RawMaterial;
use App\Models\RawMaterialRequest;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RawMaterialRequestCreate extends Component
{
    use AuthorizesRequests;

    public $id;
    public $code;
    public $notes;
    public $items = [];

    protected $listeners = [
        'updateQuantity' => 'handleUpdateQuantity',
    ];

    public function mount($id = null)
    {
        if ($id) {
            $request        = RawMaterialRequest::with('items')->findOrFail($id);
            $this->id       = $request->id;
            $this->code     = $request->code;
            $this->notes    = $request->notes;
            $this->items    = $request->items->map(fn($item) => [
                'id'              => $item->id,
                'raw_material_id' => $item->raw_material_id,
                'quantity'        => (float) $item->quantity,
                'unit_id'         => $item->unit_id,
            ])->toArray();
        }

        if (empty($this->items)) {
            $this->addRow();
        }
    }

    public function handleUpdateQuantity(int $index, float $quantity): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = $quantity;
        }
    }

    public function updatedItems($value, $key): void
    {
        // When raw_material_id changes on any item, auto-set unit_id to its purchase unit
        if (str_ends_with($key, '.raw_material_id') && $value) {
            $index    = explode('.', $key)[0];
            $material = RawMaterial::find($value);

            if ($material && $material->purchase_unit_id) {
                $this->items[$index]['unit_id'] = $material->purchase_unit_id;
            }
        }
    }

    public function addRow(): void
    {
        $this->items[] = [
            'id'              => null,
            'raw_material_id' => null,
            'quantity'        => 0.0,
            'unit_id'         => null,
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity'        => 'required|numeric|min:0.000001',
            'items.*.unit_id'         => 'required|exists:units,id',
        ]);

        if ($this->id) {

            $request = RawMaterialRequest::findOrFail($this->id);
            $request->update(['notes' => $this->notes]);

            $message = 'Request updated successfully.';
        } else {
            $request = RawMaterialRequest::create([
                'code'         => 'REQ-' . time(),
                'status'       => 'pending',
                'notes'        => $this->notes,
                'requested_by' => auth()->id(),
                'requested_at' => now(),
            ]);

            $message = 'Request created successfully.';
        }

        $syncedIds = [];

        foreach ($this->items as $item) {
            $material = RawMaterial::findOrFail($item['raw_material_id']);
            $unit     = Unit::findOrFail($item['unit_id']);
            $baseQty  = (float) $item['quantity'] * (float) $unit->conversion_factor_to_base;

            $data = [
                'raw_material_id' => $material->id,
                'quantity'        => (float) $item['quantity'],
                'unit_id'         => $unit->id,
                'base_quantity'   => $baseQty,
                'base_unit_id'    => $material->base_unit_id,
            ];

            if (!empty($item['id'])) {
                $requestItem = $request->items()->findOrFail($item['id']);
                $requestItem->update($data);
            } else {
                $requestItem = $request->items()->create($data);
            }

            $syncedIds[] = $requestItem->id;
        }

        if ($this->id) {
            $request->items()
                ->whereNotIn('id', $syncedIds)
                ->delete();
        }

        redirect()->route('raw-material-requests')->with('success', $message);
    }

    public function render()
    {
        return view('livewire.raw-material-requests.raw-material-request-create', [
            'materials' => RawMaterial::where('is_active', true)->with('purchaseUnit')->get(),
        ]);
    }
}