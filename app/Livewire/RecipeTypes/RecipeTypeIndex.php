<?php

namespace App\Livewire\RecipeTypes;

use App\Models\RecipeType;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RecipeTypeIndex extends Component
{

    use AuthorizesRequests;

    public $types = [];
    public $itemTypesMap = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount()
    {
        authorizeRequest('production.recipeType-list');

        $this->types = RecipeType::all();

        $raw = $this->api->get('/v1/item-types')['data'] ?? [];
        $this->itemTypesMap = collect($raw)->keyBy('id')->map(fn($t) => $t['name'])->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.recipeType-delete');

        $type = RecipeType::findOrFail($id);
        $type->delete();

        return redirect()->route('recipe-types')->with('success', 'Recipe Type deleted successfully.');
    }
    public function render()
    {
        return view('livewire.recipe-types.recipe-type-index');
    }
}
