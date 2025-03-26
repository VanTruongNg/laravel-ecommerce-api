<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Validator;

class CarController extends Controller
{
    public function index()
    {
        return Car::all();
    }

    public function createCar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'make' => 'required|string',
                'model' => 'required|string',
                'year' => 'required|integer',
                'color' => 'required|string',
                'price' => 'required|numeric'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $car = Car::create([
                'make' => $request->make,
                'model' => $request->model,
                'year' => $request->year,
                'color' => $request->color,
                'price' => $request->price
            ]);

            return response()->json($car, 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create car'], 500);
        }
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