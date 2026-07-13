<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use App\Models\RecipeInput;
use App\Models\RecipeSideProduct;
use App\Models\RecipeType;
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
    public ?string $name           = null;
    public ?int    $recipe_type_id = null;
    public ?int    $item_id        = null;
    public ?int    $item_unit_id   = null;
    public int     $batch              = 1;
    public ?string $quantity_per_batch = null;
    public ?string $batch_weight       = null;
    public ?string $batch_volume       = null;
    public bool    $status             = true;
    public ?string $notes              = null;

    // ─── Recipe types from DB ─────────────────────────────────────────────────
    public array $recipeTypes = [];

    // ─── Item types from API (for output item type picker) ────────────────────
    public array $itemTypes          = [];
    public ?int  $output_item_type_id = null;

    // ─── Header item list & units ─────────────────────────────────────────────
    public array $headerItems = [];
    public array $headerUnits = [];

    // ─── Dynamic ingredient sections (one per item_type_id from recipe type) ──
    // sections[i] = ['item_type_id', 'title', 'rows', 'rowUnits']
    public array $sections     = [];
    // Available items per section (parallel to $sections, kept separate to keep
    // $sections lean — only stores user-editable row data)
    public array $sectionItems = [];

    // ─── Dynamic side product sections (one per side_item_type_id from recipe type) ──
    public array $sideSections     = [];
    public array $sideSectionItems = [];

    // ─── API ──────────────────────────────────────────────────────────────────
    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, ?int $id = null): void
    {
        authorizeRequest('production.recipe-create');

        $this->recipeTypes = RecipeType::orderBy('name')->get()->toArray();

        if ($id) {
            $this->id      = $id;
            $this->editing = true;
            $this->loadRecipe($id);
        }
    }

    // ─── Load existing recipe for edit ───────────────────────────────────────
    protected function loadRecipe(int $id): void
    {
        $recipe = Recipe::with(['inputs', 'sideProducts'])->findOrFail($id);

        $this->name              = $recipe->name;
        $this->recipe_type_id    = $recipe->recipe_type_id;
        $this->item_id           = $recipe->item_id;
        $this->item_unit_id      = $recipe->item_unit_id;
        $this->batch             = $recipe->batch ?? 1;
        $this->quantity_per_batch = $recipe->quantity_per_batch;
        $this->batch_weight      = $recipe->batch_weight;
        $this->batch_volume      = $recipe->batch_volume;
        $this->status            = (bool) $recipe->status;
        $this->notes             = $recipe->notes;

        if ($this->recipe_type_id) {
            $this->buildSections($this->recipe_type_id, $recipe->inputs, $recipe->sideProducts);
            // $this->itemTypes is now populated
        }

        // Restore output item type from DB column
        $this->output_item_type_id = $recipe->item_type_id;

        if ($this->output_item_type_id) {
            $typeInfo = collect($this->itemTypes)->firstWhere('id', $this->output_item_type_id);
            $typeName = $typeInfo['name'] ?? null;
            if ($typeName) {
                $this->headerItems = $this->api->get('/v1/items', ['item_type' => $typeName, 'is_active' => true])['data'] ?? [];
            }
        }

        if ($this->item_id) {
            $this->headerUnits = $this->fetchUnitsForItem($this->item_id);
        }
    }

    // ─── Build dynamic sections from recipe type's item_type_ids ─────────────
    protected function buildSections(int $recipeTypeId, $existingInputs = null, $existingSideProducts = null): void
    {
        $recipeType        = RecipeType::find($recipeTypeId);
        $itemTypeIds       = $recipeType->item_type_ids ?? [];
        $sideItemTypeIds   = $recipeType->side_item_type_ids ?? [];
        $outputItemTypeIds = $recipeType->output_item_type_ids ?? [];

        $allItemTypes = $this->api->get('/v1/item-types')['data'] ?? [];
        $itemTypeMap  = collect($allItemTypes)->keyBy('id');

        $this->itemTypes = collect($allItemTypes)
            ->filter(fn($type) => in_array($type['id'], $outputItemTypeIds))
            ->values()
            ->toArray();

        [$this->sections, $this->sectionItems] = $this->buildSectionGroup(
            $itemTypeIds,
            $itemTypeMap,
            $existingInputs,
            'input_type'
        );

        [$this->sideSections, $this->sideSectionItems] = $this->buildSectionGroup(
            $sideItemTypeIds,
            $itemTypeMap,
            $existingSideProducts,
            'item_type_id'
        );
    }

    // ─── Shared builder for both ingredient and side product sections ────────
    protected function buildSectionGroup(array $itemTypeIds, $itemTypeMap, $existingRecords, string $groupField): array
    {
        $sections     = [];
        $sectionItems = [];

        $recordsByType = $existingRecords
            ? collect($existingRecords)->groupBy($groupField)
            : collect();

        foreach ($itemTypeIds as $typeId) {
            $typeInfo = $itemTypeMap->get($typeId);
            $title    = $typeInfo['name'] ?? "Item Type {$typeId}";
            $items    = $this->api->get('/v1/items', ['item_type' => $title, 'is_active' => true])['data'] ?? [];

            $existingRows = $recordsByType->get($typeId, collect());
            $rows         = [];
            $rowUnits     = [];

            foreach ($existingRows as $record) {
                $itemId     = is_object($record) ? $record->item_id : $record['item_id'];
                $rows[]     = [
                    'id'           => is_object($record) ? $record->id : ($record['id'] ?? null),
                    'item_id'      => $itemId,
                    'item_unit_id' => is_object($record) ? $record->item_unit_id : $record['item_unit_id'],
                    'quantity'     => is_object($record) ? $record->quantity : $record['quantity'],
                    'notes'        => is_object($record) ? $record->notes : ($record['notes'] ?? null),
                ];
                $rowUnits[] = $itemId ? $this->fetchUnitsForItem((int) $itemId) : [];
            }

            if (empty($rows)) {
                $rows[]     = ['id' => null, 'item_id' => null, 'item_unit_id' => null, 'quantity' => '', 'notes' => null];
                $rowUnits[] = [];
            }

            $sections[]     = [
                'item_type_id' => $typeId,
                'title'        => $title,
                'rows'         => $rows,
                'rowUnits'     => $rowUnits,
            ];
            $sectionItems[] = $items;
        }

        return [$sections, $sectionItems];
    }

    protected function fetchUnitsForItem(int $itemId): array
    {
        return $this->api->get("/v1/items/{$itemId}")['data']['units'] ?? [];
    }

    // ─── Watchers ─────────────────────────────────────────────────────────────
    public function updatedRecipeTypeId($value): void
    {
        $value = $value ? (int) $value : null;
        $this->recipe_type_id = $value;

        $this->output_item_type_id = null;
        $this->item_id             = null;
        $this->item_unit_id        = null;
        $this->headerItems         = [];
        $this->headerUnits         = [];
        $this->sections            = [];
        $this->sectionItems        = [];
        $this->sideSections        = [];
        $this->sideSectionItems    = [];

        if ($value) {
            $this->buildSections($value);

            $this->dispatch('setSectionsItems', $this->sectionItems);
        }

        $this->dispatch('output-item-types-changed', itemTypes: $this->itemTypes);
    }

    #[On('getHeaderItems')]
    public function getHeaderItems($value): void
    {
        $value = $value ? (int) $value : null;
        $this->output_item_type_id = $value;
        $this->item_id             = null;
        $this->item_unit_id        = null;
        $this->headerItems         = [];
        $this->headerUnits         = [];

        if ($value) {
            $typeInfo = collect($this->itemTypes)->firstWhere('id', $value);
            $typeName = $typeInfo['name'] ?? null;

            if ($typeName) {
                $this->headerItems = $this->api->get('/v1/items', ['item_type' => $typeName, 'is_active' => true])['data'] ?? [];
            }
        }

        $this->dispatch('output-type-changed', headerItems: $this->headerItems);
    }

    public function updatedItemId(?int $value): void
    {
        $this->item_unit_id = null;
        $this->headerUnits  = $value ? $this->fetchUnitsForItem($value) : [];
    }

    #[On('getHeaderUnits')]
    public function getHeaderUnits($itemId): void
    {
        $this->headerUnits = $itemId ? $this->fetchUnitsForItem($itemId) : [];
        $this->dispatch('setHeaderUnits', $this->headerUnits);
    }

    // ─── Row management ───────────────────────────────────────────────────────
    public function addRowToSection(int $sectionIndex): void
    {
        $this->sections[$sectionIndex]['rows'][]     = ['id' => null, 'item_id' => null, 'item_unit_id' => null, 'quantity' => '', 'notes' => null];
        $this->sections[$sectionIndex]['rowUnits'][] = [];
    }

    public function removeRowFromSection(int $sectionIndex, int $rowIndex): void
    {
        if (count($this->sections[$sectionIndex]['rows']) <= 1) {
            $this->dispatch('swal:error', ['title' => 'Warning', 'text' => 'At least one item is required!']);
            return;
        }

        unset(
            $this->sections[$sectionIndex]['rows'][$rowIndex],
            $this->sections[$sectionIndex]['rowUnits'][$rowIndex]
        );

        $this->sections[$sectionIndex]['rows']     = array_values($this->sections[$sectionIndex]['rows']);
        $this->sections[$sectionIndex]['rowUnits'] = array_values($this->sections[$sectionIndex]['rowUnits']);

        $this->dispatch('section-rows-removed',
            sectionIndex: $sectionIndex,
            rows: array_map(fn($r) => $r['item_id'], $this->sections[$sectionIndex]['rows'])
        );
    }

    // ─── JS-callable: section item changed → load units ──────────────────────
    public function onSectionItemChanged(int $sectionIndex, int $rowIndex, ?int $itemId): void
    {
        $this->sections[$sectionIndex]['rows'][$rowIndex]['item_id']      = $itemId;
        $this->sections[$sectionIndex]['rows'][$rowIndex]['item_unit_id'] = null;

        if ($itemId) {
            $units = $this->fetchUnitsForItem($itemId);
            $this->sections[$sectionIndex]['rowUnits'][$rowIndex] = $units;
            $basic = collect($units)->firstWhere('basic', true);
            $this->sections[$sectionIndex]['rows'][$rowIndex]['item_unit_id'] = $basic['id'] ?? ($units[0]['id'] ?? null);
        } else {
            $this->sections[$sectionIndex]['rowUnits'][$rowIndex] = [];
        }
    }

    // ─── Row management (side product sections) ──────────────────────────────
    public function addRowToSideSection(int $sectionIndex): void
    {
        $this->sideSections[$sectionIndex]['rows'][]     = ['id' => null, 'item_id' => null, 'item_unit_id' => null, 'quantity' => '', 'notes' => null];
        $this->sideSections[$sectionIndex]['rowUnits'][] = [];
    }

    public function removeRowFromSideSection(int $sectionIndex, int $rowIndex): void
    {
        if (count($this->sideSections[$sectionIndex]['rows']) <= 1) {
            $this->dispatch('swal:error', ['title' => 'Warning', 'text' => 'At least one item is required!']);
            return;
        }

        unset(
            $this->sideSections[$sectionIndex]['rows'][$rowIndex],
            $this->sideSections[$sectionIndex]['rowUnits'][$rowIndex]
        );

        $this->sideSections[$sectionIndex]['rows']     = array_values($this->sideSections[$sectionIndex]['rows']);
        $this->sideSections[$sectionIndex]['rowUnits'] = array_values($this->sideSections[$sectionIndex]['rowUnits']);

        $this->dispatch('side-section-rows-removed',
            sectionIndex: $sectionIndex,
            rows: array_map(fn($r) => $r['item_id'], $this->sideSections[$sectionIndex]['rows'])
        );
    }

    // ─── JS-callable: side section item changed → load units ─────────────────
    public function onSideSectionItemChanged(int $sectionIndex, int $rowIndex, ?int $itemId): void
    {
        $this->sideSections[$sectionIndex]['rows'][$rowIndex]['item_id']      = $itemId;
        $this->sideSections[$sectionIndex]['rows'][$rowIndex]['item_unit_id'] = null;

        if ($itemId) {
            $units = $this->fetchUnitsForItem($itemId);
            $this->sideSections[$sectionIndex]['rowUnits'][$rowIndex] = $units;
            $basic = collect($units)->firstWhere('basic', true);
            $this->sideSections[$sectionIndex]['rows'][$rowIndex]['item_unit_id'] = $basic['id'] ?? ($units[0]['id'] ?? null);
        } else {
            $this->sideSections[$sectionIndex]['rowUnits'][$rowIndex] = [];
        }
    }

    // ─── Submit ───────────────────────────────────────────────────────────────
    public function submit(): void
    {
        $rules = [
            'name'              => 'required|string|max:255',
            'recipe_type_id'    => 'required|integer|exists:recipe_types,id',
            'output_item_type_id' => 'required|integer',
            'item_id'           => 'required|integer',
            'item_unit_id'      => 'required|integer',
            'quantity_per_batch' => 'required|numeric|min:0.0001',
            'batch'             => 'required|integer|min:1',
            'batch_weight'      => 'nullable|numeric|min:0',
            'batch_volume'      => 'nullable|numeric|min:0',
            'sections'          => 'required|array|min:1',
        ];

        foreach ($this->sections as $sIdx => $section) {
            $rules["sections.{$sIdx}.rows"]                 = 'required|array|min:1';
            $rules["sections.{$sIdx}.rows.*.item_id"]      = 'required|integer';
            $rules["sections.{$sIdx}.rows.*.item_unit_id"] = 'required|integer';
            $rules["sections.{$sIdx}.rows.*.quantity"]     = 'required|numeric|min:0.0001';
        }

        foreach ($this->sideSections as $sIdx => $section) {
            $rules["sideSections.{$sIdx}.rows"]                 = 'required|array|min:1';
            $rules["sideSections.{$sIdx}.rows.*.item_id"]      = 'required|integer';
            $rules["sideSections.{$sIdx}.rows.*.item_unit_id"] = 'required|integer';
            $rules["sideSections.{$sIdx}.rows.*.quantity"]     = 'required|numeric|min:0.0001';
        }

        $this->validate($rules, [
            'name.required'                => 'Recipe name is required.',
            'recipe_type_id.required'      => 'Recipe type is required.',
            'output_item_type_id.required' => 'Output item type is required.',
            'item_id.required'             => 'Output item is required.',
            'item_unit_id.required'        => 'Output unit is required.',
            'quantity_per_batch.required'  => 'Quantity per batch is required.',
            'quantity_per_batch.min'       => 'Quantity must be greater than 0.',
            'batch.required'               => 'Batch size is required.',
            'batch.min'                    => 'Batch must be at least 1.',
            'sections.required'            => 'Please select a recipe type first.',
            'sections.min'                 => 'Please select a recipe type first.',
        ]);

        DB::beginTransaction();
        try {
            $recipeData = [
                'name'              => $this->name,
                'recipe_type_id'    => $this->recipe_type_id,
                'item_type_id'      => $this->output_item_type_id,
                'item_id'           => $this->item_id,
                'item_unit_id'      => $this->item_unit_id,
                'quantity_per_batch' => $this->quantity_per_batch,
                'batch'             => $this->batch,
                'batch_weight'      => $this->batch_weight,
                'batch_volume'      => $this->batch_volume,
                'status'            => $this->status,
                'notes'             => $this->notes,
            ];

            $recipe = $this->editing
                ? tap(Recipe::findOrFail($this->id))->update($recipeData)
                : Recipe::create($recipeData);

            $syncedIds = [];

            foreach ($this->sections as $sIdx => $section) {
                foreach ($section['rows'] as $rIdx => $row) {
                    $unit = collect($this->sections[$sIdx]['rowUnits'][$rIdx] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                    abort_if(!$unit, 422, "Invalid unit for {$section['title']} row " . ($rIdx + 1));

                    $data = [
                        'input_type'   => $section['item_type_id'],
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

            $syncedSideIds = [];

            foreach ($this->sideSections as $sIdx => $section) {
                foreach ($section['rows'] as $rIdx => $row) {
                    $unit = collect($this->sideSections[$sIdx]['rowUnits'][$rIdx] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                    abort_if(!$unit, 422, "Invalid unit for {$section['title']} row " . ($rIdx + 1));

                    $data = [
                        'item_type_id' => $section['item_type_id'],
                        'item_id'      => (int) $row['item_id'],
                        'item_unit_id' => (int) $row['item_unit_id'],
                        'quantity'     => (float) $row['quantity'],
                        'notes'        => $row['notes'] ?? null,
                    ];

                    if (!empty($row['id'])) {
                        $sideProduct = RecipeSideProduct::findOrFail($row['id']);
                        $sideProduct->update($data);
                    } else {
                        $sideProduct = $recipe->sideProducts()->create($data);
                    }
                    $syncedSideIds[] = $sideProduct->id;
                }
            }

            if ($this->editing) {
                $recipe->sideProducts()->whereNotIn('id', $syncedSideIds)->delete();
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
            'recipeTypes'         => $this->recipeTypes,
            'itemTypes'           => $this->itemTypes,
            'output_item_type_id' => $this->output_item_type_id,
            'headerItems'         => $this->headerItems,
            'headerUnits'         => $this->headerUnits,
            'sections'            => $this->sections,
            'sectionItems'        => $this->sectionItems,
            'sideSections'        => $this->sideSections,
            'sideSectionItems'    => $this->sideSectionItems,
        ]);
    }
}
