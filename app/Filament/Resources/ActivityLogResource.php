<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\Address;
use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Customer;
use App\Models\District;
use App\Models\Feedback;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Page;
use App\Models\Product;
use App\Models\RequestStatus;
use App\Models\SapRequestLog;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Slot;
use App\Models\SparePart;
use App\Models\SupportForm;
use App\Models\Technician;
use App\Models\TechnicianSparePartRequest;
use App\Models\TechnicianSparePartRequestItem;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activitylogs';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    protected static array $labelCache = [];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'subject' => function ($query) {
                    if (method_exists($query, 'withTrashed')) {
                        $query->withTrashed();
                    }
                },
                'causer',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::headline($state) : '-'),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Activity Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::modelLabel($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_label')
                    ->label('Record')
                    ->getStateUsing(fn (Activity $record): string => static::subjectLabel($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('subject_id', $search)
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('properties', 'like', "%{$search}%")),

                Tables\Columns\TextColumn::make('maintenance_request_lookup')
                    ->label('Request #')
                    ->getStateUsing(fn (Activity $record): string => static::maintenanceRequestLabel($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::applyMaintenanceRequestSearch($query, $search)),

                Tables\Columns\TextColumn::make('causer_label')
                    ->label('Changed By')
                    ->getStateUsing(fn (Activity $record): string => static::causerLabel($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHasMorph('causer', [User::class, Technician::class, Customer::class], function (Builder $query, string $type) use ($search) {
                            if ($type === User::class) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");

                                return;
                            }

                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })),

                Tables\Columns\TextColumn::make('changes_summary')
                    ->label('Changes')
                    ->getStateUsing(fn (Activity $record): string => static::changesSummary($record))
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Activity Type')
                    ->options(static::subjectTypeOptions())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                Tables\Filters\Filter::make('maintenance_request_id')
                    ->label('Maintenance Request #')
                    ->form([
                        TextInput::make('request_id')
                            ->label('Request ID')
                            ->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['request_id'] ?? null)
                        ? static::applyMaintenanceRequestSearch($query, (string) $data['request_id'])
                        : $query),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Activity')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('event')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? Str::headline($state) : '-'),
                                TextEntry::make('created_at')
                                    ->dateTime('Y-m-d h:i A'),
                                TextEntry::make('subject_type')
                                    ->label('Activity Type')
                                    ->formatStateUsing(fn (?string $state): string => static::modelLabel($state)),
                                TextEntry::make('subject_label')
                                    ->label('Record')
                                    ->state(fn (Activity $record): string => static::subjectLabel($record)),
                                TextEntry::make('causer_label')
                                    ->label('Changed By')
                                    ->state(fn (Activity $record): string => static::causerLabel($record)),
                                TextEntry::make('maintenance_request_lookup')
                                    ->label('Maintenance Request')
                                    ->state(fn (Activity $record): string => static::maintenanceRequestLabel($record)),
                                TextEntry::make('description')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Changed Values')
                    ->schema([
                        TextEntry::make('changed_values')
                            ->hiddenLabel()
                            ->state(fn (Activity $record): HtmlString => static::changesTable($record))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Activity $record): bool => count(static::formattedChanges($record)) > 0),

                Section::make('Raw Properties')
                    ->schema([
                        TextEntry::make('properties')
                            ->hiddenLabel()
                            ->state(fn (Activity $record): string => static::json($record->properties?->toArray() ?? []))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function applyMaintenanceRequestSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        return $query->where(function (Builder $query) use ($search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('subject_type', MaintenanceRequest::class)
                    ->where('subject_id', $search);
            })
                ->orWhere('properties', 'like', '%"maintenance_request_id":' . $search . '%')
                ->orWhere('properties', 'like', '%"maintenance_request_id":"' . $search . '"%');
        });
    }

    protected static function subjectTypeOptions(): array
    {
        return collect(static::trackedModels())
            ->mapWithKeys(fn (string $class): array => [$class => static::modelLabel($class)])
            ->sort()
            ->toArray();
    }

    protected static function trackedModels(): array
    {
        return [
            Address::class,
            Branch::class,
            Category::class,
            City::class,
            Customer::class,
            District::class,
            Feedback::class,
            Invoice::class,
            MaintenanceRequest::class,
            Page::class,
            Product::class,
            RequestStatus::class,
            SapRequestLog::class,
            Service::class,
            Slider::class,
            Slot::class,
            SparePart::class,
            SupportForm::class,
            Technician::class,
            TechnicianSparePartRequest::class,
            TechnicianSparePartRequestItem::class,
            User::class,
        ];
    }

    protected static function subjectLabel(Activity $activity): string
    {
        if (! $activity->subject_type || ! $activity->subject_id) {
            return '-';
        }

        return static::recordLabel($activity->subject_type, $activity->subject_id, $activity->subject)
            ?? (static::modelLabel($activity->subject_type) . ' #' . $activity->subject_id);
    }

    protected static function causerLabel(Activity $activity): string
    {
        if (! $activity->causer_type || ! $activity->causer_id) {
            return '-';
        }

        return static::recordLabel($activity->causer_type, $activity->causer_id, $activity->causer)
            ?? (static::modelLabel($activity->causer_type) . ' #' . $activity->causer_id);
    }

    protected static function maintenanceRequestLabel(Activity $activity): string
    {
        $id = static::maintenanceRequestId($activity);

        return $id ? ('#' . $id) : '-';
    }

    protected static function maintenanceRequestId(Activity $activity): ?int
    {
        if ($activity->subject_type === MaintenanceRequest::class) {
            return (int) $activity->subject_id;
        }

        $properties = $activity->properties?->toArray() ?? [];

        return (int) (
            Arr::get($properties, 'attributes.maintenance_request_id')
            ?? Arr::get($properties, 'old.maintenance_request_id')
            ?? 0
        ) ?: null;
    }

    protected static function changesSummary(Activity $activity): string
    {
        $changes = static::formattedChanges($activity);

        return $changes ? implode(' | ', array_slice($changes, 0, 3)) : '-';
    }

    protected static function formattedChanges(Activity $activity): array
    {
        return collect(static::changedRows($activity))
            ->map(fn (array $row): string => "{$row['field']}: {$row['old']} -> {$row['new']}")
            ->toArray();
    }

    protected static function changedRows(Activity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $attributes = Arr::get($properties, 'attributes', []);
        $old = Arr::get($properties, 'old', []);

        if (! is_array($attributes)) {
            return [];
        }

        $keys = collect(array_keys($attributes))
            ->merge(is_array($old) ? array_keys($old) : [])
            ->unique()
            ->reject(fn (string $key): bool => in_array($key, ['updated_at', 'created_at', 'deleted_at', 'password', 'token', 'otp', 'remember_token'], true))
            ->values();

        return $keys
            ->map(function (string $key) use ($activity, $attributes, $old): array {
                return [
                    'field' => static::fieldLabel($key),
                    'old' => array_key_exists($key, $old) ? static::formatFieldValue($key, $old[$key], $activity) : '-',
                    'new' => array_key_exists($key, $attributes) ? static::formatFieldValue($key, $attributes[$key], $activity) : '-',
                ];
            })
            ->toArray();
    }

    protected static function changesTable(Activity $activity): HtmlString
    {
        $rows = collect(static::changedRows($activity))
            ->map(function (array $row): string {
                return '<tr class="border-b border-gray-200 last:border-b-0 dark:border-gray-700">'
                    . '<td class="whitespace-nowrap px-3 py-2 text-sm font-medium text-gray-950 dark:text-white">' . e($row['field']) . '</td>'
                    . '<td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">' . nl2br(e($row['old'])) . '</td>'
                    . '<td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">' . nl2br(e($row['new'])) . '</td>'
                    . '</tr>';
            })
            ->implode('');

        return new HtmlString(
            '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">'
            . '<table class="w-full divide-y divide-gray-200 text-start dark:divide-gray-700">'
            . '<thead class="bg-gray-50 dark:bg-gray-800">'
            . '<tr>'
            . '<th class="px-3 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Field</th>'
            . '<th class="px-3 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Old Value</th>'
            . '<th class="px-3 py-2 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">New Value</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">'
            . $rows
            . '</tbody>'
            . '</table>'
            . '</div>'
        );
    }

    protected static function fieldLabel(string $key): string
    {
        return [
            'customer_id' => 'Customer',
            'technician_id' => 'Technician',
            'address_id' => 'Address',
            'city_id' => 'City',
            'district_id' => 'District',
            'category_id' => 'Category',
            'product_id' => 'Product',
            'spare_part_id' => 'Spare Part',
            'service_id' => 'Service',
            'branch_id' => 'Branch',
            'maintenance_request_id' => 'Maintenance Request',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'manager_id' => 'Manager',
            'user_id' => 'User',
        ][$key] ?? Str::headline($key);
    }

    protected static function formatFieldValue(string $key, mixed $value, Activity $activity): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $class = match ($key) {
            'customer_id' => Customer::class,
            'technician_id' => Technician::class,
            'address_id' => Address::class,
            'city_id' => City::class,
            'district_id' => District::class,
            'category_id' => Category::class,
            'product_id' => Product::class,
            'spare_part_id' => SparePart::class,
            'service_id' => Service::class,
            'branch_id' => Branch::class,
            'maintenance_request_id' => MaintenanceRequest::class,
            'created_by', 'updated_by', 'manager_id' => User::class,
            'user_id' => static::userTypeClass($activity) ?? User::class,
            default => null,
        };

        if ($class && is_scalar($value)) {
            return static::recordLabel($class, $value) ?? (static::modelLabel($class) . ' #' . $value);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return static::json($value);
        }

        return (string) $value;
    }

    protected static function userTypeClass(Activity $activity): ?string
    {
        $type = Arr::get($activity->properties?->toArray() ?? [], 'attributes.user_type')
            ?? Arr::get($activity->properties?->toArray() ?? [], 'old.user_type');

        return match ($type) {
            'customer' => Customer::class,
            'technician' => Technician::class,
            default => null,
        };
    }

    protected static function recordLabel(string $class, int|string|null $id, ?Model $record = null): ?string
    {
        if (! $id) {
            return null;
        }

        $cacheKey = $class . ':' . $id;

        if (isset(static::$labelCache[$cacheKey])) {
            return static::$labelCache[$cacheKey];
        }

        $record ??= static::findRecord($class, $id);

        if (! $record) {
            return null;
        }

        $label = match ($class) {
            User::class => $record->name ?: ('User #' . $id),
            Technician::class => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) . ($record->phone ? " ({$record->phone})" : ''),
            Customer::class => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) . ($record->phone ? " ({$record->phone})" : ''),
            City::class, District::class, Category::class, Product::class, Service::class, SparePart::class, Branch::class => ($record->name_ar ?? $record->name_en ?? $record->name ?? null) . ' #' . $id,
            Address::class => ($record->name ?? 'Address') . ' #' . $id,
            MaintenanceRequest::class => 'Maintenance Request #' . $id . ($record->customer?->phone ? " ({$record->customer->phone})" : ''),
            Invoice::class => 'Invoice #' . $id . ($record->maintenance_request_id ? " / Request #{$record->maintenance_request_id}" : ''),
            Feedback::class => 'Feedback #' . $id . ($record->maintenance_request_id ? " / Request #{$record->maintenance_request_id}" : ''),
            RequestStatus::class => ($record->status ?? 'Status') . ($record->maintenance_request_id ? " / Request #{$record->maintenance_request_id}" : ''),
            SapRequestLog::class => 'SAP Log #' . $id . ($record->maintenance_request_id ? " / Request #{$record->maintenance_request_id}" : ''),
            SupportForm::class => ($record->subject ?? 'Support Form') . ' #' . $id,
            TechnicianSparePartRequest::class => 'Technician Spare Part Request #' . $id,
            TechnicianSparePartRequestItem::class => 'Spare Part Request Item #' . $id,
            Page::class => ($record->title_ar ?? $record->title_en ?? 'Page') . ' #' . $id,
            Slider::class => ($record->title_ar ?? $record->title_en ?? 'Slider') . ' #' . $id,
            Slot::class => 'Slot #' . $id,
            default => static::modelLabel($class) . ' #' . $id,
        };

        return static::$labelCache[$cacheKey] = trim($label) ?: (static::modelLabel($class) . ' #' . $id);
    }

    protected static function findRecord(string $class, int|string $id): ?Model
    {
        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        $query = $class::query();

        if (method_exists($query, 'withTrashed')) {
            $query->withTrashed();
        }

        if ($class === MaintenanceRequest::class) {
            $query->with('customer');
        }

        return $query->find($id);
    }

    protected static function modelLabel(?string $class): string
    {
        if (! $class) {
            return '-';
        }

        return [
            MaintenanceRequest::class => 'Maintenance Request',
            RequestStatus::class => 'Request Status',
            SapRequestLog::class => 'SAP Request Log',
            TechnicianSparePartRequest::class => 'Technician Spare Part Request',
            TechnicianSparePartRequestItem::class => 'Technician Spare Part Request Item',
            SupportForm::class => 'Support Form',
            SparePart::class => 'Spare Part',
        ][$class] ?? Str::headline(class_basename($class));
    }

    protected static function json(mixed $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
