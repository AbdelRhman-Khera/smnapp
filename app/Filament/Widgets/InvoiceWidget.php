<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;

class InvoiceWidget extends Widget
{
    protected static string $view = 'filament.widgets.invoice-widget';

    public ?Invoice $invoice = null;
    public ?MaintenanceRequest $record = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {

        return request()->routeIs('filament.admin.resources.maintenance-requests.view');
    }
    public function mount($record)
    {
        $this->record = $record;
        $this->invoice = Invoice::with(['services', 'spareParts'])
            ->where('maintenance_request_id', $record->id)
            ->first();
    }


    // public function mount()
    // {
    //     $recordId = request()->route('record');
    //     $this->invoice = Invoice::where('maintenance_request_id', $recordId)->first();
    // }
}
