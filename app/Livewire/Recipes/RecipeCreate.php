<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use App\Models\RecipeInput;
use App\Models\RecipeSideProduct;
use App\Models\RecipeStage;
use App\Models\RecipeType;
use App\Models\StationType;
use App\Services\ApiService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class RecipeCreate extends Component
{
    use AuthorizesRequests;

    // ─── State ────────────────────────────────────────────────────────────────
    #[Locked]
    public bool $editing = false;
    #[Locked]
    public ?int $id      = null;
    // Opened from recipes.view: everything renders read-only and submit refuses.
    #[Locked]
    public bool $viewing = false;

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

    // ─── Line stages (a recipe run on a line, one stage per station type) ────
    // Keyed by a stable key ("t<id>" saved, "n<hex>" new) — ingredient rows
    // point at a stage by this key, and removing a stage never shifts the
    // wire:model paths of another.
    public array   $stages            = [];
    public ?string $balance_tolerance = null;
    public ?string $reject_allowance  = null;
    // Active items (with their basic unit) a stage's rejects can be reworked into.
    public array   $reworkItems       = [];

    // ─── API ──────────────────────────────────────────────────────────────────
    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    /** $mode is 'view' on the recipes.view route (a route default). */
    public function mount(ApiService $api, ?int $id = null, ?string $mode = null): void
    {
        $this->viewing = $mode === 'view';

        authorizeRequest(match (true) {
            $this->viewing => 'production.recipe-view',
            (bool) $id     => 'production.recipe-edit',
            default        => 'production.recipe-create',
        });

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
        $recipe = Recipe::with(['inputs', 'sideProducts', 'stages'])->findOrFail($id);

        $this->name              = $recipe->name;
        $this->recipe_type_id    = $recipe->recipe_type_id;
        $this->item_id           = $recipe->item_id;
        $this->item_unit_id      = $recipe->item_unit_id;
        $this->batch             = $recipe->batch ?? 1;
        $this->quantity_per_batch = format_quantity($recipe->quantity_per_batch);
        $this->batch_weight      = $recipe->batch_weight !== null ? format_quantity($recipe->batch_weight) : null;
        $this->batch_volume      = $recipe->batch_volume !== null ? format_quantity($recipe->batch_volume) : null;
        $this->status            = (bool) $recipe->status;
        $this->notes             = $recipe->notes;
        $this->balance_tolerance = $recipe->balance_tolerance !== null ? format_quantity($recipe->balance_tolerance) : null;
        $this->reject_allowance  = $recipe->reject_allowance !== null ? format_quantity($recipe->reject_allowance) : null;

        // Stages first: ingredient rows below point at them by key.
        foreach ($recipe->stages as $stage) {
            $this->stages['t' . $stage->id] = [
                'id'                  => $stage->id,
                'station_type_id'     => $stage->station_type_id ? (string) $stage->station_type_id : '',
                'output_quantity'     => format_quantity($stage->output_quantity),
                'output_unit'         => $stage->output_unit,
                'output_item_unit_id' => $stage->output_item_unit_id,
                'reject_action'       => $stage->reject_action ?: 'waste',
                'rework_item_id'      => $stage->rework_item_id,
                'rework_factor'       => format_quantity($stage->rework_factor),
            ];
        }

        if ($this->stages) {
            $this->loadReworkItems();
        }

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
            'item_type_id'
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
                $stageId    = is_object($record) ? ($record->recipe_stage_id ?? null) : ($record['recipe_stage_id'] ?? null);
                $rows[]     = [
                    'id'           => is_object($record) ? $record->id : ($record['id'] ?? null),
                    'item_id'      => $itemId,
                    'item_unit_id' => is_object($record) ? $record->item_unit_id : $record['item_unit_id'],
                    'quantity'     => format_quantity(is_object($record) ? $record->quantity : $record['quantity']),
                    'notes'        => is_object($record) ? $record->notes : ($record['notes'] ?? null),
                    'stage_key'    => $stageId && isset($this->stages['t' . $stageId]) ? 't' . $stageId : null,
                ];
                $rowUnits[] = $itemId ? $this->fetchUnitsForItem((int) $itemId) : [];
            }

            if (empty($rows)) {
                $rows[]     = $this->blankRow();
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
        $this->sections[$sectionIndex]['rows'][]     = $this->blankRow();
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
        $this->sideSections[$sectionIndex]['rows'][]     = $this->blankRow();
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

    protected function blankRow(): array
    {
        return ['id' => null, 'item_id' => null, 'item_unit_id' => null, 'quantity' => '', 'notes' => null, 'stage_key' => null];
    }

    // ─── Line stages ─────────────────────────────────────────────────────────

    /**
     * A recipe with stages is run on a line as one event across its stations:
     * each stage names a station type and runs on the line's stations of it.
     */
    public function addStage(): void
    {
        if ($this->viewing) {
            return;
        }

        $this->stages['n' . bin2hex(random_bytes(4))] = [
            'id'                  => null,
            'station_type_id'     => '',
            'output_quantity'     => '',
            'output_unit'         => null,
            'output_item_unit_id' => null,
            'reject_action'       => 'waste',
            'rework_item_id'      => null,
            'rework_factor'       => '1',
        ];

        $this->loadReworkItems();
    }

    public function removeStage(string $key): void
    {
        if ($this->viewing || !isset($this->stages[$key])) {
            return;
        }

        unset($this->stages[$key]);

        // Ingredients that were on it go back to having no stage.
        foreach ($this->sections as $sIdx => $section) {
            foreach ($section['rows'] as $rIdx => $row) {
                if (($row['stage_key'] ?? null) === $key) {
                    $this->sections[$sIdx]['rows'][$rIdx]['stage_key'] = null;
                }
            }
        }
    }

    /** One place up (-1) or down (+1) in the line's order. */
    public function moveStage(string $key, int $direction): void
    {
        $keys  = array_keys($this->stages);
        $index = array_search($key, $keys, true);
        $swap  = $index === false ? null : $index + $direction;

        if ($this->viewing || $swap === null || $swap < 0 || $swap >= count($keys)) {
            return;
        }

        [$keys[$index], $keys[$swap]] = [$keys[$swap], $keys[$index]];

        $this->stages = array_combine($keys, array_map(fn($k) => $this->stages[$k], $keys));
    }

    /** Every active item, with its basic unit, for "rework into". One call. */
    protected function loadReworkItems(): void
    {
        if ($this->reworkItems) {
            return;
        }

        $this->reworkItems = collect($this->api->get('/v1/items', ['is_active' => 1])['data'] ?? [])
            ->map(function ($item) {
                $basic = collect($item['units'] ?? [])->firstWhere('basic', true) ?? ($item['units'][0] ?? null);

                return [
                    'id'           => (int) $item['id'],
                    'name'         => $item['name'] . (!empty($item['code']) ? " ({$item['code']})" : ''),
                    'item_type_id' => isset($item['item_type_id']) ? (int) $item['item_type_id'] : null,
                    'unit_id'      => $basic['id'] ?? null,
                    'unit_name'    => $basic['name'] ?? null,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Strip Cleave.js thousands separators (e.g. "1,000" → "1000") from every
     * quantity field before validation/save. Null/empty values are preserved
     * so an optional Batch Weight/Volume stays null rather than becoming "".
     */
    protected function sanitizeQuantities(): void
    {
        $this->quantity_per_batch = $this->stripThousands($this->quantity_per_batch);
        $this->batch_weight       = $this->stripThousands($this->batch_weight);
        $this->batch_volume       = $this->stripThousands($this->batch_volume);
        $this->balance_tolerance  = $this->stripThousands($this->balance_tolerance);
        $this->reject_allowance   = $this->stripThousands($this->reject_allowance);

        foreach ($this->stages as $key => $stage) {
            $this->stages[$key]['output_quantity'] = $this->stripThousands($stage['output_quantity'] ?? '');
            $this->stages[$key]['rework_factor']   = $this->stripThousands($stage['rework_factor'] ?? '');
        }

        foreach ($this->sections as $sIdx => $section) {
            foreach ($section['rows'] as $rIdx => $row) {
                $this->sections[$sIdx]['rows'][$rIdx]['quantity'] = $this->stripThousands($row['quantity'] ?? '');
            }
        }

        foreach ($this->sideSections as $sIdx => $section) {
            foreach ($section['rows'] as $rIdx => $row) {
                $this->sideSections[$sIdx]['rows'][$rIdx]['quantity'] = $this->stripThousands($row['quantity'] ?? '');
            }
        }
    }

    protected function stripThousands($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return str_replace(',', '', (string) $value);
    }

    /**
     * An ingredient row nothing was entered in — no item and no quantity.
     * Unlike side products, a quantity typed without an item still counts,
     * so it is reported rather than silently dropped.
     */
    protected function isBlankIngredientRow(array $row): bool
    {
        return blank($row['item_id'] ?? null) && blank($row['quantity'] ?? null);
    }

    // ─── Submit ───────────────────────────────────────────────────────────────
    public function submit(): void
    {
        abort_if($this->viewing, 403, 'This recipe is open for viewing only.');

        $this->sanitizeQuantities();

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

        // A recipe needn't use every item type its recipe type lists, so a
        // section may stay blank: only rows with an item or a quantity entered
        // are validated and saved. The recipe still needs one ingredient overall.
        $ingredientRows = 0;

        foreach ($this->sections as $sIdx => $section) {
            foreach ($section['rows'] as $rIdx => $row) {
                if ($this->isBlankIngredientRow($row)) {
                    continue;
                }

                $ingredientRows++;
                $rules["sections.{$sIdx}.rows.{$rIdx}.item_id"]      = 'required|integer';
                $rules["sections.{$sIdx}.rows.{$rIdx}.item_unit_id"] = 'required|integer';
                $rules["sections.{$sIdx}.rows.{$rIdx}.quantity"]     = 'required|numeric|min:0.0001';

                // On a recipe with stages every ingredient is used at one of them.
                if ($this->stages) {
                    $rules["sections.{$sIdx}.rows.{$rIdx}.stage_key"] = ['required', Rule::in(array_keys($this->stages))];
                }
            }
        }

        // Side products are optional. Only validate the rows the user actually
        // filled in (i.e. picked an item for); blank rows are simply skipped.
        foreach ($this->sideSections as $sIdx => $section) {
            foreach ($section['rows'] as $rIdx => $row) {
                if (empty($row['item_id'])) {
                    continue;
                }

                $rules["sideSections.{$sIdx}.rows.{$rIdx}.item_id"]      = 'required|integer';
                $rules["sideSections.{$sIdx}.rows.{$rIdx}.item_unit_id"] = 'required|integer';
                $rules["sideSections.{$sIdx}.rows.{$rIdx}.quantity"]     = 'required|numeric|min:0.0001';
            }
        }

        foreach (array_keys($this->stages) as $key) {
            $rules["stages.{$key}.station_type_id"] = ['required', Rule::exists('station_types', 'id')->whereNull('deleted_at')];
            $rules["stages.{$key}.output_quantity"] = 'required|numeric|min:0.0001';
            $rules["stages.{$key}.output_unit"]     = 'nullable|string|max:50';
            $rules["stages.{$key}.reject_action"]   = 'required|in:waste,rework';
            $rules["stages.{$key}.rework_item_id"]  = ($this->stages[$key]['reject_action'] ?? 'waste') === 'rework' ? 'required|integer' : 'nullable|integer';
            $rules["stages.{$key}.rework_factor"]   = 'nullable|numeric|min:0';
        }

        if ($this->stages) {
            $rules['balance_tolerance'] = 'nullable|numeric|min:0|max:100';
            $rules['reject_allowance']  = 'nullable|numeric|min:0|max:100';
        }

        $this->validate($rules, [
            'stages.*.station_type_id.required'    => 'Pick the station type — the stage runs on the line\'s stations of it.',
            'stages.*.station_type_id.exists'      => 'Pick the station type — the stage runs on the line\'s stations of it.',
            'stages.*.output_quantity.required'    => 'How much does this stage make per batch?',
            'stages.*.output_quantity.min'         => 'Must be more than 0.',
            'stages.*.rework_item_id.required'     => 'Pick what rejects are reworked into.',
            'sections.*.rows.*.stage_key.required' => 'Pick the stage that uses it.',
            'sections.*.rows.*.stage_key.in'       => 'Pick the stage that uses it.',
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
            'sections.*.rows.*.item_id.required'      => 'Ingredient item is required.',
            'sections.*.rows.*.item_unit_id.required' => 'Ingredient unit is required.',
            'sections.*.rows.*.quantity.required'     => 'Ingredient quantity is required.',
            'sections.*.rows.*.quantity.min'          => 'Quantity must be greater than 0.',
            'sideSections.*.rows.*.item_id.required'      => 'Side product item is required.',
            'sideSections.*.rows.*.item_unit_id.required' => 'Side product unit is required.',
            'sideSections.*.rows.*.quantity.required'     => 'Side product quantity is required.',
            'sideSections.*.rows.*.quantity.min'          => 'Quantity must be greater than 0.',
        ]);

        if ($ingredientRows === 0) {
            $this->addError('sections', 'Add at least one ingredient.');

            return;
        }

        $stageTypes = collect($this->stages)->map(fn($stage) => (int) $stage['station_type_id']);

        if ($stageTypes->duplicates()->isNotEmpty()) {
            $this->addError('stages', 'Two stages are ' . StationType::find($stageTypes->duplicates()->first())?->name . '. Each station type can be one stage of a recipe.');

            return;
        }

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
                'balance_tolerance' => $this->stages && $this->balance_tolerance !== null && $this->balance_tolerance !== '' ? $this->balance_tolerance : null,
                'reject_allowance'  => $this->stages && $this->reject_allowance !== null && $this->reject_allowance !== '' ? $this->reject_allowance : null,
            ];

            $recipe = $this->editing
                ? tap(Recipe::findOrFail($this->id))->update($recipeData)
                : Recipe::create($recipeData);

            $stageIds = $this->saveStages($recipe);

            $syncedIds = [];

            foreach ($this->sections as $sIdx => $section) {
                foreach ($section['rows'] as $rIdx => $row) {
                    if ($this->isBlankIngredientRow($row)) {
                        continue;
                    }

                    $unit = collect($this->sections[$sIdx]['rowUnits'][$rIdx] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                    abort_if(!$unit, 422, "Invalid unit for {$section['title']} row " . ($rIdx + 1));

                    $data = [
                        'item_type_id' => $section['item_type_id'],
                        'item_id'      => (int) $row['item_id'],
                        'item_unit_id' => (int) $row['item_unit_id'],
                        'quantity'     => (float) $row['quantity'],
                        'notes'        => $row['notes'] ?? null,
                        'recipe_stage_id' => $stageIds[$row['stage_key'] ?? ''] ?? null,
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
                    // Side products are optional — skip blank rows entirely.
                    if (empty($row['item_id'])) {
                        continue;
                    }

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

    /**
     * Create, update and remove the recipe's stages to match the form, in
     * the form's order.
     *
     * @return array<string, int> stage key => id
     */
    protected function saveStages(Recipe $recipe): array
    {
        $ids      = [];
        $sequence = 0;
        $rework   = collect($this->reworkItems)->keyBy('id');
        $types    = StationType::withTrashed()->pluck('name', 'id');

        foreach ($this->stages as $key => $stage) {
            $reworkItem = $stage['rework_item_id'] ? $rework->get((int) $stage['rework_item_id']) : null;

            $data = [
                'sequence'            => ++$sequence,
                'station_type_id'     => (int) $stage['station_type_id'],
                'name'                => $types[(int) $stage['station_type_id']] ?? '',
                'output_quantity'     => (float) $stage['output_quantity'],
                'output_unit'         => $stage['output_unit'] ? trim($stage['output_unit']) : null,
                'output_item_unit_id' => $stage['output_item_unit_id'] ? (int) $stage['output_item_unit_id'] : null,
                'reject_action'       => $stage['reject_action'] === 'rework' && $reworkItem ? 'rework' : 'waste',
                'rework_item_type_id' => $reworkItem['item_type_id'] ?? null,
                'rework_item_id'      => $reworkItem['id'] ?? null,
                'rework_item_unit_id' => $reworkItem['unit_id'] ?? null,
                'rework_factor'       => $stage['rework_factor'] !== null && $stage['rework_factor'] !== '' ? (float) $stage['rework_factor'] : 1,
            ];

            $model = !empty($stage['id']) ? RecipeStage::where('recipe_id', $recipe->id)->find($stage['id']) : null;
            $model ? $model->update($data) : $model = $recipe->stages()->create($data);

            $ids[$key] = $model->id;
        }

        $recipe->stages()->whereNotIn('id', array_values($ids) ?: [0])->delete();

        return $ids;
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
            'stationTypes'        => StationType::orderBy('sequence')->orderBy('name')->get(['id', 'name', 'rate_unit']),
        ]);
    }
}
