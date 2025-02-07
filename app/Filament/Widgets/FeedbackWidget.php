<?php

namespace App\Filament\Widgets;

use App\Models\Feedback;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeedbackWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?int $recordId = null;

    public function mount($record)
    {
        $this->recordId = $record->id;
    }

    protected function getTableQuery(): Builder
    {
        return Feedback::query()
            ->where('maintenance_request_id', $this->recordId)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('rating')
                ->label('Rating')
                ->sortable()
                ->formatStateUsing(fn ($state) => str_repeat('⭐', $state)), // Shows stars instead of numbers

            Tables\Columns\TextColumn::make('feedback_text')
                ->label('Feedback')
                ->limit(100)
                ->wrap()
                ->placeholder('No feedback provided'),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Submitted At')
                ->dateTime()
                ->sortable(),
        ];
    }
}
