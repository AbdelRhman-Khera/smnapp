<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="complete" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-check-circle">
                Complete Request
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
