<x-filament-panels::page>
    @php($calendar = $this->calendarData)

    <div class="tech-calendar">
        <div class="tech-calendar__toolbar">
            <div class="tech-calendar__field tech-calendar__field--wide">
                <label>Tech Name</label>
                <select wire:model.live="technicianId">
                    <option value="">All</option>
                    @foreach ($this->technicianOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tech-calendar__field">
                <label>From</label>
                <input type="date" wire:model.live="from">
            </div>

            <div class="tech-calendar__field">
                <label>To</label>
                <input type="date" wire:model.live="to">
            </div>

            <div class="tech-calendar__actions">
                <button type="button" wire:click="previousWeek">Previous</button>
                <button type="button" wire:click="currentWeek">This Week</button>
                <button type="button" wire:click="nextWeek">Next</button>
                <button type="button" wire:click="toggleFullDay">
                    {{ $showFullDay ? 'Show 8 AM - 8 PM' : 'Show Full Day' }}
                </button>
            </div>
        </div>

        <div class="tech-calendar__summary">
            <span>All Appointments <strong>{{ $calendar['summary']['total'] }}</strong></span>
            <span class="tech-calendar__summary-free">Free <strong>{{ $calendar['summary']['free'] }}</strong></span>
            <span class="tech-calendar__summary-booked">Occupied <strong>{{ $calendar['summary']['occupied'] }}</strong></span>
            <span class="tech-calendar__summary-empty">No Slot <strong>{{ $calendar['summary']['empty'] }}</strong></span>
        </div>

        <div class="tech-calendar__table-wrap">
            <table class="tech-calendar__table">
                <thead>
                    <tr>
                        <th class="tech-calendar__sticky tech-calendar__tech-head">Technician</th>
                        @foreach ($calendar['dates'] as $date)
                            <th colspan="{{ count($calendar['hours']) }}" class="tech-calendar__date-head">
                                {{ $date['label'] }}
                                <small>{{ $date['day'] }}</small>
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="tech-calendar__sticky tech-calendar__time-head">Date / Time</th>
                        @foreach ($calendar['dates'] as $date)
                            @foreach ($calendar['hours'] as $hour)
                                <th class="tech-calendar__hour-head">
                                    {{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse ($calendar['technicians'] as $technician)
                        <tr>
                            <th class="tech-calendar__sticky tech-calendar__tech-name">
                                <span>{{ trim($technician->first_name . ' ' . $technician->last_name) ?: 'Technician #' . $technician->id }}</span>
                                <small>{{ $technician->phone }}</small>
                            </th>

                            @foreach ($calendar['dates'] as $date)
                                @foreach ($calendar['hours'] as $hour)
                                    @php
                                        $time = sprintf('%02d:00:00', $hour);
                                        $cell = $calendar['cells'][$technician->id][$date['value']][$time];
                                        $slot = $cell['slot'];
                                        $request = $cell['request'];
                                    @endphp

                                    <td>
                                        @if (! $slot)
                                            @if (auth()->user()?->can('create_slot'))
                                                <button
                                                    type="button"
                                                    class="tech-calendar__cell tech-calendar__cell--empty"
                                                    wire:click="createSlot({{ $technician->id }}, '{{ $date['value'] }}', '{{ $time }}')"
                                                    wire:confirm="Create available slot for this technician at {{ $date['label'] }} {{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}?"
                                                >
                                                    <strong>+</strong>
                                                    <span>Add</span>
                                                </button>
                                            @else
                                                <div class="tech-calendar__cell tech-calendar__cell--empty tech-calendar__cell--locked">
                                                    <strong>+</strong>
                                                    <span>No Slot</span>
                                                </div>
                                            @endif
                                        @elseif (! $slot->is_booked)
                                            <div class="tech-calendar__cell tech-calendar__cell--free">
                                                <strong>Available</strong>
                                                <span>#{{ $slot->id }}</span>
                                            </div>
                                        @else
                                            @if ($request && $cell['url'])
                                                <a href="{{ $cell['url'] }}" class="tech-calendar__cell tech-calendar__cell--booked">
                                                    <strong>Booked</strong>
                                                    <span>Order #{{ $request->id }}</span>
                                                </a>
                                            @else
                                                <div class="tech-calendar__cell tech-calendar__cell--booked">
                                                    <strong>Booked</strong>
                                                    <span>Slot #{{ $slot->id }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="tech-calendar__empty-state">No technicians found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .tech-calendar {
            display: grid;
            gap: 14px;
        }

        .tech-calendar__toolbar,
        .tech-calendar__summary {
            align-items: end;
            background: #ffffff;
            border: 1px solid #d8dee4;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px;
        }

        .dark .tech-calendar__toolbar,
        .dark .tech-calendar__summary,
        .dark .tech-calendar__table {
            background: #111827;
            border-color: #374151;
        }

        .tech-calendar__field {
            display: grid;
            gap: 4px;
            min-width: 150px;
        }

        .tech-calendar__field--wide {
            min-width: 260px;
        }

        .tech-calendar__field label {
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .dark .tech-calendar__field label {
            color: #cbd5e1;
        }

        .tech-calendar__field input,
        .tech-calendar__field select {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            min-height: 38px;
            padding: 6px 10px;
        }

        .dark .tech-calendar__field input,
        .dark .tech-calendar__field select {
            background: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }

        .tech-calendar__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tech-calendar__actions button {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            min-height: 38px;
            padding: 7px 12px;
        }

        .dark .tech-calendar__actions button {
            background: #1f2937;
            border-color: #475569;
            color: #f8fafc;
        }

        .tech-calendar__summary span {
            border: 1px solid #d8dee4;
            border-radius: 6px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            padding: 7px 10px;
        }

        .dark .tech-calendar__summary span {
            border-color: #475569;
            color: #f8fafc;
        }

        .tech-calendar__summary-free {
            background: #dcfce7;
        }

        .tech-calendar__summary-booked {
            background: #fee2e2;
        }

        .tech-calendar__summary-empty {
            background: #e0f2fe;
        }

        .tech-calendar__table-wrap {
            border: 1px solid #111827;
            border-radius: 8px;
            max-height: 72vh;
            overflow: auto;
        }

        .tech-calendar__table {
            background: #ffffff;
            border-collapse: collapse;
            min-width: 1280px;
            table-layout: fixed;
            width: max-content;
        }

        .tech-calendar__table th,
        .tech-calendar__table td {
            border: 1px solid #111827;
            height: 64px;
            min-width: 112px;
            padding: 0;
            text-align: center;
            vertical-align: middle;
        }

        .tech-calendar__table thead th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            position: sticky;
            top: 0;
            z-index: 3;
        }

        .dark .tech-calendar__table thead th {
            background: #1f2937;
            color: #f8fafc;
        }

        .tech-calendar__table thead tr:nth-child(2) th {
            top: 64px;
        }

        .tech-calendar__sticky {
            left: 0;
            min-width: 190px !important;
            position: sticky;
            z-index: 4 !important;
        }

        .tech-calendar__tech-head,
        .tech-calendar__time-head,
        .tech-calendar__tech-name {
            background: #bfdbfe !important;
        }

        .dark .tech-calendar__tech-head,
        .dark .tech-calendar__time-head,
        .dark .tech-calendar__tech-name {
            background: #1e3a8a !important;
            color: #f8fafc;
        }

        .tech-calendar__date-head {
            height: 64px;
        }

        .tech-calendar__date-head small,
        .tech-calendar__tech-name small {
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin-top: 2px;
            opacity: .75;
        }

        .tech-calendar__tech-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            padding: 8px !important;
            text-align: start !important;
        }

        .tech-calendar__cell {
            align-items: center;
            border: 0;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            gap: 3px;
            height: 100%;
            justify-content: center;
            min-height: 64px;
            padding: 6px;
            text-decoration: none;
            width: 100%;
        }

        .tech-calendar__cell strong {
            font-size: 12px;
            line-height: 1.1;
        }

        .tech-calendar__cell span {
            font-size: 11px;
            line-height: 1.15;
        }

        .tech-calendar__cell--free {
            background: #22c55e;
            color: #052e16;
        }

        .tech-calendar__cell--booked {
            background: #ef4444;
            color: #ffffff;
        }

        .tech-calendar__cell--empty {
            background: #eff6ff;
            cursor: pointer;
        }

        .tech-calendar__cell--empty strong {
            font-size: 24px;
            line-height: 1;
        }

        .tech-calendar__cell--empty:hover {
            background: #dbeafe;
        }

        .tech-calendar__empty-state {
            padding: 24px !important;
            text-align: center;
        }
    </style>
</x-filament-panels::page>
