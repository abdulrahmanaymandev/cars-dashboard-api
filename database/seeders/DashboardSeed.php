<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DashboardSeed extends Seeder
{
    public function run(): void
    {
        $data = $this->loadDataJs();

        DB::transaction(function () use ($data) {
            // Users
            foreach (($data['usersData'] ?? []) as $u) {
                User::updateOrCreate(
                    ['email' => $u['email']],
                    [
                        'name' => $u['name'],
                        'password' => Hash::make('123456'),
                        'role' => strtolower($u['role']),
                        'status' => $u['status'] ?? 'active',
                    ]
                );
            }

            // Cars
            foreach (($data['carsData'] ?? []) as $c) {
                Car::updateOrCreate(
                    ['stock_no' => $c['stockNo']],
                    [
                        'make' => $c['make'],
                        'model' => $c['model'],
                        'year' => (int) $c['year'],
                        'trim' => $c['trim'] ?? null,
                        'color' => $c['color'] ?? null,
                        'price' => (float) $c['price'],
                        'stock' => (int) $c['stock'],
                        'image_url' => $c['image'] ?? null,
                        'status' => ((int)$c['stock'] > 0) ? 'available' : 'sold',
                    ]
                );
            }

            // Orders
            $svc = app(OrderStatusService::class);

            foreach (($data['ordersData'] ?? []) as $o) {
                $order = Order::updateOrCreate(
                    ['order_no' => $o['id']],
                    [
                        'customer_name' => $o['customerName'],
                        'email' => $o['email'] ?? null,
                        'phone' => $o['phone'] ?? null,
                        'order_date' => $o['date'],
                        'status' => 'pending',
                        'total' => 0,
                    ]
                );

                $order->items()->delete();

                $total = 0;
                foreach (($o['items'] ?? []) as $it) {
                    // ✅ ربط مضمون بالـ stockNo
                    $car = Car::where('stock_no', $it['stockNo'])->first();

                    // إذا ملفك الحالي يستخدم carId بدل stockNo:
                    // غيّر في data.js items إلى: { stockNo: "STK-0001", qty: 2, price: 68500 }
                    if (!$car) {
                        throw new \RuntimeException("Car not found for stockNo in order {$o['id']}");
                    }

                    $qty = (int) $it['qty'];
                    $unitPrice = (float) ($it['price'] ?? $car->price);
                    $lineTotal = $unitPrice * $qty;
                    $total += $lineTotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'car_id' => $car->id,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);
                }

                $order->total = $total;
                $order->save();

                $svc->updateStatus($order, $o['status'] ?? 'pending');
            }
        });
    }

    private function loadDataJs(): array
    {
        $file = database_path('seed-data/data.js');
        if (!file_exists($file)) {
            throw new \RuntimeException("data.js not found: {$file}");
        }

        $filePath = str_replace('\\', '\\\\', $file);

        // Windows: cmd /c
        $cmd = 'cmd /c node -e "console.log(JSON.stringify(require(\'' . $filePath . '\')))"';
        $json = shell_exec($cmd);

        if (!$json) {
            throw new \RuntimeException("Node failed. Ensure Node is installed + data.js is module.exports. Command: {$cmd}");
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid data returned from data.js");
        }
        return $data;
    }
}
