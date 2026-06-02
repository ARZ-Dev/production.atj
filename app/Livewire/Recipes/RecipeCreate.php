<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use App\Models\RecipeInput;
use App\Services\ApiService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RecipeCreate extends Component
{
    use AuthorizesRequests;

    // ─── State ────────────────────────────────────────────────────────────────
    public bool $editing = false;
    public ?int $id      = null;

    // ─── Recipe Header Fields ─────────────────────────────────────────────────
    public ?string $name         = null;
    public ?int    $recipe_type  = null; // 1 = Preparation, 2 = Production
    public ?int    $item_id      = null;
    public ?int    $item_unit_id = null;
    public ?int    $batch        = null;
    public ?string $batch_weight = null;
    public ?string $batch_volume = null;
    public bool    $status       = true;
    public ?string $notes        = null;

    // ─── Item lists (loaded per recipe_type) ──────────────────────────────────
    public array $headerItems = [];
    public array $headerUnits = [];

    // ─── Input rows ───────────────────────────────────────────────────────────
    public array $inputRows     = [];
    public array $inputRowUnits = [];

    // ─── Packaging rows (production only) ────────────────────────────────────
    public array $packagingRows     = [];
    public array $packagingRowUnits = [];

    // ─── Available items per section ──────────────────────────────────────────
    public array $inputItems     = [];
    public array $packagingItems = [];

    // ─── Item type constants ──────────────────────────────────────────────────
    const TYPE_RAW_MATERIAL      = 'Raw Material';
    const TYPE_SEMI_FINISHED     = 'Semi-Finished Goods';
    const TYPE_FINISHED_GOODS    = 'Finished Goods';
    const TYPE_PACKAGING         = 'Packaging Material';

    // ─── API ──────────────────────────────────────────────────────────────────
    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, ?int $id = null): void
    {
        authorizeRequest('production.recipe-create');

        if ($id) {
            $this->id      = $id;
            $this->editing = true;
            $this->loadRecipe($id);
        } else {
            $this->addInputRow();
        }
    }

    // ─── Load existing recipe for edit ───────────────────────────────────────
    protected function loadRecipe(int $id): void
    {
        $recipe = Recipe::with('inputs')->findOrFail($id);

        $this->name         = $recipe->name;
        $this->recipe_type  = $recipe->recipe_type;
        $this->item_id      = $recipe->item_id;
        $this->item_unit_id = $recipe->item_unit_id;
        $this->batch        = $recipe->batch;
        $this->batch_weight = $recipe->batch_weight;
        $this->batch_volume = $recipe->batch_volume;
        $this->status       = (bool) $recipe->status;
        $this->notes        = $recipe->notes;

        if ($this->recipe_type == 1) {
            // Preparation: output = Semi-Finished Goods, inputs = Raw Material
            $this->headerItems    = $this->fetchItemsByType(self::TYPE_SEMI_FINISHED);
            $this->inputItems     = $this->fetchItemsByType(self::TYPE_RAW_MATERIAL);
            $this->packagingItems = [];
        } elseif ($this->recipe_type == 2) {
            // Production: output = all items except Raw Material, Semi-Finished Goods, Packaging Material
            $this->headerItems    = $this->fetchItemsExcludingTypes([
                self::TYPE_RAW_MATERIAL,
                self::TYPE_SEMI_FINISHED,
                self::TYPE_PACKAGING,
            ]);
            $this->inputItems     = $this->fetchItemsByType(self::TYPE_SEMI_FINISHED);
            $this->packagingItems = $this->fetchItemsByType(self::TYPE_PACKAGING);
        }

        if ($this->item_id) {
            $this->headerUnits = $this->fetchUnitsForItem($this->item_id);
        }

        $inputType     = $this->recipe_type == 1 ? 1 : 2;
        $packagingType = 3;

        $inputRecords    = $recipe->inputs->where('input_type', $inputType)->values();
        $packagingRecords = $recipe->inputs->where('input_type', $packagingType)->values();

        $this->inputRows = $inputRecords->map(fn($i) => [
            'id'           => $i->id,
            'item_id'      => $i->item_id,
            'item_unit_id' => $i->item_unit_id,
            'quantity'     => $i->quantity,
            'notes'        => $i->notes,
        ])->toArray();

        foreach ($this->inputRows as $index => $row) {
            $this->inputRowUnits[$index] = $row['item_id']
                ? $this->fetchUnitsForItem($row['item_id'])
                : [];
        }

        if ($this->recipe_type == 2) {
            $this->packagingRows = $packagingRecords->map(fn($i) => [
                'id'           => $i->id,
                'item_id'      => $i->item_id,
                'item_unit_id' => $i->item_unit_id,
                'quantity'     => $i->quantity,
                'notes'        => $i->notes,
            ])->toArray();

            foreach ($this->packagingRows as $index => $row) {
                $this->packagingRowUnits[$index] = $row['item_id']
                    ? $this->fetchUnitsForItem($row['item_id'])
                    : [];
            }
        }

        if (empty($this->inputRows)) {
            $this->addInputRow();
        }
    }

    // ─── Load item lists based on recipe type ─────────────────────────────────
    public function loadItemsForType(int $type): void
    {
        if ($type == 1) {
            // Preparation: output = Semi-Finished Goods, inputs = Raw Material
            $this->headerItems    = $this->fetchItemsByType(self::TYPE_SEMI_FINISHED);
            $this->inputItems     = $this->fetchItemsByType(self::TYPE_RAW_MATERIAL);
            $this->packagingItems = [];
        } elseif ($type == 2) {
            // Production: output = all items except Raw Material, Semi-Finished Goods, Packaging Material
            $this->headerItems    = $this->fetchItemsExcludingTypes([
                self::TYPE_RAW_MATERIAL,
                self::TYPE_SEMI_FINISHED,
                self::TYPE_PACKAGING,
            ]);
            $this->inputItems     = $this->fetchItemsByType(self::TYPE_SEMI_FINISHED);
            $this->packagingItems = $this->fetchItemsByType(self::TYPE_PACKAGING);
        }

        $this->dispatch('setItems', ['headerItems' => $this->headerItems, 'inputItems' => $this->inputItems, 'packagingItems' => $this->packagingItems]);
    }

    protected function fetchItemsByType(string $type): array
    {
        return $this->api->get('/v1/items', [
            'item_type' => $type,
            'is_active' => true,
        ])['data'] ?? [];
    }

    protected function fetchItemsExcludingTypes(array $excludedTypes): array
    {
        $items = $this->api->get('/v1/items', [
            'is_active' => true,
        ])['data'] ?? [];

        return array_values(array_filter(
            $items,
            fn($item) => !in_array($item['item_type'] ?? null, $excludedTypes, true)
        ));
    }

    protected function fetchUnitsForItem(int $itemId): array
    {
        return $this->api->get("/v1/items/{$itemId}")['data']['units'] ?? [];
    }

    // ─── JS-callable methods for wire:ignore item pickers ────────────────────
    public function onInputItemChanged(int $index, ?int $itemId): void
    {
        $this->inputRows[$index]['item_id']      = $itemId;
        $this->inputRows[$index]['item_unit_id'] = null;

        if ($itemId) {
            $units = $this->fetchUnitsForItem($itemId);
            $this->inputRowUnits[$index] = $units;
            $basic = collect($units)->firstWhere('basic', true);
            $this->inputRows[$index]['item_unit_id'] = $basic['id'] ?? ($units[0]['id'] ?? null);
        } else {
            $this->inputRowUnits[$index] = [];
        }
    }

    public function onPackagingItemChanged(int $index, ?int $itemId): void
    {
        $this->packagingRows[$index]['item_id']      = $itemId;
        $this->packagingRows[$index]['item_unit_id'] = null;

        if ($itemId) {
            $units = $this->fetchUnitsForItem($itemId);
            $this->packagingRowUnits[$index] = $units;
            $basic = collect($units)->firstWhere('basic', true);
            $this->packagingRows[$index]['item_unit_id'] = $basic['id'] ?? ($units[0]['id'] ?? null);
        } else {
            $this->packagingRowUnits[$index] = [];
        }
    }

    // ─── Watchers ─────────────────────────────────────────────────────────────
    public function updatedRecipeType($value): void
    {
        $value             = $value ? (int) $value : null;
        $this->recipe_type = $value;

        $this->item_id      = null;
        $this->item_unit_id = null;
        $this->headerUnits  = [];

        $this->inputRows         = [];
        $this->inputRowUnits     = [];
        $this->packagingRows     = [];
        $this->packagingRowUnits = [];

        if ($value) {
            $this->loadItemsForType($value);
            $this->addInputRow();

            if ($value == 2) {
                $this->addPackagingRow();
            }

            $this->dispatch('input-items-updated',    items: $this->inputItems);
            $this->dispatch('packaging-items-updated', items: $this->packagingItems);
        }
    }

    public function updatedItemId(?int $value): void
    {
        $this->item_unit_id = null;
        $this->headerUnits  = $value ? $this->fetchUnitsForItem($value) : [];
    }

    #[On('getHeaderUnits')]
    public function getHeaderUnits($itemId)
    {
        $this->headerUnits  = $itemId ? $this->fetchUnitsForItem($itemId) : [];
        $this->dispatch('setHeaderUnits', $this->headerUnits);
    }

    // ─── Row management ───────────────────────────────────────────────────────
    public function addInputRow(): void
    {
        $this->inputRows[]     = ['id' => null, 'item_id' => null, 'item_unit_id' => null, 'quantity' => '', 'notes' => null];
        $this->inputRowUnits[] = [];
    }

    public function removeInputRow(int $index): void
    {
        if (count($this->inputRows) <= 1) {
            $this->dispatch('swal:error', ['title' => 'Warning', 'text' => 'At least one ingredient is required!']);
            return;
        }
        unset($this->inputRows[$index], $this->inputRowUnits[$index]);
        $this->inputRows     = array_values($this->inputRows);
        $this->inputRowUnits = array_values($this->inputRowUnits);

        $this->dispatch('rows-removed', rows: array_map(fn($r) => $r['item_id'], $this->inputRows), type: 'input');
    }

    public function addPackagingRow(): void
    {
        $this->packagingRows[]     = ['id' => null, 'item_id' => null, 'item_unit_id' => null, 'quantity' => '', 'notes' => null];
        $this->packagingRowUnits[] = [];
    }

    public function removePackagingRow(int $index): void
    {
        if (count($this->packagingRows) <= 1) {
            $this->dispatch('swal:error', ['title' => 'Warning', 'text' => 'At least one packaging item is required!']);
            return;
        }
        unset($this->packagingRows[$index], $this->packagingRowUnits[$index]);
        $this->packagingRows     = array_values($this->packagingRows);
        $this->packagingRowUnits = array_values($this->packagingRowUnits);

        $this->dispatch('rows-removed', rows: array_map(fn($r) => $r['item_id'], $this->packagingRows), type: 'packaging');
    }

    // ─── Submit ───────────────────────────────────────────────────────────────
    public function submit(): void
    {
        $this->validate([
            'name'                         => 'required|string|max:255',
            'recipe_type'                  => 'required|in:1,2',
            'item_id'                      => 'required|integer',
            'item_unit_id'                 => 'required|integer',
            'batch'                        => 'required|integer|min:1',
            'batch_weight'                 => 'nullable|numeric|min:0',
            'batch_volume'                 => 'nullable|numeric|min:0',
            'inputRows'                    => 'required|array|min:1',
            'inputRows.*.item_id'          => 'required|integer',
            'inputRows.*.item_unit_id'     => 'required|integer',
            'inputRows.*.quantity'         => 'required|numeric|min:0.0001',
            'packagingRows.*.item_id'      => 'required_if:recipe_type,2|integer',
            'packagingRows.*.item_unit_id' => 'required_if:recipe_type,2|integer',
            'packagingRows.*.quantity'     => 'required_if:recipe_type,2|numeric|min:0.0001',
        ], [
            'name.required'                       => 'Recipe name is required.',
            'recipe_type.required'                => 'Recipe type is required.',
            'item_id.required'                    => 'Output item is required.',
            'item_unit_id.required'               => 'Output unit is required.',
            'batch.required'                      => 'Batch size is required.',
            'batch.min'                           => 'Batch must be at least 1.',
            'inputRows.required'                  => 'Please add at least one ingredient.',
            'inputRows.*.item_id.required'        => 'Item is required.',
            'inputRows.*.item_unit_id.required'   => 'Unit is required.',
            'inputRows.*.quantity.required'       => 'Quantity is required.',
            'inputRows.*.quantity.min'            => 'Quantity must be greater than 0.',
            'packagingRows.*.item_id.required_if' => 'Packaging item is required.',
            'packagingRows.*.quantity.required_if'=> 'Packaging quantity is required.',
        ]);

        DB::beginTransaction();
        try {
            $recipeData = [
                'name'         => $this->name,
                'recipe_type'  => $this->recipe_type,
                'item_id'      => $this->item_id,
                'item_unit_id' => $this->item_unit_id,
                'batch'        => $this->batch,
                'batch_weight' => $this->batch_weight,
                'batch_volume' => $this->batch_volume,
                'status'       => $this->status,
                'notes'        => $this->notes,
            ];

            $recipe = $this->editing
                ? tap(Recipe::findOrFail($this->id))->update($recipeData)
                : Recipe::create($recipeData);

            $syncedIds = [];
            $inputType = $this->recipe_type == 1 ? 1 : 2;

            foreach ($this->inputRows as $index => $row) {
                $unit = collect($this->inputRowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                abort_if(!$unit, 422, 'Invalid unit for ingredient row ' . ($index + 1));

                $data = [
                    'input_type'   => $inputType,
                    'item_id'      => (int) $row['item_id'],
                    'item_unit_id' => (int) $row['item_unit_id'],
                    'quantity'     => (float) $row['quantity'],
                    'notes'        => $row['notes'] ?? null,
                ];

                if (!empty($row['id'])) {
                    $input = RecipeInput::findOrFail($row['id']);
                    $input->update($data);
                } else {
                    $input = $recipe->inputs()->create($data);
                }
                $syncedIds[] = $input->id;
            }

            if ($this->recipe_type == 2) {
                foreach ($this->packagingRows as $index => $row) {
                    if (empty($row['item_id'])) continue;

                    $data = [
                        'input_type'   => 3,
                        'item_id'      => (int) $row['item_id'],
                        'item_unit_id' => (int) $row['item_unit_id'],
                        'quantity'     => (float) $row['quantity'],
                        'notes'        => $row['notes'] ?? null,
                    ];

                    if (!empty($row['id'])) {
                        $input = RecipeInput::findOrFail($row['id']);
                        $input->update($data);
                    } else {
                        $input = $recipe->inputs()->create($data);
                    }
                    $syncedIds[] = $input->id;
                }
            }

            if ($this->editing) {
                $recipe->inputs()->whereNotIn('id', $syncedIds)->delete();
            }

            DB::commit();

            redirect()->route('recipes')->with(
                'success',
                $this->editing ? 'Recipe updated successfully!' : 'Recipe created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', ['title' => 'Error', 'text' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.recipes.recipe-create', [
            'headerItems'       => $this->headerItems,
            'headerUnits'       => $this->headerUnits,
            'inputItems'        => $this->inputItems,
            'packagingItems'    => $this->packagingItems,
            'inputRowUnits'     => $this->inputRowUnits,
            'packagingRowUnits' => $this->packagingRowUnits,
        ]);
    }
}