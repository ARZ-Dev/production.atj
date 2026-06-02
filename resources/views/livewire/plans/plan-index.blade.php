<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="cal-header">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="cal-title">
                {{ \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->format('F Y') }}
            </span>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-light" wire:click="prevMonth">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-light" wire:click="goToToday">Today</button>
                <button class="btn btn-light" wire:click="nextMonth">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
        @hasPermission('production.plan-create')
        <button type="button" class="btn btn-primary btn-sm" wire:click="create">
            <i class="bi bi-plus-lg me-1"></i> New Plan
        </button>
        @endhasPermission
    </div>

    <div class="card mb-0">
        <div class="card-body p-0">
            <div class="cal-wrap">

                <div class="cal-dow">
                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
                        <div class="cal-dow-cell">{{ $dow }}</div>
                    @endforeach
                </div>

                @php
                    $daysInMonth = $startOfMonth->daysInMonth;
                    $firstDow    = $startOfMonth->dayOfWeek;
                    $padding     = $firstDow === 0 ? 6 : $firstDow - 1;
                    $today       = now()->format('Y-m-d');
                    $colors      = ['#818cf8','#34d399','#fbbf24','#38bdf8','#f87171','#a78bfa'];
                    $totalCells  = $padding + $daysInMonth;
                    $trailing    = (7 - ($totalCells % 7)) % 7;
                @endphp

                <div class="cal-body">

                    @for($i = 0; $i < $padding; $i++)
                        <div class="cal-cell is-empty"></div>
                    @endfor

                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateStr  = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            $dayPlans = $plansByDate[$dateStr] ?? collect();
                            $isToday  = $dateStr === $today;
                        @endphp
                        <div class="cal-cell {{ $isToday ? 'is-today' : '' }}">
                            <div class="cal-date-row">
                                <span class="cal-day-num">{{ $day }}</span>
                                @hasPermission('production.plan-create')
                                <button class="cal-add-btn"
                                    wire:click="createForDate('{{ $dateStr }}')"
                                    title="Add plan">+</button>
                                @endhasPermission
                            </div>

                            @foreach($dayPlans as $plan)
                                @php $color = $colors[($plan->shift_id ?? 0) % count($colors)]; @endphp
                                <div class="plan-card" style="border-left-color:{{ $color }}">
                                    <div class="plan-card-name" style="color:{{ $color }}">
                                        {{ $plan->shift?->name ?? 'No Shift' }}
                                    </div>
                                    @if($plan->shift)
                                    <div class="plan-card-time">
                                        {{ \Carbon\Carbon::parse($plan->shift->from_time)->format('H:i') }}
                                        – {{ \Carbon\Carbon::parse($plan->shift->to_time)->format('H:i') }}
                                    </div>
                                    @endif
                                    <div class="plan-card-foot">
                                        <span class="plan-card-evt">
                                            <i class="bi bi-card-list" style="font-size:10px"></i>
                                            {{ $plan->events_count }}
                                        </span>
                                        <div class="pca">
                                            <a href="{{ route('plans.view', $plan->id) }}"
                                               class="pca-btn v" title="View"><i class="bi bi-eye"></i></a>
                                            @hasPermission('production.event-create')
                                            <a href="{{ route('events.create', $plan->id) }}"
                                               class="pca-btn e" title="Events"><i class="bi bi-list-task"></i></a>
                                            @endhasPermission
                                            @hasPermission('production.plan-edit')
                                            <button class="pca-btn p" wire:click="edit({{ $plan->id }})"
                                                title="Edit"><i class="bi bi-pencil"></i></button>
                                            @endhasPermission
                                            @hasPermission('production.plan-delete')
                                            <button class="pca-btn d" title="Delete"
                                                wire:click="delete({{ $plan->id }})"
                                                wire:confirm="Delete this plan? This cannot be undone.">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endhasPermission
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endfor

                    @for($i = 0; $i < $trailing; $i++)
                        <div class="cal-cell is-empty"></div>
                    @endfor

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel"
        aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planModalLabel">
                        {{ $editing ? 'Edit Plan' : 'New Plan' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="plan_date" class="form-label">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                class="form-control @error('date') is-invalid @enderror"
                                id="plan_date" wire:model="date">
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="plan_shift">
                                Shift <span class="text-danger">*</span>
                            </label>
                            <div wire:ignore>
                                <select wire:model="shift_id" id="plan_shift"
                                    class="selectpicker w-100"
                                    title="Select shift…"
                                    data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5"
                                    data-tick-icon="ti-check text-white">
                                    @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}">
                                        {{ $shift->name }}
                                        ({{ \Carbon\Carbon::parse($shift->from_time)->format('H:i') }}
                                        – {{ \Carbon\Carbon::parse($shift->to_time)->format('H:i') }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('shift_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary"
                        data-bs-dismiss="modal" wire:click="resetForm">Cancel</button>
                    <button type="button" class="btn btn-primary"
                        wire:click="submit" wire:loading.attr="disabled">
                        <span wire:loading wire:target="submit"
                            class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const planModal = new bootstrap.Modal(document.getElementById('planModal'));

        $wire.on('openModal', () => {
            planModal.show();
            setTimeout(() => {
                let sid = $wire.get('shift_id');
                $('#plan_shift').selectpicker('val', sid ? String(sid) : '');
                let d = $wire.get('date');
                if (d) document.getElementById('plan_date').value = d;
            }, 250);
        });

        $wire.on('closeModal', () => planModal.hide());

        $('.selectpicker').selectpicker();
        $(document).on('change', '.selectpicker', function () {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });
    </script>
    @endscript
</div>
