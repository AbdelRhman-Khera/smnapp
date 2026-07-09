<?php

namespace App\Filament\Resources\TechnicianPayoutRequestResource\Pages;

use App\Filament\Resources\TechnicianPayoutRequestResource;
use App\Models\TechnicianPayoutRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewTechnicianPayoutRequest extends ViewRecord
{
    protected static string $resource = TechnicianPayoutRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printVoucher')
                ->label('Print Payout Voucher')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === 'approved')
                ->url(fn (): string => route('admin.technician-payouts.print', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'pending' && (auth()->user()?->can('update', $this->record) ?? false))
                ->requiresConfirmation()
                ->modalDescription('The payout amount will be deducted from the technician wallet balance automatically.')
                ->form([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    /** @var TechnicianPayoutRequest $record */
                    $record = $this->record;
                    $record->approve(auth()->id(), $data['admin_notes'] ?? null);
                    $this->refreshFormData(['status', 'admin_notes', 'processed_by', 'processed_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'pending' && (auth()->user()?->can('update', $this->record) ?? false))
                ->requiresConfirmation()
                ->modalDescription('The amount will return to the technician wallet balance.')
                ->form([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Rejection Reason')
                        ->required(),
                ])
                ->action(function (array $data) {
                    /** @var TechnicianPayoutRequest $record */
                    $record = $this->record;
                    $record->reject(auth()->id(), $data['admin_notes'] ?? null);
                    $this->refreshFormData(['status', 'admin_notes', 'processed_by', 'processed_at']);
                }),
        ];
    }
}
