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

    {{-- Monthly plans configured for the viewed month --}}
    <div class="month-plan-strip">
        <span class="month-plan-strip-label">
            <i class="bi bi-calendar2-week me-1"></i> Monthly plans
        </span>
        @forelse($monthPlans as $mp)
            <span class="month-plan-chip" title="{{ $mp->plans_count }} plan(s)">
                <i class="bi bi-building"></i>
                <span class="mpc-name">{{ $mp->display_name }}</span>
                <span class="mpc-count">{{ $mp->plans_count }}</span>
                @hasPermission('production.plan-delete')
                <button type="button" class="mpc-del js-delete-month-plan"
                    data-id="{{ $mp->id }}"
                    data-name="{{ $mp->display_name }}"
                    title="Delete monthly plan">
                    <i class="bi bi-x-lg"></i>
                </button>
                @endhasPermission
            </span>
        @empty
            <span class="month-plan-empty">No monthly plans yet for this month.</span>
        @endforelse
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
                                @php
                                    $color = $colors[$plan->id % count($colors)];
                                    $planName = $plan->monthPlan?->display_name ?: 'Plan #' . $plan->id;
                                @endphp
                                <div class="plan-card" style="border-left-color:{{ $color }}">
                                    <div class="plan-card-name" style="color:{{ $color }}" title="{{ $planName }}">
                                        {{ $planName }}
                                    </div>
                                    <div class="plan-card-foot">
                                        <span class="plan-card-evt">
                                            <i class="bi bi-card-list" style="font-size:10px"></i>
                                            {{ $plan->events_count }}
                                        </span>
                                        <div class="pca">
                                            <a href="{{ route('plans.view', $plan->id) }}"
                                               class="pca-btn v" title="View"><i class="bi bi-eye"></i></a>
                                            @hasPermission('production.event-create')
                                            <a href="{{ route('plans.view', $plan->id) }}"
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
                        <div class="col-12 col-md-6">
                            <label for="plan_department" class="form-label">Department</label>
                            <div wire:ignore>
                                <select id="plan_department" class="selectpicker w-100" title="Select department…"
                                    data-style="btn-default" data-live-search="true">
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept['id'] }}" @selected($department_id == $dept['id'])>
                                        {{ $dept['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-text">Used to filter the factory list.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="plan_factory" class="form-label">
                                Factory <span class="text-danger">*</span>
                            </label>
                            <div wire:ignore>
                                <select id="plan_factory" class="selectpicker w-100 @error('factory_id') is-invalid @enderror"
                                    title="Select factory…" data-style="btn-default" data-live-search="true"></select>
                            </div>
                            @error('factory_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Month-plan gate: a monthly plan must exist for the
                             selected factory before daily plans can be added. --}}
                        <div class="col-12">
                            @if(!$factory_id)
                                <div class="mp-gate mp-gate-muted">
                                    <i class="bi bi-arrow-up-circle"></i>
                                    <span>Select a factory to continue.</span>
                                </div>
                            @elseif(!$monthPlanReady)
                                <div class="mp-gate mp-gate-warn">
                                    <div class="mp-gate-head">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <div>
                                            <strong>No monthly plan for {{ \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->format('F Y') }}</strong>
                                            <div class="mp-gate-sub">
                                                {{ $selectedFactoryName ?? 'This factory' }} needs a monthly plan
                                                before you can add daily plans.
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-warning btn-sm mt-2"
                                        wire:click="createMonthPlan" wire:loading.attr="disabled" wire:target="createMonthPlan">
                                        <span wire:loading wire:target="createMonthPlan"
                                            class="spinner-border spinner-border-sm me-1"></span>
                                        <i class="bi bi-calendar2-plus me-1"></i>
                                        Create monthly plan
                                    </button>
                                </div>
                            @else
                                <div class="mp-gate mp-gate-ok">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Monthly plan ready for
                                        <strong>{{ $selectedFactoryName ?? 'this factory' }}</strong>
                                        — {{ \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->format('F Y') }}.
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label for="plan_date" class="form-label">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                class="form-control @error('date') is-invalid @enderror"
                                id="plan_date" wire:model="date" value="{{ $date }}"
                                min="{{ $startOfMonth->format('Y-m-d') }}"
                                max="{{ $startOfMonth->copy()->endOfMonth()->format('Y-m-d') }}"
                                @disabled(!$monthPlanReady)>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @unless($monthPlanReady)
                            <div class="form-text">Available once a monthly plan exists.</div>
                            @endunless
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary"
                        data-bs-dismiss="modal" wire:click="resetForm">Cancel</button>
                    <button type="button" class="btn btn-primary"
                        wire:click="submit" wire:loading.attr="disabled" wire:target="submit"
                        @disabled(!$monthPlanReady)>
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

        function buildPlanFactorySelect(factories, selectedId) {
            const $sel = $('#plan_factory');
            $sel.selectpicker('destroy');
            $sel.empty();
            (factories || []).forEach(f => {
                $sel.append($('<option>', { value: f.id, text: f.name, selected: f.id == selectedId }));
            });
            $sel.selectpicker();
        }

        $wire.on('openModal', ({ factories }) => {
            planModal.show();
            setTimeout(() => {
                let d = $wire.get('date');
                if (d) document.getElementById('plan_date').value = d;

                $('#plan_department').selectpicker('destroy').selectpicker();
                $('#plan_department').selectpicker('val', String($wire.get('department_id') || ''));
                buildPlanFactorySelect(factories, $wire.get('factory_id'));
            }, 150);
        });

        $wire.on('planFactoriesReady', ({ factories }) => {
            buildPlanFactorySelect(factories, null);
        });

        $wire.on('closeModal', () => planModal.hide());

        $wire.on('monthPlanCreated', ({ factory, period }) => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Monthly plan created',
                text: `You can now add plans for ${factory} in ${period}.`,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
        });

        $(document).on('change', '#plan_department', function () {
            $wire.call('onDepartmentChange', parseInt($(this).val()) || null);
        });
        $(document).on('change', '#plan_factory', function () {
            const id   = parseInt($(this).val()) || null;
            const name = $(this).find('option:selected').text() || null;
            $wire.call('onFactoryChange', id, name);
        });

        $(document).on('click', '.js-delete-month-plan', function () {
            const id   = $(this).data('id');
            const name = $(this).data('name');
            Swal.fire({
                title: 'Delete monthly plan?',
                html: `This removes the monthly plan for <strong>${name}</strong> and <u>all its daily plans</u> for this month. This cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.dispatch('deleteMonthPlan', { id: id });
                }
            });
        });
    </script>
    @endscript
</div>
