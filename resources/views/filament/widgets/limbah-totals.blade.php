
<x-filament::card>
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
            Ringkasan Total Jumlah Limbah
        </h2>

        @if (!empty($rows))
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-200">Limbah</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-200">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">Belum ada data.</div>
        @endif
    </div>
</x-filament::card>
