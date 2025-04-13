<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\utils\Response;
use App\UploadService\uploaderService;

class CarController extends Controller
{
    protected $uploaderService;
    public function __construct(uploaderService $uploaderService)
    {
        $this->uploaderService = $uploaderService;
    }
    public function getAllCars(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 12);
            $offset = ($page - 1) * $limit;

            // Get total count
            $total = Car::count();

            // Get cars for current page
            $cars = Car::with(['brand'])
                ->select([
                    'id',
                    'model',
                    'year',
                    'brand_id',
                    'color',
                    'price',
                    'image_url',
                    'stock',
                    'fuel_type',
                    'availability',
                    'created_at'
                ])
                ->skip($offset)
                ->take($limit)
                ->get();

            // Calculate pagination info
            $lastPage = ceil($total / $limit);

            return Response::success("Cars retrieved successfully", [
                'cars' => $cars,
                'total' => $total,
                'current_page' => (int) $page,
                'last_page' => $lastPage,
                'per_page' => (int) $limit
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving cars', $e->getMessage());
        }
    }

    public function getCarByID($id)
    {
        try {
            $car = Car::with('brand')->findOrFail($id);

            return Response::success("Car retrieved successfully", [
                'car' => $car
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving the car', $e->getMessage());
        }
    }

    public function getNewestCar()
    {
        try {
            $cars = Car::where('year', 2025)
                ->select([
                    'id',
                    'model',
                    'year',
                    'brand_id',
                    'color',
                    'price',
                    'image_url',
                    'stock',
                    'fuel_type',
                    'availability',
                    'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return Response::success("Cars from 2025 retrieved successfully", [
                'cars' => $cars
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving the cars', $e->getMessage());
        }
    }

    public function createCar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string|max:255',
                'year' => 'required|integer|digits:4|between:1886,' . date('Y'),
                'color' => 'required|string|max:50',
                'brand_id' => 'required|exists:brands,id',
                'description' => 'nullable|string|max:100000000000',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'fuel_type' => 'required|in:gasoline,diesel,electric,hybrid',
                'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return Response::badRequest($validator->errors()->first(), 400);
            }

            $file = $request->file('image');
            $filename = strtolower(str_replace(' ', '_', $request->model)) . '.' . $file->getClientOriginalExtension();
            $uploadResponse = $this->uploaderService->uploadFile($file, $filename);

            DB::beginTransaction();

            $car = Car::create([
                'model' => $request->model,
                'year' => $request->year,
                'color' => $request->color,
                'price' => $request->price,
                'brand_id' => $request->brand_id,
                'image_url' => $uploadResponse['data']['url'],
                'stock' => $request->stock,
                'fuel_type' => $request->fuel_type,
                'availability' => $request->availability ?? 'in_stock',
            ]);

            DB::commit();

            return Response::success("Car created successfully", [
                'car' => $car
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return Response::serverError('An error occurred while creating the car', $e->getMessage());
        }
    }

    public function updateCar(Request $request, $id)
    {
        try {
            // Debug request data
            \Log::info('Request all:', $request->all());
            \Log::info('Request files:', $request->allFiles());
            $validator = Validator::make($request->all(), [
                'model' => 'sometimes|required|string|max:255',
                'year' => 'sometimes|required|integer|digits:4|between:1886,' . date('Y'),
                'color' => 'sometimes|required|string|max:50',
                'brand_id' => 'sometimes|required|exists:brands,id',
                'price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'fuel_type' => 'sometimes|required|in:gasoline,diesel,electric,hybrid',
                'image' => 'sometimes|required|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return Response::badRequest($validator->errors()->first(), 400);
            }

            $car = Car::findOrFail($id);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = strtolower(str_replace(' ', '_', $request->model)) . '.' . $file->getClientOriginalExtension();
                $uploadResponse = $this->uploaderService->uploadFile($file, $filename);
                $car->image_url = $uploadResponse['data']['url'];
            }

            $car->update($request->only(['model', 'year', 'color', 'price', 'brand_id', 'stock', 'fuel_type', 'availability']));

            return Response::success("Car updated successfully", [
                'car' => $car
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while updating the car', $e->getMessage());
        }
    }

    public function deleteCar($id)
    {
        try {
            $car = Car::findOrFail($id);
            $car->delete();

            return Response::success("Car deleted successfully");
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while deleting the car', $e->getMessage());
        }
    }
}
