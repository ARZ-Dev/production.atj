<?php

namespace App\Livewire\RecipeTypes;

use App\Models\RecipeType;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RecipeTypeCreate extends Component
{
    use AuthorizesRequests;

    public $id = null;
    public $name;
    public $item_type_ids = [];
    public $itemTypes = [];

    public bool $editing = false;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount($id = null)
    {
        authorizeRequest('production.recipeType-create');

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
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'item_type_ids' => 'required|array|min:1',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'item_type_ids' => array_map('intval', $this->item_type_ids),
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