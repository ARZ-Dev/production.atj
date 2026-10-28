<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Station types and stations (machines) as master data.
     *
     *  - station_types: what a machine does — Cutting, Cooking, Creaming,
     *    Packaging, Packing, Filling & Seaming … A recipe stage picks one.
     *  - stations: the machines themselves — Oven 1, Flow Wrap 3 … — each of
     *    one type, with its own rate (per hour or minute, in its type's unit).
     *  - line_station: which machines make up a line, in its order. A line's
     *    rate at a stage is the sum of its machines of that stage's type.
     *
     * Replaces line_stations (name + machine count + rate typed on each line
     * row): every row becomes a type (by its name) and as many machines as it
     * counted; recipe stages are linked to the type of their name, and the
     * station a line stop or station stop was charged to moves to the machine.
     */
    public function up(): void
    {
        Schema::create('station_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->string('rate_unit', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_type_id')->constrained('station_types')->restrictOnDelete();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->decimal('rate', 15, 4)->nullable();
            $table->string('rate_period', 10)->default('hour');   // hour | minute
            $table->string('rate_unit', 50)->nullable();          // else the type's
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('line_station', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();

            $table->unique(['line_id', 'station_id']);
        });

        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->after('recipe_id')
                ->constrained('station_types')->nullOnDelete();
        });

        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('event_id')
                ->constrained('stations')->nullOnDelete();
        });

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('event_type_id')
                ->constrained('stations')->nullOnDelete();
        });

        if (Schema::hasTable('line_stations')) {
            $this->convertLineStations();
        }

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('line_station_id');
            $table->dropColumn('machine_number');
        });

        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('line_station_id');
        });

        Schema::dropIfExists('line_stations');
    }

    /**
     * Every line_stations row → its type (by name) and machine_count machines
     * on that line; recipe stages and logged stops follow.
     */
    private function convertLineStations(): void
    {
        $now     = now();
        $rows    = DB::table('line_stations')->orderBy('line_id')->orderBy('sequence')->orderBy('id')->get();
        $stages  = DB::table('recipe_stages')->whereNull('deleted_at')->get(['id', 'name', 'sequence']);
        $lines   = DB::table('lines')->pluck('name', 'id');
        $key     = fn($name) => mb_strtolower(trim((string) $name));
        $typeIds = [];

        // One type per distinct name, ordered as the lines and recipes had them.
        $names = $rows->map(fn($row) => ['name' => trim($row->name), 'sequence' => (int) $row->sequence, 'unit' => $row->rate_unit])
            ->concat($stages->map(fn($stage) => ['name' => trim($stage->name), 'sequence' => (int) $stage->sequence, 'unit' => null]))
            ->filter(fn($entry) => $entry['name'] !== '')
            ->groupBy(fn($entry) => $key($entry['name']));

        foreach ($names as $typeKey => $entries) {
            $typeIds[$typeKey] = DB::table('station_types')->insertGetId([
                'name'       => $entries->first()['name'],
                'sequence'   => $entries->min('sequence'),
                'rate_unit'  => $entries->pluck('unit')->filter()->countBy()->sortDesc()->keys()->first(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Each row's machines; a row that counted several becomes that many.
        $machinesOf = [];

        foreach ($rows as $row) {
            $count = max(1, (int) $row->machine_count);
            $where = '(' . ($lines[$row->line_id] ?? "line {$row->line_id}") . ')';
            $base  = $row->machine ?: trim($row->name);
            $base  = str_contains($base, $where) ? $base : "{$base} {$where}";

            for ($i = 1; $i <= $count; $i++) {
                $machineId = DB::table('stations')->insertGetId([
                    'station_type_id' => $typeIds[$key($row->name)],
                    'name'            => $base . ($count > 1 ? " {$i}" : ''),
                    'rate'            => $row->rate,
                    'rate_period'     => $row->rate_period ?: 'hour',
                    'rate_unit'       => $row->rate_unit,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                DB::table('line_station')->insert([
                    'line_id'    => $row->line_id,
                    'station_id' => $machineId,
                    'sequence'   => $row->sequence,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $machinesOf[$row->id][] = $machineId;
            }
        }

        foreach ($stages as $stage) {
            if (isset($typeIds[$key($stage->name)])) {
                DB::table('recipe_stages')->where('id', $stage->id)->update(['station_type_id' => $typeIds[$key($stage->name)]]);
            }
        }

        foreach (DB::table('event_status_logs')->whereNotNull('line_station_id')->get(['id', 'line_station_id']) as $log) {
            DB::table('event_status_logs')->where('id', $log->id)->update(['station_id' => $machinesOf[$log->line_station_id][0] ?? null]);
        }

        foreach (DB::table('event_pause_activities')->whereNotNull('line_station_id')->get(['id', 'line_station_id', 'machine_number']) as $activity) {
            $machines = $machinesOf[$activity->line_station_id] ?? [];
            $index    = max(0, (int) $activity->machine_number - 1);

            DB::table('event_pause_activities')->where('id', $activity->id)->update(['station_id' => $machines[$index] ?? $machines[0] ?? null]);
        }
    }

    public function down(): void
    {
        Schema::create('line_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('name');
            $table->string('machine')->nullable();
            $table->unsignedSmallInteger('machine_count')->default(1);
            $table->decimal('rate', 15, 4)->nullable();
            $table->string('rate_period', 10)->default('hour');
            $table->string('rate_unit', 50)->nullable();
            $table->timestamps();

            $table->index(['line_id', 'sequence']);
        });

        // Best effort: one row per line and type, its first machine's rate.
        $now = now();
        $rows = DB::table('line_station')
            ->join('stations', 'stations.id', '=', 'line_station.station_id')
            ->join('station_types', 'station_types.id', '=', 'stations.station_type_id')
            ->orderBy('line_station.line_id')->orderBy('line_station.sequence')
            ->get(['line_station.line_id', 'line_station.sequence', 'station_types.name as type', 'stations.name', 'stations.rate', 'stations.rate_period', 'stations.rate_unit']);

        foreach ($rows->groupBy(fn($row) => $row->line_id . '|' . $row->type) as $group) {
            $first = $group->first();
            DB::table('line_stations')->insert([
                'line_id'       => $first->line_id,
                'sequence'      => $first->sequence,
                'name'          => $first->type,
                'machine'       => $group->count() > 1 ? preg_replace('/ 1$/', '', $first->name) : $first->name,
                'machine_count' => $group->count(),
                'rate'          => $group->pluck('rate')->filter()->first(),
                'rate_period'   => $first->rate_period,
                'rate_unit'     => $first->rate_unit,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->foreignId('line_station_id')->nullable()->after('event_id')->constrained('line_stations')->nullOnDelete();
        });

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->foreignId('line_station_id')->nullable()->after('event_type_id')->constrained('line_stations')->nullOnDelete();
            $table->unsignedSmallInteger('machine_number')->nullable()->after('station_name');
        });

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_id');
        });

        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_id');
        });

        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_type_id');
        });

        Schema::dropIfExists('line_station');
        Schema::dropIfExists('stations');
        Schema::dropIfExists('station_types');
    }
};
