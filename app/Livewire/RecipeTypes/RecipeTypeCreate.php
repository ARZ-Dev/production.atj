<?php

namespace App\Livewire\RecipeTypes;

use App\Models\RecipeType;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecipeTypeCreate extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public $id = null;
    // Opened from recipe-types.view: read-only, and save refuses.
    #[Locked]
    public bool $viewing = false;
    public $name;
    public $item_type_ids = [];
    public $side_item_type_ids = [];
    public $output_item_type_ids = [];
    public $itemTypes = [];

    #[Locked]
    public bool $editing = false;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    /** $mode is 'view' on the recipe-types.view route (a route default). */
    public function mount($id = null, ?string $mode = null)
    {
        $this->viewing = $mode === 'view';

        authorizeRequest(match (true) {
            $this->viewing => 'production.recipeType-view',
            (bool) $id     => 'production.recipeType-edit',
            default        => 'production.recipeType-create',
        });

        $this->itemTypes = $this->api->get('/v1/item-types')['data'] ?? [];

        if ($id) {
            $this->id = $id;
            $this->editing = true;

            $type = RecipeType::find($id);

            if (!$type) {
                abort(404);
            }

            $this->name = $type['name'] ?? '';
            $this->item_type_ids = $type['item_type_ids'] ?? [];
            $this->side_item_type_ids = $type['side_item_type_ids'] ?? [];
            $this->output_item_type_ids = $type['output_item_type_ids'] ?? [];
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'item_type_ids' => 'required|array|min:1',
            'side_item_type_ids' => 'nullable|array',
            'output_item_type_ids' => 'required|array|min:1',
        ];
    }

    public function save()
    {
        abort_if($this->viewing, 403, 'This recipe type is open for viewing only.');

        $this->validate();

        $data = [
            'name' => $this->name,
            'item_type_ids' => array_map('intval', $this->item_type_ids),
            'side_item_type_ids' => array_map('intval', $this->side_item_type_ids ?? []),
            'output_item_type_ids' => array_map('intval', $this->output_item_type_ids ?? []),
        ];

        if ($this->editing) {
            $type = RecipeType::findOrFail($this->id);
            $type->update($data);

            return redirect()->route('recipe-types')->with('success', 'Recipe Type updated successfully.');
        }

        RecipeType::create($data);

        return redirect()->route('recipe-types')->with('success', 'Recipe Type created successfully.');
    }

    public function render()
    {
        return view('livewire.recipe-types.recipe-type-create');
    }
}