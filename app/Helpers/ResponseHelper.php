<?php

namespace App\Helpers;

use App\Enums\HttpStatus;
use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    public static function success($data = null, string $message = 'Success', int $status = HttpStatus::OK, array $meta = []): JsonResponse
    {
        $response = [
            'code' => $status,
            'message' => $message,
            'success' => true,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    public static function error(string $message, int $status = HttpStatus::BAD_REQUEST, array $errors = [], $data = null): JsonResponse
    {
        $response = [
            'code' => $status,
            'message' => $message,
            'success' => false,
            'data' => $data,
        ];

        return response()->json($response, $status);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, HttpStatus::UNPROCESSABLE_ENTITY, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, HttpStatus::UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, HttpStatus::FORBIDDEN);
    }

    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, HttpStatus::NOT_FOUND);
    }

    public static function conflict(string $message = 'Conflict', array $errors = []): JsonResponse
    {
        return self::error($message, HttpStatus::CONFLICT, $errors);
    }

    public static function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return self::error($message, HttpStatus::TOO_MANY_REQUESTS);
    }

    public static function internalServerError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, HttpStatus::INTERNAL_SERVER_ERROR);
    }

    public static function serviceUnavailable(string $message = 'Service unavailable'): JsonResponse
    {
        return self::error($message, HttpStatus::SERVICE_UNAVAILABLE);
    }

    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, HttpStatus::CREATED);
    }

    public static function noContent(string $message = 'Operation completed successfully'): JsonResponse
    {
        return self::success(null, $message, HttpStatus::NO_CONTENT);
    }

    public static function badRequest(string $message = 'Bad request'): JsonResponse
    {
        return self::error($message, HttpStatus::BAD_REQUEST);
    }

    public static function paginated($data, array $pagination, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return self::success($data, $message, HttpStatus::OK, [
            'pagination' => $pagination
        ]);
    }

    public static function custom($data, string $message, int $status, array $additional = []): JsonResponse
    {
        $response = [
            'status' => HttpStatus::isSuccess($status) ? 'success' : 'error',
            'code' => $status,
            'message' => $message,
            'data' => $data,
        ];

        foreach ($additional as $key => $value) {
            $response[$key] = $value;
        }

        return response()->json($response, $status);
    }
}
