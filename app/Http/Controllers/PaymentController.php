<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderStatus;
use App\Models\PaymentStatus;
use App\utils\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private string $clientId;
    private string $apiKey;
    private string $checksumKey;
    private const PAYOS_API_URL = 'https://api-merchant.payos.vn';

    public function __construct()
    {
        $this->clientId = env("PAYOS_CLIENT_ID");
        $this->apiKey = env("PAYOS_API_KEY");
        $this->checksumKey = env("PAYOS_CHECKSUM_KEY");
    }
    
    private function createSignature($data): string 
    {
        $stringToHash = 
            "amount=" . $data['amount'] . 
            "&cancelUrl=" . $data['cancelUrl'] . 
            "&description=" . $data['description'] . 
            "&orderCode=" . $data['orderCode'] . 
            "&returnUrl=" . $data['returnUrl'];

        return hash_hmac('sha256', $stringToHash, $this->checksumKey);
    }

    public function createPaymentLink(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'amount' => 'required|integer|min:1000|max:10000000',
                'order_id' => 'required|exists:orders,id',
            ]);

            if ($validate->fails()) {
                return Response::badRequest($validate->errors()->first(), 400);
            }

            $order = Order::find($request->order_id);
            if (!$order) {
                return Response::badRequest('Đơn hàng không tồn tại', 404);
            }

            // Lưu payment code để tracking
            $orderCode = intval(substr(strval(microtime(true) * 10000), -6));
            $order->payment_code = $orderCode;
            $order->save();

            // Tạo payment record
            Payment::create([
                'order_id' => $order->id,
                'amount' => $request->amount,
                'status' => PaymentStatus::PENDING
            ]);
            
            $data = [
                "orderCode" => $orderCode,
                "amount" => $request->amount,
                "description" => "Payment #" . $orderCode,
                "returnUrl" => env("FRONTEND_RETURN_URL"),
                "cancelUrl" => env("FRONTEND_CANCEL_URL"),
                "expiredAt" => time() + 1800 // 30 phút
            ];

            $requiredData = [
                "orderCode" => $data["orderCode"],
                "amount" => $data["amount"],
                "description" => $data["description"],
                "returnUrl" => $data["returnUrl"],
                "cancelUrl" => $data["cancelUrl"]
            ];
            
            $data['signature'] = $this->createSignature($requiredData);

            Log::info('PayOS Request:', [
                'url' => self::PAYOS_API_URL . '/v2/payment-requests',
                'data' => $data
            ]);

            $response = Http::withHeaders([
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->withoutVerifying()->post(self::PAYOS_API_URL . '/v2/payment-requests', $data);

            $responseData = $response->json();
            Log::info('PayOS Raw Response:', $responseData);

            if ($response->successful()) {
                if (!isset($responseData['code'])) {
                    Log::error('PayOS Missing Response Code', $responseData);
                    return Response::badRequest('Lỗi định dạng phản hồi từ PayOS', 500);
                }

                if ($responseData['code'] !== '00') {
                    Log::error('PayOS Error Response:', [
                        'code' => $responseData['code'],
                        'message' => $responseData['desc'] ?? 'Unknown error'
                    ]);
                    return Response::badRequest($responseData['desc'] ?? 'Lỗi từ PayOS', 400);
                }

                if (!isset($responseData['data']['checkoutUrl'])) {
                    Log::error('PayOS Missing Checkout URL:', $responseData);
                    return Response::badRequest('Thiếu thông tin URL thanh toán từ PayOS', 500);
                }
                
                return Response::success($responseData['data']['checkoutUrl'], 200);
            }

            Log::error('PayOS Request Failed:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return Response::badRequest("Không thể kết nối đến PayOS", 500);
        } catch (\Exception $err) {
            Log::error('Payment Error:', [
                'error' => $err->getMessage(),
                'trace' => $err->getTraceAsString()
            ]);
            return Response::serverError('Có lỗi xảy ra khi xử lý thanh toán', 500);
        }
    }

    public function checkPaymentStatus(Request $request)
    {
        try {
            $orderCode = $request->orderCode;
            
            $response = Http::withHeaders([
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->withoutVerifying()
              ->get(self::PAYOS_API_URL . '/v2/payment-requests/' . $orderCode);

            $responseData = $response->json();

            if ($response->successful()) {
                if (!isset($responseData['code'])) {
                    return Response::badRequest('Lỗi định dạng phản hồi từ PayOS', 500);
                }

                if ($responseData['code'] !== '00') {
                    return Response::badRequest($responseData['desc'] ?? 'Lỗi từ PayOS', 400);
                }

                // Update order and payment status if payment successful
                if ($responseData['data']['status'] === 'PAID') {
                    $order = Order::where('payment_code', $orderCode)->first();
                    if ($order) {
                        $order->status = OrderStatus::COMPLETED;
                        $order->save();

                        // Update payment
                        $payment = Payment::where('order_id', $order->id)->first();
                        if ($payment) {
                            $payment->status = PaymentStatus::COMPLETED;
                            $payment->paid_at = now();
                            $payment->save();
                        }
                    }
                }

                return Response::success([
                    'status' => $responseData['data']['status'],
                    'message' => $responseData['data']['status'] === 'PAID' 
                        ? 'Thanh toán thành công'
                        : 'Chờ thanh toán'
                ]);
            }

            Log::error('PayOS Check Status Failed:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return Response::badRequest('Không thể kiểm tra trạng thái thanh toán', 500);
        } catch (\Exception $err) {
            return Response::serverError('Có lỗi xảy ra khi kiểm tra thanh toán', 500);
        }
    }
}