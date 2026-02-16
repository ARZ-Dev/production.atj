<?php

namespace App\Livewire\Plans;

use App\Models\Company;
use App\Models\Plan;
use App\Models\ProductionLine;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanIndex extends Component
{
    use AuthorizesRequests;

    public $plans;
    public $companies = [];
    public $productionLines = [];
    public $company_id;
    public $production_line_id;
    public $date;
    public $plan_id;
    public $editing = false;

    public function mount()
    {
        $this->loadPlans();

        if (auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        } else {
            $this->company_id = auth()->user()->company_id;
            $this->companies = Company::where('id', auth()->user()->company_id)->get();
            $this->productionLines = ProductionLine::where('company_id', auth()->user()->company_id)->get();
        }
    }

    public function loadPlans()
    {
        if (auth()->user()->hasRole('Super Admin')) {
            $this->plans = Plan::all();
        } else {
            $this->plans = Plan::where('company_id', auth()->user()->company_id)->get();
        }
    }

    public function resetForm()
    {
        $this->plan_id = null;
        $this->production_line_id = '';
        $this->date = '';
        $this->editing = false;
        if (!auth()->user()->hasRole('Super Admin')) {
            $this->company_id = auth()->user()->company_id;
        } else {
            $this->company_id = '';
            $this->productionLines = [];
        }
        $this->resetValidation();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id)
    {
        $this->resetForm();

        $plan = Plan::findOrFail($id);
        $this->plan_id = $plan->id;
        $this->company_id = $plan->company_id;
        $this->production_line_id = $plan->production_line_id;
        $this->date = $plan->date;
        $this->editing = true;

        if ($this->company_id) {
            $this->productionLines = ProductionLine::where('company_id', $this->company_id)->get();
            $this->dispatch('setProductionLines', $this->productionLines->map->only(['id'])->values()->toArray());
        }

        $this->dispatch('openModal');
    }

    #[On('GetProductionLines')]
    public function getProductionLines($company_id)
    {
        $productionLines = ProductionLine::where('company_id', $company_id)->get();
        $this->dispatch('setProductionLines', $productionLines);

    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'production_line_id' => 'required|exists:production_lines,id',
            'date' => 'required|date',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'company_id' => $this->company_id,
            'production_line_id' => $this->production_line_id,
            'date' => $this->date,
        ];

        if ($this->editing) {
            $plan = Plan::findOrFail($this->plan_id);
            $plan->update($data);
        } else {
            Plan::create($data);
        }

        $this->loadPlans();
        $this->dispatch('closeModal');
        $this->resetForm();

        return to_route('plans')->with('success', $this->editing ? 'Plan updated successfully.' : 'Plan created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return to_route('plans')->with('success', 'Plan deleted successfully.');
    }

    public function render()
    {
        return view('livewire.plans.plan-index');
    }
}