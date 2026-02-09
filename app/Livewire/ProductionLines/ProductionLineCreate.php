<?php

namespace App\Livewire\ProductionLines;

use App\Models\Factory;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\ProductionLine;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ProductionLineCreate extends Component
{
    public $rows = [];
    public $factoryId;
    public $editing = false;
    public $id = null;
    public $warehouseTypes = [];
    public $machineTypes = [];

    public function mount($factoryId, $id = null)
    {
        $this->factoryId = $factoryId;

        $this->warehouseTypes = WarehouseType::all();
        $this->machineTypes = MachineType::all();

        if ($id) {
            $this->editing = true;
            $this->id = $id;

            // Load warehouses
            $warehouses = Warehouse::where('production_line_id', $id)->get();
            foreach ($warehouses as $warehouse) {
                $this->rows[] = [
                    'id' => $warehouse->id,
                    'type' => 'warehouse',
                    'warehouse_type' => $warehouse->warehouse_type_id,
                    'machine_type' => null,
                    'name' => $warehouse->name,
                ];
            }

            // Load machines
            $machines = Machine::where('production_line_id', $id)->get();
            foreach ($machines as $machine) {
                $this->rows[] = [
                    'id' => $machine->id,
                    'type' => 'machine',
                    'warehouse_type' => null,
                    'machine_type' => $machine->machine_type_id,
                    'name' => $machine->name,
                ];
            }
        }

        if (empty($this->rows)) {
            $this->rows[] = [
                'id' => null,
                'type' => null,
                'warehouse_type' => null,
                'machine_type' => null,
                'name' => '',
            ];
        }
    }

    public function addRow()
    {
        $this->rows[] = [
            'id' => null,
            'type' => null,
            'warehouse_type' => null,
            'machine_type' => null,
            'name' => '',
        ];
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function submit()
    {
        $this->validate([
            'rows.*.type' => 'required|in:warehouse,machine',
            'rows.*.warehouse_type' => 'required_if:rows.*.type,warehouse',
            'rows.*.machine_type' => 'required_if:rows.*.type,machine',
            'rows.*.name' => 'required|string|max:255',
        ]);

        DB::transaction(function () {
            $factory = Factory::findOrFail($this->factoryId);

            if ($this->editing) {
                $this->authorize('productionLine-edit');

                $productionLine = ProductionLine::findOrFail($this->id);
                $productionLine->update([
                    'factory_id' => $this->factoryId,
                ]);
            } else {
                $productionLine = ProductionLine::create([
                    'factory_id' => $this->factoryId,
                    'company_id' => $factory->company_id,
                ]);
            }

            $warehouseIds = [];
            $machineIds = [];

            foreach ($this->rows as $row) {
                if ($row['type'] === 'warehouse') {
                    $warehouse = Warehouse::updateOrCreate(
                        [
                            'id' => $row['id'] ?? null,
                            'production_line_id' => $productionLine->id,
                        ],
                        [
                            'company_id' => $productionLine->company_id,
                            'warehouse_type_id' => $row['warehouse_type'],
                            'name' => $row['name'],
                        ]
                    );

                    $warehouseIds[] = $warehouse->id;
                }

                if ($row['type'] === 'machine') {
                    $machine = Machine::updateOrCreate(
                        [
                            'id' => $row['id'] ?? null,
                            'production_line_id' => $productionLine->id,
                        ],
                        [
                            'company_id' => $productionLine->company_id,
                            'machine_type_id' => $row['machine_type'],
                            'name' => $row['name'],
                        ]
                    );

                    $machineIds[] = $machine->id;
                }
            }

            // Delete removed records
            Warehouse::where('production_line_id', $productionLine->id)
                ->whereNotIn('id', $warehouseIds)
                ->delete();

            Machine::where('production_line_id', $productionLine->id)
                ->whereNotIn('id', $machineIds)
                ->delete();
        });

        return to_route('production-lines', ['factoryId' => $this->factoryId])
            ->with('success', $this->editing
                ? 'Production Line updated successfully!'
                : 'Production Line created successfully!');
    }

    public function render()
    {
        return view('livewire.productionlines.production-line-create');
    }
}
