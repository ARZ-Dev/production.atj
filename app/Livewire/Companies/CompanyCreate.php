<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CompanyCreate extends Component
{
    use AuthorizesRequests;

    public bool $editing = false;
    public $id;
    public $company;
    public $name;
    public $phone;
    public $description;
    public $address;

    public function mount($id = null)
    {
        if ($id) {
            $this->authorize('company-edit');

            $this->editing = true;
            $this->company = \App\Models\Company::findOrFail($id);
            $this->name = $this->company->name;
            $this->phone = $this->company->phone;
            $this->description = $this->company->description;
            $this->address = $this->company->address;
        } else {
            $this->authorize('company-create');
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
        ];
    }

    public function submit()
    {
        $this->validate();

        if ($this->editing) {
            $this->company->update([
                'name' => $this->name,
                'phone' => $this->phone,
                'description' => $this->description,
                'address' => $this->address,
            ]);

            return to_route('companies')->with('success', 'Company updated successfully!');
        } else {
            Company::create([
                'name' => $this->name,
                'phone' => $this->phone,
                'description' => $this->description,
                'address' => $this->address,
            ]);

            return to_route('companies')->with('success', 'Company created successfully!');
        }
    }
    public function render()
    {
        return view('livewire.companies.company-create');
    }
}
