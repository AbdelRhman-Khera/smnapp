<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Models\Slot;
use App\Models\Technician;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TechnicianCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.technician-calendar';

    protected static ?string $navigationGroup = 'Technicians Management';

    protected static ?string $navigationLabel = 'Technician Calendar';

    protected static ?string $title = 'Technician Calendar';

    protected static ?int $navigationSort = 2;

    public int|string|null $technicianId = null;

    public ?string $from = null;

    public ?string $to = null;

    public bool $showFullDay = false;

    public function mount(): void
    {
        $start = now()->startOfWeek(Carbon::SATURDAY);

        $this->from = $start->toDateString();
        $this->to = $start->copy()->addDays(6)->toDateString();
        $this->technicianId = request()->integer('technicianId') ?: Technician::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->value('id');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_TechnicianCalendar') ?? false;
    }

    public function previousWeek(): void
    {
        $start = Carbon::parse($this->from)->subWeek()->startOfWeek(Carbon::SATURDAY);

        $this->from = $start->toDateString();
        $this->to = $start->copy()->addDays(6)->toDateString();
    }

    public function currentWeek(): void
    {
        $start = now()->startOfWeek(Carbon::SATURDAY);

        $this->from = $start->toDateString();
        $this->to = $start->copy()->addDays(6)->toDateString();
    }

    public function nextWeek(): void
    {
        $start = Carbon::parse($this->from)->addWeek()->startOfWeek(Carbon::SATURDAY);

        $this->from = $start->toDateString();
        $this->to = $start->copy()->addDays(6)->toDateString();
    }

    public function toggleFullDay(): void
    {
        $this->showFullDay = ! $this->showFullDay;
    }

    public function createSlot(int $technicianId, string $date, string $time): void
    {
        abort_unless(auth()->user()?->can('create_slot'), 403);

        $slot = Slot::query()->firstOrCreate(
            [
                'technician_id' => $technicianId,
                'date' => Carbon::parse($date)->toDateString(),
                'time' => Carbon::parse($time)->format('H:00:00'),
            ],
            [
                'is_booked' => false,
            ],
        );

        Notification::make()
            ->title($slot->wasRecentlyCreated ? 'Slot created successfully' : 'Slot already exists')
            ->success()
            ->send();
    }

    public function getCalendarDataProperty(): array
    {
        [$start, $end] = $this->dateBounds();

        $technician = Technician::query()
            ->select(['id', 'first_name', 'last_name', 'phone'])
            ->when($this->technicianId, fn ($query) => $query->whereKey($this->technicianId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->first();

        $hours = $this->hours();
        $dates = $this->dates($start, $end);

        $timeValues = collect($hours)
            ->map(fn (int $hour): string => sprintf('%02d:00:00', $hour))
            ->all();

        $slots = Slot::query()
            ->when($technician, fn ($query) => $query->where('technician_id', $technician->id))
            ->when(! $technician, fn ($query) => $query->whereRaw('1 = 0'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('time', $timeValues)
            ->get();

        $slotIds = $slots->pluck('id')->all();

        $requests = MaintenanceRequest::query()
            ->with(['customer:id,first_name,last_name,phone'])
            ->when($technician, fn ($query) => $query->where('technician_id', $technician->id))
            ->when(! $technician, fn ($query) => $query->whereRaw('1 = 0'))
            ->where(function ($query) use ($slotIds) {
                $query->whereIn('slot_id', $slotIds)
                    ->orWhereNotNull('extra_slot_id');
            })
            ->get();

        $requestsBySlot = $this->requestsBySlot($requests, $slotIds);

        $slotsByKey = $slots->keyBy(
            fn (Slot $slot): string => $this->slotKey(
                (int) $slot->technician_id,
                Carbon::parse($slot->date)->toDateString(),
                Carbon::parse($slot->time)->format('H:00:00'),
            ),
        );

        return [
            'dates' => $dates,
            'hours' => $hours,
            'technician' => $technician,
            'cells' => $this->cells($technician, $slotsByKey, $requestsBySlot, $dates, $hours),
            'summary' => $this->summary($slots, $technician, $dates, $hours),
        ];
    }

    public function getTechnicianOptionsProperty(): Collection
    {
        return Technician::query()
            ->select(['id', 'first_name', 'last_name', 'phone'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Technician $technician): array => [
                $technician->id => trim($technician->first_name . ' ' . $technician->last_name) ?: 'Technician #' . $technician->id,
            ]);
    }

    private function cells(?Technician $technician, Collection $slotsByKey, Collection $requestsBySlot, array $dates, array $hours): array
    {
        $cells = [];

        if (! $technician) {
            return $cells;
        }

        foreach ($dates as $date) {
            foreach ($hours as $hour) {
                $time = sprintf('%02d:00:00', $hour);
                $key = $this->slotKey((int) $technician->id, $date['value'], $time);
                $slot = $slotsByKey->get($key);
                $request = $slot ? $requestsBySlot->get($slot->id) : null;

                $cells[$date['value']][$time] = [
                    'slot' => $slot,
                    'request' => $request,
                    'url' => $request ? MaintenanceRequestResource::getUrl('view', ['record' => $request->id]) : null,
                ];
            }
        }

        return $cells;
    }

    private function requestsBySlot(Collection $requests, array $slotIds): Collection
    {
        $slotIdLookup = array_flip($slotIds);
        $mapped = collect();

        foreach ($requests as $request) {
            if ($request->slot_id && isset($slotIdLookup[$request->slot_id])) {
                $mapped->put($request->slot_id, $request);
            }

            foreach ((array) ($request->extra_slot_id ?? []) as $extraSlotId) {
                if (isset($slotIdLookup[$extraSlotId])) {
                    $mapped->put($extraSlotId, $request);
                }
            }
        }

        return $mapped;
    }

    private function summary(Collection $slots, ?Technician $technician, array $dates, array $hours): array
    {
        $totalCells = ($technician ? 1 : 0) * count($dates) * count($hours);
        $free = $slots->where('is_booked', false)->count();
        $occupied = $slots->where('is_booked', true)->count();

        return [
            'total' => $totalCells,
            'free' => $free,
            'occupied' => $occupied,
            'empty' => max(0, $totalCells - $slots->count()),
        ];
    }

    private function dates(Carbon $start, Carbon $end): array
    {
        return collect(CarbonPeriod::create($start, $end))
            ->map(fn (Carbon $date): array => [
                'value' => $date->toDateString(),
                'label' => $date->format('d M'),
                'day' => $date->format('D'),
            ])
            ->all();
    }

    private function hours(): array
    {
        return $this->showFullDay ? range(0, 23) : range(8, 20);
    }

    private function dateBounds(): array
    {
        $start = Carbon::parse($this->from ?: now())->startOfDay();
        $end = Carbon::parse($this->to ?: $start->copy()->addDays(6))->startOfDay();

        if ($end->lessThan($start)) {
            $end = $start->copy()->addDays(6);
        }

        if ($start->diffInDays($end) > 30) {
            $end = $start->copy()->addDays(30);
            $this->to = $end->toDateString();
        }

        return [$start, $end];
    }

    private function slotKey(int $technicianId, string $date, string $time): string
    {
        return "{$technicianId}|{$date}|{$time}";
    }
}
