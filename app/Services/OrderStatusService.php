<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function updateStatus(Order $order, string $newStatus): Order
    {
        return DB::transaction(function () use ($order, $newStatus) {
            $order->loadMissing('items');

            $oldStatus = $order->status;
            if ($oldStatus === $newStatus) {
                return $order->refresh()->load('items.car');
            }

            // لو كان completed ثم تغيّر لغيره: رجّع
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                $this->restoreStock($order);
            }

            // لو صار completed: خصم
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->decreaseStock($order);
            }

            $order->status = $newStatus;
            $order->save();

            return $order->refresh()->load('items.car');
        });
    }

    private function decreaseStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $car = Car::lockForUpdate()->find($item->car_id);
            if (!$car) {
                throw ValidationException::withMessages(['items' => ['Car not found.']]);
            }
            if ($car->stock < $item->qty) {
                throw ValidationException::withMessages([
                    'stock' => ["Not enough stock for car_id={$car->id}"]
                ]);
            }

            $car->stock -= $item->qty;
            if ($car->stock === 0) $car->status = 'sold';
            $car->save();
        }
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $car = Car::lockForUpdate()->find($item->car_id);
            if (!$car) continue;

            $car->stock += $item->qty;
            if ($car->stock > 0) $car->status = 'available';
            $car->save();
        }
    }
}
