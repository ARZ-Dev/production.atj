<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A station belongs to a department and its factory (a warehouse of type
     * factory) — the same pair a production line or a packaging carries. No
     * foreign keys: both live in the parent. Nullable so a station saved
     * before stays readable; the Stations page asks for both on save.
     *
     * A station already on a line takes that line's department and the
     * factory of the line's production line.
     */
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('station_type_id')->index();
            $table->unsignedBigInteger('factory_id')->nullable()->after('department_id')->index();
        });

        $placed = DB::table('line_station')
            ->join('lines', 'lines.id', '=', 'line_station.line_id')
            ->leftJoin('line_production_line', 'line_production_line.line_id', '=', 'lines.id')
            ->leftJoin('production_lines', 'production_lines.id', '=', 'line_production_line.production_line_id')
            ->orderBy('line_station.line_id')
            ->get(['line_station.station_id', 'lines.department_id', 'production_lines.factory_id']);

        foreach ($placed->groupBy('station_id') as $stationId => $rows) {
            DB::table('stations')->where('id', $stationId)->update([
                'department_id' => $rows->pluck('department_id')->filter()->first(),
                'factory_id'    => $rows->pluck('factory_id')->filter()->first(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
            $table->dropIndex(['factory_id']);
            $table->dropColumn(['department_id', 'factory_id']);
        });
    }
};
