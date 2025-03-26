<?php

namespace App\utils;

class Response
{
    public static function success($message, $data = null, $statusCode = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    // 400 Bad Request - Lỗi validation hoặc request không hợp lệ
    public static function badRequest($message, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 400);
    }

    // 401 Unauthorized - Chưa xác thực hoặc token không hợp lệ
    public static function unauthorized($message = 'Unauthorized', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 401);
    }

    // 403 Forbidden - Không có quyền truy cập
    public static function forbidden($message = 'Forbidden', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 403);
    }

    // 404 Not Found - Không tìm thấy resource
    public static function notFound($message = 'Resource not found', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 404);
    }

    // 422 Unprocessable Entity - Dữ liệu không hợp lệ
    public static function validationError($message = 'Validation failed', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 422);
    }

    // 429 Too Many Requests - Quá nhiều request
    public static function tooManyRequests($message = 'Too many requests', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 429);
    }

    // 500 Internal Server Error - Lỗi server
    public static function serverError($message = 'Server error', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 500);
    }

    // 503 Service Unavailable - Dịch vụ không khả dụng
    public static function serviceUnavailable($message = 'Service unavailable', $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => ['errors' => $errors]
        ], 503);
    }

    // 201 Created - Tạo resource thành công
    public static function created($message, $data = null)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], 201);
    }

    // 204 No Content - Xóa thành công
    public static function noContent($message = 'Resource deleted')
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => null
        ], 204);
    }
}