<?php

namespace App\Filament\Pages;

use App\Http\Controllers\SapController;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class CompletePaidRequest extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static string $view = 'filament.pages.complete-paid-request';

    protected static ?string $navigationGroup = 'Business Management';

    protected static ?string $navigationLabel = 'Complete Paid Request';

    protected static ?string $title = 'Complete Paid Request';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_CompletePaidRequest') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('request_id')
                    ->label('Request ID')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => $this->searchPendingInvoiceRequests($search))
                    ->getOptionLabelUsing(fn ($value): ?string => $value ? $this->getRequestOptionLabel((int) $value) : null),

                Forms\Components\Select::make('payment_method')
                    ->label('Payment Method')
                    ->options(fn (): array => $this->paymentMethodOptions())
                    ->searchable()
                    ->required(),

                Forms\Components\Textarea::make('note')
                    ->label('Note')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function complete(): void
    {
        $data = $this->form->getState();

        $maintenanceRequest = MaintenanceRequest::query()
            ->with(['invoice', 'customer', 'technician', 'address.city', 'address.district', 'products'])
            ->whereKey($data['request_id'])
            ->whereHas('invoices', fn ($query) => $query->where('status', 'pending')->where('invoice_type', 'final'))
            ->firstOrFail();

        $userName = auth()->user()?->name ?: 'System';
        $note = trim((string) $data['note']);
        $paymentMethod = (string) $data['payment_method'];
        $statusNote = "Completed by: {$userName} | Payment method: {$paymentMethod} | Note: {$note}";

        DB::transaction(function () use ($maintenanceRequest, $paymentMethod, $statusNote): void {
            $maintenanceRequest->invoices()
                ->where('status', 'pending')
                ->where('invoice_type', 'final')
                ->latest()
                ->firstOrFail()
                ->update([
                'status' => 'completed',
                'payment_method' => $paymentMethod,
            ]);

            $maintenanceRequest->statuses()->create([
                'status' => 'completed',
                'notes' => $statusNote,
            ]);

            $maintenanceRequest->update([
                'last_status' => 'completed',
            ]);
        });

        $sapResult = app(SapController::class)->createSalesOrder(
            $maintenanceRequest->fresh(['invoice', 'invoice.services', 'invoice.spareParts', 'customer', 'technician', 'address.city', 'address.district']),
            $this->formatPaymentMethodForSap($paymentMethod),
        );

        $success = (bool) ($sapResult['success'] ?? false);

        Notification::make()
            ->title($success ? 'Request completed and sent to SAP' : 'Request completed, but SAP failed')
            ->body($sapResult['sap_desc'] ?? $sapResult['message'] ?? null)
            ->color($success ? 'success' : 'warning')
            ->send();

        $this->form->fill();
    }

    private function searchPendingInvoiceRequests(string $search): array
    {
        return MaintenanceRequest::query()
            ->with(['customer', 'invoice'])
            ->whereHas('invoices', fn ($query) => $query->where('status', 'pending')->where('invoice_type', 'final'))
            ->where(function ($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            })
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (MaintenanceRequest $request): array => [
                $request->id => $this->formatRequestOptionLabel($request),
            ])
            ->toArray();
    }

    private function getRequestOptionLabel(int $requestId): ?string
    {
        $request = MaintenanceRequest::query()
            ->with(['customer', 'invoice'])
            ->whereKey($requestId)
            ->first();

        return $request ? $this->formatRequestOptionLabel($request) : null;
    }

    private function formatRequestOptionLabel(MaintenanceRequest $request): string
    {
        $customerName = trim(
            ($request->customer?->first_name ?? '') . ' ' .
            ($request->customer?->last_name ?? '')
        ) ?: 'No customer';

        $phone = $request->customer?->phone ?: '-';
        $total = number_format((float) ($request->invoice?->total ?? 0), 2);

        return "#{$request->id} | {$customerName} | {$phone} | {$total} SAR";
    }

    private function paymentMethodOptions(): array
    {
        return collect(Setting::paymentMethods())
            ->filter(fn (array $method): bool => (bool) ($method['is_active'] ?? false))
            ->mapWithKeys(fn (array $method): array => [
                $method['code'] => $method['label_en'] ?? $method['code'],
            ])
            ->toArray();
    }

    private function formatPaymentMethodForSap(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'online' => 'Online',
            'machine' => 'Machine',
            'cash' => 'Cash',
            'remittance' => 'Remittance',
            default => ucfirst($paymentMethod),
        };
    }
}
