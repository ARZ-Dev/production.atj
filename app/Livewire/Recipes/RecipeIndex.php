<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RecipeIndex extends Component
{
    use AuthorizesRequests;

    public $recipes;


    public function mount()
    {
        authorizeRequest('production.recipe-list');

        $this->recipes = Recipe::orderBy('created_at', 'desc')->get();
    }


    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.recipe-delete');

        $recipe = Recipe::findOrFail($id);
        $recipe->delete();

        return redirect()->route('recipes')->with('success', 'Recipe deleted successfully.');
    }

    public function activate($id)
    {
        $recipe = Recipe::find($id);
        if ($recipe) {
            $recipe->status = true;
            $recipe->save();

            return to_route('recipes')->with('success', 'Recipe activated successfully');
        }
        $this->mount();
    }

    public function deactivate($id)
    {
        // dd($id);
        $recipe = Recipe::find($id);
        if ($recipe) {
            $recipe->status = false;
            $recipe->save();
            return to_route('recipes')->with('success', 'Recipe deactivated successfully');
        }
        $this->mount();
    }

    public function render()
    {
        return view('livewire.recipes.recipe-index');
    }
}
