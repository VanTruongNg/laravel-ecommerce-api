<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderStatus;
use App\utils\Response;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function getAllOrders()
    {
        try {
            $orders = Order::with([
                'user',
                'orderDetails.car.brand'
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            return Response::success("Orders retrieved successfully", [
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving the orders', $e->getMessage());
        }
    }

    public function getOrders(Request $request)
    {
        try {
            $userId = $request->auth->sub;

            $orders = Order::with(['orderDetails.car.brand', 'payment'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return Response::success('Orders retrieved successfully', [
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            return Response::serverError('An error occurred while fetching orders', $e->getMessage());
        }
    }

    public function createOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:cart,direct',
                'car_id' => 'required_if:type,direct|string|exists:cars,id',
                'quantity' => 'required_if:type,direct|integer|min:1',
            ]);

            if ($validator->fails()) {
                return Response::badRequest('Validation error', $validator->errors());
            }

            $userId = $request->auth->sub;

            DB::beginTransaction();
            try {
                $orderDetails = [];
                $totalPrice = 0;

                if ($request->type === 'cart') {
                    $cart = Cart::with('cars')->where('user_id', $userId)->first();
                    if (!$cart || $cart->cars->isEmpty()) {
                        return Response::badRequest('Cart is empty');
                    }

                    foreach ($cart->cars as $car) {
                        if ($car->stock < $car->pivot->quantity) {
                            throw new \Exception("Not enough stock for {$car->model}");
                        }

                        $subTotal = $car->price * $car->pivot->quantity;
                        $totalPrice += $subTotal;
                        $orderDetails[] = [
                            'car_id' => $car->id,
                            'quantity' => $car->pivot->quantity,
                            'price' => $car->price,
                            'subtotal_price' => $subTotal
                        ];

                        $car->decrement('stock', $car->pivot->quantity);
                    }
                } else {
                    $car = Car::find($request->car_id);
                    if (!$car) {
                        return Response::notFound('Car not found');
                    }

                    if ($car->stock < $request->quantity) {
                        return Response::badRequest('Not enough stock available', [
                            'available_stock' => $car->stock,
                            'requested_quantity' => $request->quantity,
                        ]);
                    }

                    $subTotal = $car->price * $request->quantity;
                    $totalPrice += $subTotal;
                    $orderDetails[] = [
                        'car_id' => $car->id,
                        'quantity' => $request->quantity,
                        'price' => $car->price,
                        'subtotal_price' => $subTotal
                    ];

                    $car->decrement('stock', $request->quantity);
                }

                $order = Order::create([
                    'user_id' => $userId,
                    'total_price' => $totalPrice,
                    'status' => OrderStatus::PENDING,
                    'order_time' => now()
                ]);

                foreach ($orderDetails as $detail) {
                    $order->orderDetails()->create($detail);
                }

                $order->payment()->create([
                    'amount' => $totalPrice,
                    'status' => 'pending'
                ]);

                if ($request->type === 'cart') {
                    $cart->cars()->detach();
                }

                DB::commit();
                return Response::success('Order created successfully', [
                    'order' => $order->load(['orderDetails.car', 'payment'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return Response::serverError('An error occurred while creating the order', $e->getMessage());
            }
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while creating the order', $e->getMessage());
        }
    }

    public function getOrderDetails(Request $request, $orderId)
    {
        try {
            $userId = $request->auth->sub;

            $order = Order::with(['orderDetails.car.brand', 'payment'])
                ->where('user_id', $userId)
                ->findOrFail($orderId);

            return Response::success('Order detail retrieved successfully', [
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while retrieving the order details', $e->getMessage());
        }
    }

    public function cancelOrder(Request $request, $orderId)
    {
        try {
            $userId = $request->auth->sub;

            DB::beginTransaction();
            try {
                $order = Order::with('orderDetails.car')
                    ->where('user_id', $userId)
                    ->findOrFail($orderId);

                if ($order->status !== OrderStatus::PENDING) {
                    return Response::badRequest('Cannot cancel this order', [
                        'current_status' => $order->status
                    ]);
                }

                foreach ($order->orderDetails as $detail) {
                    $detail->car->increment('stock', $detail->quantity);
                }

                $order->update(['status' => OrderStatus::CANCELLED]);

                $order->payment()->update([
                    'status' => 'failed'
                ]);

                DB::commit();

                return Response::success('Order cancelled successfully', [
                    'order' => $order->load(['orderDetails.car', 'payment'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return Response::serverError('An error occurred while canceling the order', $e->getMessage());
            }
        } catch (\Exception $e) {
            return Response::serverError('An error occurred while canceling the order', $e->getMessage());
        }
    }
}