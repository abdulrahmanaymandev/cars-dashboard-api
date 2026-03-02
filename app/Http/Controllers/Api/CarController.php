<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use Illuminate\Http\JsonResponse;

class CarController extends Controller
{
    public function index()
    {
        $cars = Car::query()->latest()->paginate(10);
        return CarResource::collection($cars);
    }

    public function store(StoreCarRequest $request): JsonResponse
    {
        $v = $request->validated();

        $car = Car::create([
            'stock_no' => $v['stockNo'],
            'make' => $v['make'],
            'model' => $v['model'],
            'year' => $v['year'],
            'trim' => $v['trim'] ?? null,
            'color' => $v['color'] ?? null,
            'price' => $v['price'],
            'stock' => $v['stock'],
            'image_url' => $v['image'] ?? null,
            'status' => ($v['stock'] > 0) ? 'available' : 'sold',
        ]);

        return response()->json(['success' => true, 'data' => new CarResource($car)], 201);
    }

    public function show(Car $car)
    {
        return new CarResource($car);
    }

    public function update(UpdateCarRequest $request, Car $car): JsonResponse
    {
        $v = $request->validated();

        $car->update([
            'stock_no' => $v['stockNo'] ?? $car->stock_no,
            'make' => $v['make'] ?? $car->make,
            'model' => $v['model'] ?? $car->model,
            'year' => $v['year'] ?? $car->year,
            'trim' => array_key_exists('trim', $v) ? $v['trim'] : $car->trim,
            'color' => array_key_exists('color', $v) ? $v['color'] : $car->color,
            'price' => $v['price'] ?? $car->price,
            'stock' => $v['stock'] ?? $car->stock,
            'image_url' => array_key_exists('image', $v) ? $v['image'] : $car->image_url,
            'status' => (isset($v['stock']) ? ((int)$v['stock'] > 0) : ((int)$car->stock > 0)) ? 'available' : 'sold',
        ]);

        return response()->json(['success' => true, 'data' => new CarResource($car)]);
    }

    public function destroy(Car $car): JsonResponse
    {
        $car->delete();
        return response()->json(['success' => true, 'message' => 'Car deleted']);
    }
}
