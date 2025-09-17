<?php

namespace App\Traits;
use App\Helpers\ResponseHelper;
use App\Enums\HttpStatus;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
     protected function successResponse($data = null, string $message = 'Success', int $status = HttpStatus::OK, array $meta = []): JsonResponse
    {
        return ResponseHelper::success($data, $message, $status, $meta);
    }

      protected function errorResponse(string $message, int $status = HttpStatus::BAD_REQUEST, array $errors = [], $data = null): JsonResponse
    {
        return ResponseHelper::error($message, $status, $errors, $data);
    }

     protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        $firstError = collect($errors)->flatten()->first();
        return ResponseHelper::error($firstError ?: $message, HttpStatus::UNPROCESSABLE_ENTITY);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return ResponseHelper::unauthorized($message);
    }

    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return ResponseHelper::forbidden($message);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return ResponseHelper::notFound($message);
    }

    protected function conflictResponse(string $message = 'Conflict', array $errors = []): JsonResponse
    {
        return ResponseHelper::conflict($message, $errors);
    }

    protected function createdResponse($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return ResponseHelper::created($data, $message);
    }

    protected function noContentResponse(string $message = 'Operation completed successfully'): JsonResponse
    {
        return ResponseHelper::noContent($message);
    }

    protected function paginatedResponse($data, array $pagination, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return ResponseHelper::paginated($data, $pagination, $message);
    }

    protected function tooManyRequestsResponse(string $message = 'Too many requests'): JsonResponse
    {
        return ResponseHelper::tooManyRequests($message);
    }

    protected function internalServerErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return ResponseHelper::internalServerError($message);
    }

    protected function serviceUnavailableResponse(string $message = 'Service unavailable'): JsonResponse
    {
        return ResponseHelper::serviceUnavailable($message);
    }

    protected function badRequestResponse(string $message = 'Bad request'): JsonResponse
    {
        return ResponseHelper::badRequest($message);
    }
}
