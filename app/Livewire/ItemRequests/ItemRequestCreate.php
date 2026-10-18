<?php

namespace App\Livewire\ItemRequests;

use App\Livewire\Concerns\PreventsDuplicateSubmission;
use App\Models\ItemRequest;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ItemRequestCreate extends Component
{
    use AuthorizesRequests, PreventsDuplicateSubmission;

    #[Locked]
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
        authorizeRequest($id ? 'production.itemRequest-edit' : 'production.itemRequest-create');

        $this->warehouses = $this->api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];

        // Every factory's raw materials — one call per item type, since the
        // parent filters on a single item type name.
        $this->rawMaterials = collect(['RM Massa Tomate', 'RM Bolacha'])
            ->flatMap(fn ($itemType) => $api->get('/v1/items', ['item_type' => $itemType, 'is_active' => true])['data'] ?? [])
            ->values()
            ->all();

        if ($id) {
            $request            = ItemRequest::with('inputs')->findOrFail($id);

            // Only a pending request is editable — the index hides Edit for the
            // rest, but the URL is still reachable.
            abort_if($request->status !== 'pending', 403, 'Only a pending request can be edited.');

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

        try {
            // One transaction, so an invalid row can't leave half a request behind.
            return DB::transaction(fn() => $this->persist());
        } catch (\Throwable $e) {
            // Two submits of this form raced past the check in persist().
            if ($this->isDuplicateSubmission($e)) {
                return redirect()->route('item-requests')->with('success', 'Request created successfully.');
            }

            throw $e;
        }
    }

    protected function persist()
    {
        if ($this->id) {
            $request = ItemRequest::lockForUpdate()->findOrFail($this->id);

            if ($request->status !== 'pending') {
                return redirect()->route('item-requests')->with('error', 'Only a pending request can be edited.');
            }

            $request->update([
                'notes'        => $this->notes,
                'warehouse_id' => $this->warehouse_id,
            ]);
            $message = 'Request updated successfully.';
        } else {
            // One form, one request: a repeated Submit finds the one already made.
            if ($this->alreadySubmitted(ItemRequest::class)) {
                return redirect()->route('item-requests')->with('success', 'Request created successfully.');
            }

            $request = ItemRequest::create([
                'code'         => $this->nextCode(),
                'status'       => 'pending',
                'notes'        => $this->notes,
                'warehouse_id' => $this->warehouse_id,
                // Users live in the auth service, so Laravel's own auth() is
                // always empty here — record the auth-service user instead.
                'requested_by'      => authUser()?->id,
                'requested_by_name' => authUser()?->name,
                'requested_at'      => now(),
                'submission_key'    => $this->submissionKey,
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

    /**
     * REQ-<unix time>, as before — plus -2, -3 … when another request already
     * took that second (the column is unique, deleted requests included), which
     * used to fail the save outright.
     */
    private function nextCode(): string
    {
        $base = 'REQ-' . time();
        $code = $base;

        for ($n = 2; ItemRequest::withTrashed()->where('code', $code)->exists(); $n++) {
            $code = "{$base}-{$n}";
        }

        return $code;
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
