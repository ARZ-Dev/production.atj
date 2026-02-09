<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class CompanyIndex extends Component
{
    use AuthorizesRequests;

    public $companies = [];

    public function mount()
    {
        $this->authorize('company-list');

        if(auth()->user()->hasRole('Super Admin'))
        {
            $this->companies = Company::all();
        } else {
            $this->companies = Company::where('id', auth()->user()->company_id)->get();
        }
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('company-delete');

        $company = Company::findOrFail($id);
        $company->delete();

        return to_route('companies')->with('success', 'Company has been deleted successfully!');
    }

    
    public function render()
    {
        return view('livewire.companies.company-index');
    }
}
