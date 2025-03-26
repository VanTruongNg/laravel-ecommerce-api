<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index()
    {
        return Car::all();
    }

    public function store(Request $request)
    {
        $request->merge(['id' => (string) \Illuminate\Support\Str::uuid()]);
        $car = Car::create($request->all());
        return response()->json($car, 201);
    }

    public function show($id)
    {
        return Car::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $car = Car::findOrFail($id);
        $car->update($request->all());
        return response()->json($car, 200);
    }

    public function destroy($id)
    {
        Car::destroy($id);
        return response()->json(null, 204);
    }
}