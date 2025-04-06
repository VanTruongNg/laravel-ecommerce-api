<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\UploadService\UploaderService;
use App\utils\Response;
use Illuminate\Http\Request;
use Storage;
use Validator;

class BrandController extends Controller {
    protected $uploaderService;
    public function __construct()
    {
        $this->uploaderService = new UploaderService();
    }
    public function getAllBrands () {
        try {
            $brands = Brand::all();
            return Response::success("Brands retrieved successfully", [
                'brands' => $brands
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving brands', $e->getMessage());
        }
    }

    public function getBrandByID ($id) {
        try {
            $brand = Brand::find($id);
            if (!$brand) {
                return Response::notFound('Brand not found', 404);
            }
            return Response::success("Brand retrieved successfully", [
                'brand' => $brand
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving the brand', $e->getMessage());
        }
    }

    public function createBrand (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'banner' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::badRequest($validator->errors()->first(), 400);
            }

            $file = $request->file('banner');
            $filename = strtolower(str_replace(' ', '_', $request->name)) . '_' . strtolower(str_replace(' ', '_', $request->country)) . '.' . $file->getClientOriginalExtension();

            $uploadResponse = $this->uploaderService->uploadFile($file, $filename);

            $brand = Brand::create([
                'name' => $request->name,
                'country' => $request->country,
                'banner_url' => $uploadResponse['data']['url'],
            ]);

            return Response::success("Brand created successfully", [
                'brand' => $brand
            ], 201);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while creating the brand', $e->getMessage());
        }
    }

    public function updateBrand (Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'country' => 'sometimes|required|string|max:255',
                'banner' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::badRequest($validator->errors()->first(), 400);
            }

            $brand = Brand::find($id);
            if (!$brand) {
                return Response::notFound('Brand not found', 404);
            }

            if ($request->hasFile('banner')) {
                $file = $request->file('banner');
                $filename = strtolower(str_replace(' ', '_', $request->name)) . '_' . strtolower(str_replace(' ', '_', $request->country)) . '.' . $file->getClientOriginalExtension();

                $uploadResponse = $this->uploaderService->uploadFile($file, $filename);
                $brand->banner_url = $uploadResponse['data']['url'];
            }

            $brand->update($request->only(['name', 'country']));

            return Response::success("Brand updated successfully", [
                'brand' => $brand
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while updating the brand', $e->getMessage());
        }
    }

    public function deleteBrand ($id) {
        try {
            $brand = Brand::find($id);
            if (!$brand) {
                return Response::notFound('Brand not found', 404);
            }

            $brand->delete();

            return Response::success("Brand deleted successfully");
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while deleting the brand', $e->getMessage());
        }
    }
}