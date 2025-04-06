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
            $limit = $request->input('limit', 12);
            $cars = Car::with('brand')->paginate($limit);

            return Response::success("Cars retrieved successfully", [
                'cars' => $cars->items(),
                'total' => $cars->total(),
                'current_page' => $cars->currentPage(),
                'last_page' => $cars->lastPage(),
                'per_page' => $cars->perPage(),
                'next_page_url' => $cars->nextPageUrl(),
                'prev_page_url' => $cars->previousPageUrl(),
                'first_page_url' => $cars->url(1),
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving cars', $e->getMessage());
        }
    }

    public function getNewestCar()
    {
            $cars = Car::where('year', 2025)
                ->orderBy('created_at', 'desc')
                ->get();

            return Response::success("Cars from 2025 retrieved successfully", [
                'cars' => $cars
            ]);
    }

    public function createCar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string|max:255',
                'year' => 'required|integer|digits:4|between:1886,' . date('Y'),
                'color' => 'required|string|max:50',
                'brand_id' => 'required|exists:brands,id',
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

    public function update(Request $request, $id)
    {

    }
}
