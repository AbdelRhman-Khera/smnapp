<?php

namespace App\Filament\Resources\SupportFormResource\Pages;

use App\Filament\Resources\SupportFormResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportForm extends ViewRecord
{
    protected static string $resource = SupportFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addNote')
                ->label('Add Note')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('info')
                ->modalHeading('Add follow-up note')
                ->modalSubmitActionLabel('Add Note')
                ->form(SupportFormResource::noteFormSchema())
                ->action(function (array $data): void {
                    SupportFormResource::appendNote($this->record, $data);
                    $this->refreshFormData(['notes']);
                }),

            Actions\Action::make('toggleStatus')
                ->label(fn (): string => $this->record->status === 'open' ? 'Close Ticket' : 'Reopen Ticket')
                ->icon(fn (): string => $this->record->status === 'open' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-path')
                ->color(fn (): string => $this->record->status === 'open' ? 'success' : 'gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => $this->record->status === 'open' ? 'closed' : 'open']);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title($this->record->status === 'open' ? 'Ticket reopened' : 'Ticket closed')
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make(),
        ];
    }
}
