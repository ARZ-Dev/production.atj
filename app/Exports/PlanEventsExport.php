<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\ProductionLine;
use App\Services\ApiService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanEventsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected int $planId)
    {
    }

    public function collection()
    {
        return Event::with(['eventType', 'recipe', 'recipeType'])
            ->where('plan_id', $this->planId)
            ->orderBy('from_time')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Event Type', 'Item', 'Recipe Type', 'Recipe', 'Batch Count',
            'From', 'To', 'Duration (min)', 'Production Line', 'Lane',
            'Status', 'Description',
        ];
    }

    public function map($event): array
    {
        $hasRecipe = (bool) ($event->eventType?->has_recipe);

        $itemName = null;
        if ($event->item_id) {
            $itemName = app(ApiService::class)->get("/v1/items/{$event->item_id}")['data']['name'] ?? null;
        }

        $placeableName = null;
        if ($event->placeable_type && $event->placeable_id) {
            $placeableName = $event->placeable_type::find($event->placeable_id)?->name;
        }

        $productionLineName = $event->production_line_id
            ? ProductionLine::find($event->production_line_id)?->name
            : null;

        return [
            $event->eventType?->name ?? '—',
            $itemName ?? '—',
            $event->recipeType?->name ?? '—',
            $event->recipe?->name ?? '—',
            $event->batch_count ?? '—',
            $event->from_time ? Carbon::parse($event->from_time)->format('H:i') : '—',
            $event->to_time ? Carbon::parse($event->to_time)->format('H:i') : '—',
            ($hasRecipe ? $event->calculated_duration : $event->planned_duration) ?: '—',
            $productionLineName ?? '—',
            $placeableName ?? '—',
            $event->status ?? '—',
            $event->description ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
