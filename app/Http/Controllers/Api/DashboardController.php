<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Order;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalCars     = Car::count();
        $availableCars = Car::where('status', 'available')->count();
        $totalOrders   = Order::count();
        $revenue       = (float) Order::where('status', 'completed')->sum('total');

        // Build as an array of {status, count} objects so the frontend can iterate
        $statuses     = ['pending', 'completed', 'cancelled'];
        $rawCounts    = Order::selectRaw('status, COUNT(*) as cnt')
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $ordersByStatus = array_map(fn($s) => [
            'status' => $s,
            'count'  => (int)($rawCounts[$s] ?? 0),
        ], $statuses);

        return response()->json([
            'success' => true,
            'data'    => [
                'totalCars'      => $totalCars,
                'availableCars'  => $availableCars,
                'totalOrders'    => $totalOrders,
                'revenue'        => $revenue,
                'ordersByStatus' => $ordersByStatus,
            ],
        ]);
    }
}
