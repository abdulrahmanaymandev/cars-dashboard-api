<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Car;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::query()->with('items.car')->latest('order_date')->paginate(50);
        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $v = $request->validated();

        $order = DB::transaction(function () use ($v) {
            $order = Order::create([
                'order_no'      => $v['id'],
                'customer_name' => $v['customerName'],
                'email'         => $v['email'] ?? null,
                'phone'         => $v['phone'] ?? null,
                'order_date'    => $v['date'],
                'status'        => 'pending',
                'total'         => 0,
            ]);

            $total = 0;

            // Merge duplicate car rows (same stockNo) by summing their qty.
            // This prevents a unique(order_id, car_id) constraint violation
            // when the user adds the same car on multiple form rows.
            $merged = [];
            foreach ($v['items'] as $it) {
                $key = $it['stockNo'];
                if (!isset($merged[$key])) {
                    $merged[$key] = [
                        'stockNo' => $key,
                        'qty'     => 0,
                        'price'   => $it['price'] ?? null,
                    ];
                }
                $merged[$key]['qty'] += (int) $it['qty'];
            }

            foreach ($merged as $it) {
                $car = Car::where('stock_no', $it['stockNo'])->firstOrFail();

                $qty       = (int)   $it['qty'];
                $unitPrice = isset($it['price']) ? (float) $it['price'] : (float) $car->price;
                $lineTotal = $unitPrice * $qty;
                $total    += $lineTotal;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'car_id'     => $car->id,
                    'qty'        => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->total = $total;
            $order->save();

            return $order;
        });

        // Apply the requested status after the transaction
        app(OrderStatusService::class)->updateStatus($order, $v['status']);

        $order->load('items.car');

        return response()->json(['success' => true, 'data' => new OrderResource($order)], 201);
    }

    public function show(Order $order)
    {
        $order->load('items.car');
        return new OrderResource($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $status = $request->validated()['status'];
        $order  = app(OrderStatusService::class)->updateStatus($order, $status);

        return response()->json(['success' => true, 'data' => new OrderResource($order)]);
    }

    public function destroy(Order $order): JsonResponse
    {
        if ($order->status === 'completed') {
            app(OrderStatusService::class)->updateStatus($order, 'cancelled');
        }

        $order->delete();
        return response()->json(['success' => true, 'message' => 'Order deleted']);
    }
}
