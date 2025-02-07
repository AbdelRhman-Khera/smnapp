<x-filament::widget>
    <x-filament::card>
        <h2 class="mb-4 text-xl font-bold">Maintenance Request History</h2>

        <x-filament::table>
            <x-slot name="header">
                <tr>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Slot Date</th>
                    <th>Slot Time</th>
                    <th>Rating</th>
                </tr>
            </x-slot>

            @foreach ($this->getTableQuery()->get() as $request)
                <tr>
                    <td>{{ $request->customer->first_name ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $request->type)) }}</td>
                    <td>{{ ucfirst($request->last_status) }}</td>
                    <td>{{ $request->slot?->date ?? 'N/A' }}</td>
                    <td>{{ $request->slot?->time ?? 'N/A' }}</td>
                    <td>{{ $request->feedback?->rating ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </x-filament::table>
    </x-filament::card>
</x-filament::widget>
