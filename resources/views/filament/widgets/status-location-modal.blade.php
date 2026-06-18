<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
        <span class="font-semibold">Coordinates:</span>
        {{ $latitude }}, {{ $longitude }}
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
        <iframe
            src="https://maps.google.com/maps?q={{ urlencode($latitude . ',' . $longitude) }}&z=15&output=embed"
            class="h-96 w-full"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
    </div>

    <a
        href="https://www.google.com/maps?q={{ urlencode($latitude . ',' . $longitude) }}"
        target="_blank"
        class="inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500"
    >
        Open in Google Maps
    </a>
</div>
