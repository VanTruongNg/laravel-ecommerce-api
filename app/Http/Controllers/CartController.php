<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Cart;
use App\utils\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller {
    public function getCartByUserId(Request $request) {
        try {
            $userId = $request->auth->sub;
            $cart = Cart::where('user_id', $userId)->with('cars')->get();
            if ($cart->isEmpty()) {
                return Response::notFound('Cart not found', 404);
            }
            return Response::success("Cart retrieved successfully", [
                'cart' => $cart->load('cars.brand')
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving the cart', $e->getMessage());
        }
    }

    public function addToCart (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'car_id' => 'required|string|exists:cars,id',
                'quantity' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return Response::badRequest('Validation error', $validator->errors());
            }

            $userId = $request->auth->sub;
            $cart = Cart::where('user_id', $userId)->first();
            if (!$cart) {
                return Response::notFound('Cart not found', 404);
            }

            $car = Car::find($request->car_id);
            if (!$car) {
                return Response::notFound('Car not found');
            }

            $existingCar = $cart->cars()->where('car_id', $request->car_id)->first();
            if ($existingCar) {
                $newQuantity = $existingCar->pivot->quantity + $request->quantity;
                if ($newQuantity > $car->stock) {
                    return Response::badRequest('Not enough stock available', [
                        'available_stock' => $car->stock,
                        'current_quantity' => $existingCar->pivot->quantity,
                        'requested_quantity' => $request->quantity,
                    ]);
                }
                $cart->cars()->updateExistingPivot($request->car_id, [
                    'quantity' => $newQuantity,
                    'updated_at' => now()
                ]);
            } else {
                if ($request->quantity > $car->stock) {
                    return Response::badRequest('Not enough stock available', [
                        'available_stock' => $car->stock,
                        'requested_quantity' => $request->quantity,
                    ]);
                }
                $cart->cars()->attach($request->car_id, [
                    'quantity' => $request->quantity
                ]);
            }

            return Response::success('Car added to cart successfully', [
                'cart' => $cart->load('cars.brand')
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while adding to the cart', $e->getMessage());
        }
    }

    public function removeFromCart(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'car_id' => 'required|string|exists:cars,id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return Response::badRequest('Validation error', $validator->errors());
            }

            $userId = $request->auth->sub;
            $cart = Cart::where('user_id', $userId)->first();
            if (!$cart) {
                return Response::notFound('Cart not found', 404);
            }

            $existingCar = $cart->cars()->where('car_id', $request->car_id)->first();
            if (!$existingCar) {
                return Response::notFound('Car not found in cart', 404);
            }

            $newQuantity = $existingCar->pivot->quantity - $request->quantity;
            if ($newQuantity <= 0) {
                $cart->cars()->detach($request->car_id);
            } else {
                $cart->cars()->updateExistingPivot($request->car_id, [
                    'quantity' => $newQuantity,
                    'updated_at' => now()
                ]);
            }

            return Response::success('Cart updated successfully', [
                'cart' => $cart->load('cars.brand')
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while removing from the cart', $e->getMessage());
        }
    }

    public function clearCart(Request $request) {
        try {
            $userId = $request->auth->sub;
            $cart = Cart::where('user_id', $userId)->first();
            if (!$cart) {
                return Response::notFound('Cart not found', 404);
            }

            $cart->cars()->detach();
            return Response::success('Cart cleared successfully', [
                'cart' => $cart->load('cars.brand')
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while clearing the cart', $e->getMessage());
        }
    }
}