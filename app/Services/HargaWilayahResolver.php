<?php

namespace App\Services;

use App\Models\tb_harga_wilayah;
use App\Models\tb_limbah;

class HargaWilayahResolver
{
    /**
     * Resolve harga for each limbah id with optional wilayah override.
     *
     * @param  array<int, int|string>  $limbahIds
     * @param  string|null  $kodeWilayah
     * @return array<int, int>
     */
    public static function getFor(array $limbahIds, ?string $kodeWilayah): array
    {
        $ids = array_values(array_unique(array_map('intval', $limbahIds)));
        if (empty($ids)) {
            return [];
        }

        $prices = tb_limbah::whereIn('id_limbah', $ids)
            ->pluck('harga', 'id_limbah')
            ->map(fn ($price) => (int) $price)
            ->toArray();

        if ($kodeWilayah) {
            $overrides = tb_harga_wilayah::where('kode_wilayah', $kodeWilayah)
                ->whereIn('id_limbah', $ids)
                ->pluck('harga', 'id_limbah');

            foreach ($overrides as $id => $price) {
                $prices[(int) $id] = (int) $price;
            }
        }

        return $prices;
    }

    public static function resolve(int $limbahId, ?string $kodeWilayah): int
    {
        $prices = self::getFor([$limbahId], $kodeWilayah);

        return (int) ($prices[$limbahId] ?? 0);
    }
}
