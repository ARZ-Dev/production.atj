<?php

namespace App\Livewire\PreperationItems;

use App\Models\PreperationItem;
use App\Models\PreperationItemUnit;
use App\Models\ItemUnitPolicy;
use App\Utils\Constants;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PreperationItemCreate extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    // ─── PreperationItem ────────────────────────────────────
    public ?PreperationItem $item = null;
    public bool $editing = false;
    public int $status = 0;
    public string $statusAttributes = '';

    // ─── Item Fields ────────────────────────────────────────
    public ?string $itemName = null;
    public ?string $itemCode = null;
    public int $itemWithFormula = 1;
    public ?string $itemWeight = null;
    public ?string $itemVolume = null;
    public ?string $itemVAT = null;

    // ─── Units ──────────────────────────────────────────────
    public array $priceTypes = [];
    public array $units = [
        [
            'unitName'   => '',
            'symbol'     => '',
            'basic'      => 1,
            'is_box'     => '',
            'box_qty'    => '',
            'not_pos'    => '',
            'price_type' => '',
            'formula'    => '',
            'price'      => '',
            'weight'     => '',
            'volume'     => '',
            'vat'        => '',
        ],
    ];

    // ─── Image ──────────────────────────────────────────────
    public $itemImage = [];
    public ?string $itemImagePath = null;

    // ────────────────────────────────────────────────────────

    public function mount(int $id = null, int $status = 0): void
    {
        $this->priceTypes = [
            ['name' => 'ALL',     'value' => Constants::AllPrice],
            ['name' => 'B2B/B2C', 'value' => Constants::BusinessPrice],
            ['name' => 'POS',     'value' => Constants::PosPrice],
        ];

        if ($id) {
            $this->status = $status;

            if ($status == Constants::VIEW_STATUS) {
                authorizeRequest('preperation-item-view');
                $this->statusAttributes = 'disabled readonly';
            } else {
                authorizeRequest('preperation-item-edit');
            }

            $this->loadItem($id);
        } else {
            authorizeRequest('preperation-item-create');
        }
    }

    private function loadItem(int $id): void
    {
        $this->item    = PreperationItem::with('units')->findOrFail($id);
        $this->editing = true;

        $this->itemName        = $this->item->name;
        $this->itemCode        = $this->item->code;
        $this->itemWeight      = $this->item->weight;
        $this->itemVolume      = $this->item->volume;
        $this->itemVAT         = $this->item->vat;
        $this->itemWithFormula = $this->item->with_formula;
        $this->itemImagePath   = $this->item->image;

        $this->units = $this->item->units->map(fn($unit) => [
            'id'         => $unit->id,
            'unitName'   => $unit->name,
            'symbol'     => $unit->symbol,
            'basic'      => $unit->basic,
            'is_box'     => $unit->is_box,
            'box_qty'    => $unit->box_qty,
            'price_type' => $unit->price_type,
            'formula'    => $unit->formula,
            'price'      => $unit->price,
            'weight'     => $unit->weight,
            'volume'     => $unit->volume,
            'vat'        => $unit->vat,
        ])->toArray();
    }

    // ─── Units ──────────────────────────────────────────────

    public function addRow(): void
    {
        $this->units[] = [
            'unitName'   => '',
            'symbol'     => '',
            'basic'      => 0,
            'is_box'     => 0,
            'box_qty'    => '',
            'not_pos'    => '',
            'price_type' => '',
            'formula'    => '',
            'price'      => '',
            'weight'     => '',
            'volume'     => '',
            'vat'        => '',
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->units[$index]);
        $this->units = array_values($this->units);
    }

    public function toggleBasic(int $key): void
    {
        foreach ($this->units as $index => $unit) {
            $this->units[$index]['basic'] = $index === $key ? 1 : 0;
        }
    }

    // ─── Image ──────────────────────────────────────────────

    #[On('deleteImage')]
    public function deleteImage(?string $filename = null): void
    {
        $this->itemImagePath = null;

        if ($this->item) {
            $this->item->update(['image' => null]);
        }
    }

    // ─── Validation ─────────────────────────────────────────

    public function rules(): array
    {
        return [
            'itemName'           => 'required|string|max:255',
            'itemCode'           => 'required|string|max:255',
            'itemWithFormula'    => 'required',
            'itemWeight'         => 'nullable|numeric',
            'itemVolume'         => 'nullable|numeric',
            'itemVAT'            => 'nullable|numeric',
            'itemImage'          => 'nullable|max:2048',
            'units.*.unitName'   => 'required|string|max:255',
            'units.*.symbol'     => 'required|string|max:50',
            'units.*.basic'      => 'nullable',
            'units.*.is_box'     => 'nullable',
            'units.*.box_qty'    => 'nullable|numeric',
            'units.*.not_pos'    => 'nullable',
            'units.*.price_type' => 'nullable',
            'units.*.formula'    => 'nullable|numeric',
            'units.*.price'      => 'nullable|numeric',
            'units.*.weight'     => 'nullable|numeric',
            'units.*.volume'     => 'nullable|numeric',
            'units.*.vat'        => 'nullable|numeric',
        ];
    }

    // ─── Save / Update ──────────────────────────────────────

    public function store()
    {
        $data = $this->validate();

        $imagePath = null;
        if ($this->itemImage) {
            $imagePath = $this->itemImage->store('preperation-items', ['disk' => 'public']);
        }

        $item = PreperationItem::create([
            'code'         => $data['itemCode'],
            'name'         => $data['itemName'],
            'with_formula' => $data['itemWithFormula'],
            'weight'       => $data['itemWeight'],
            'volume'       => $data['itemVolume'],
            'vat'          => $data['itemVAT'],
            'image'        => $imagePath,
        ]);

        foreach ($this->units as $unitItem) {
            $this->saveUnit($item->id, $unitItem, $data);
        }

        return redirect()->route('preperation-items')->with('success', 'Preperation item created successfully.');
    }

    public function update()
    {
        $data = $this->validate();

        // Keep existing image unless a new one is uploaded
        $imagePath = $this->itemImagePath;
        if ($this->itemImage) {
            $imagePath = $this->itemImage->store('preperation-items', ['disk' => 'public']);
        }

        $this->item->update([
            'code'         => $data['itemCode'],
            'name'         => $data['itemName'],
            'with_formula' => $data['itemWithFormula'],
            'weight'       => $data['itemWeight'],
            'volume'       => $data['itemVolume'],
            'vat'          => $data['itemVAT'],
            'image'        => $imagePath,
        ]);

        $savedUnitIds = [];

        foreach ($this->units as $unitItem) {
            $unit           = $this->saveUnit($this->item->id, $unitItem, $data);
            $savedUnitIds[] = $unit->id;
        }

        $this->deleteRemovedUnits($savedUnitIds);

        return redirect()->route('preperation-items')->with('success', 'Preperation item updated successfully.');
    }

    private function saveUnit(int $itemId, array $unitItem, array $data): PreperationItemUnit
    {
        $unit = isset($unitItem['id'])
            ? PreperationItemUnit::findOrFail($unitItem['id'])
            : new PreperationItemUnit();

        $unit->preperation_item_id = $itemId;
        $unit->name       = $unitItem['unitName'];
        $unit->symbol     = $unitItem['symbol'];
        $unit->basic      = $unitItem['basic'] ?? 0;
        $unit->is_box     = $unitItem['is_box'] ? 1 : 0;
        $unit->box_qty    = $unitItem['box_qty'] ?: 0;
        $unit->price_type = $unitItem['price_type'] ?: 0;
        $unit->formula    = is_numeric($unitItem['formula'] ?? null)
            ? (float) $unitItem['formula']
            : 0;

        if ($data['itemWithFormula']) {
            $unit->weight = (float) $data['itemWeight'] * $unit->formula;
            $unit->volume = (float) $data['itemVolume'] * $unit->formula;
            $unit->vat    = (float) $data['itemVAT'];
        } else {
            $unit->weight = (float) ($unitItem['weight'] ?? 0);
            $unit->volume = (float) ($unitItem['volume'] ?? 0);
            $unit->vat    = (float) ($unitItem['vat'] ?? 0);
        }

        $unit->save();

        return $unit;
    }

    private function deleteRemovedUnits(array $savedUnitIds): void
    {
        $deletedUnits = PreperationItemUnit::where('preperation_item_id', $this->item->id)
            ->whereNotIn('id', $savedUnitIds)
            ->get();

        foreach ($deletedUnits as $unit) {
            $unit->delete();
        }
    }

    // ────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.preperation-items.preperation-item-create');
    }
}