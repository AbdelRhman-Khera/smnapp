<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class InvoiceWidget extends Widget
{
    protected static string $view = 'filament.widgets.invoice-widget';

    public ?Invoice $invoice = null;
    public ?Collection $invoices = null;
    public ?MaintenanceRequest $record = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {

        return request()->routeIs('filament.admin.resources.maintenance-requests.view');
    }
    public function mount($record)
    {
        $this->record = $record;
        $this->invoices = Invoice::with(['services', 'spareParts'])
            ->where('maintenance_request_id', $record->id)
            ->orderByRaw("FIELD(invoice_type, 'visit_fee', 'workshop', 'final', 'zero_service')")
            ->latest()
            ->get();
        $this->invoice = $this->invoices->first();
    }


    // public function mount()
    // {
    //     $recordId = request()->route('record');
    //     $this->invoice = Invoice::where('maintenance_request_id', $recordId)->first();
    // }
}
