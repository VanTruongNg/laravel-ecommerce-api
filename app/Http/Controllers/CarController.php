<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\utils\Response;
use App\Services\ImageService;
use App\UploadService\UploaderService;

class CarController extends Controller
{
    protected $imageService;
    
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }


    
    public function getAllCars(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(100, (int) $request->query('limit', 25)));

        $cars = Car::query()
            ->paginate($limit, ['*'], 'page', $page);

        return Response::success(
            'Cars retrieved successfully',
            [
                'cars' => $cars->items(),
                'pagination' => [
                    'current_page' => $cars->currentPage(),
                    'last_page' => $cars->lastPage(),
                    'per_page' => $cars->perPage(),
                    'total' => $cars->total()
                ]
            ]
        );
    }

    public function createCar(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'make' => 'required|string',
                'model' => 'required|string',
                'registration' => 'required|string|unique:cars,registration',
                'engine_size' => 'required|string',
                'price' => 'required|numeric',
                'image_url' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ])->stopOnFirstFailure();

            $imageUrl = null;

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $uploaderService = new UploaderService();
            $filename = $request->registration . '.' . $request->file('image')->getClientOriginalExtension();
            
            $uploadResult = $uploaderService->uploadFile(
                $request->file('image'),
                $filename
            );

            $uploadData = $uploadResult->getData();

            if ($uploadData->status === 'error') {
                DB::rollBack();
                return Response::serverError(
                    'Tải lên ảnh thất bại',
                    $uploadData->data->errors ?? 'Lỗi không xác định'
                );
            }

            $car = Car::create([
                'make' => $request->make,
                'model' => $request->model,
                'registration' => $request->registration,
                'engine_size' => $request->engine_size,
                'price' => $request->price,
                'image_url' => $imageUrl,
            ]);

            DB::commit();

            return Response::created(
                'Car created successfully',
                ['car' => $car]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::serverError(
                'Failed to create car',
                $e->getMessage()
            );
        }
    }

    public function getCarByID($id)
    {
        try {
            $car = Car::findOrFail($id);
            return Response::success(
                'Car retrieved successfully',
                ['car' => $car]
            );
        } catch (\Exception $e) {
            return Response::notFound(
                'Car not found',
                $e->getMessage()
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'make' => 'string',
                'model' => 'string',
                'registration' => 'string|unique:cars,registration,' . $id,
                'engine_size' => 'string',
                'price' => 'numeric',
                'image_url' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $car = Car::findOrFail($id);

            $car->update($request->all());
            return Response::success(
                'Car updated successfully',
                ['car' => $car]
            );
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return Response::notFound(
                    'Car not found',
                    $e->getMessage()
                );
            }
            return Response::serverError(
                'Failed to update car',
                $e->getMessage()
            );
        }
    }

    public function destroy($id)
    {
        try {
            $car = Car::findOrFail($id);
            $car->delete();
            return Response::noContent();
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return Response::notFound(
                    'Car not found',
                    $e->getMessage()
                );
            }
            return Response::serverError(
                'Failed to delete car',
                $e->getMessage()
            );
        }
    }
}
