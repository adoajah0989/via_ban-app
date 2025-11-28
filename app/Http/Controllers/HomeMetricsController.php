<?php

namespace App\Http\Controllers;

use App\Models\tb_transaksi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class HomeMetricsController extends Controller
{
    public function limbahSummary(): JsonResponse
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $totalPickup = tb_transaksi::query()
            ->whereBetween('tanggal', [$start, $end])
            ->sum('total_pickup');

        return response()->json([
            'total_kg_bulan_ini' => (int) $totalPickup,
        ]);
    }
}

