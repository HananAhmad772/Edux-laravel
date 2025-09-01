<?php

namespace App\Exceptions;

use App\Enums\HttpStatus;
use App\Helpers\ResponseHelper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e): JsonResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof AuthenticationException) {
            return ResponseHelper::unauthorized('Authentication required');
        }

        if ($e instanceof AccessDeniedHttpException) {
            return ResponseHelper::forbidden('Access denied');
        }

        if ($e instanceof ModelNotFoundException) {
            return ResponseHelper::notFound('Resource not found');
        }

        if ($e instanceof NotFoundHttpException) {
            return ResponseHelper::notFound('Endpoint not found');
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return ResponseHelper::error(
                'Method not allowed',
                HttpStatus::METHOD_NOT_ALLOWED
            );
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return ResponseHelper::tooManyRequests('Too many requests');
        }

        if ($e instanceof ValidationException) {
            return ResponseHelper::validationError(
                $e->errors(),
                'Validation failed'
            );
        }

        if ($this->isDatabaseException($e)) {
            return $this->handleDatabaseException($e);
        }

        if ($this->isBusinessLogicException($e)) {
            return $this->handleBusinessLogicException($e);
        }

        if (config('app.debug')) {
            return ResponseHelper::error(
                'Server Error: ' . $e->getMessage(),
                HttpStatus::INTERNAL_SERVER_ERROR,
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }

        return ResponseHelper::internalServerError('An unexpected error occurred');
    }

    protected function isDatabaseException(Throwable $e): bool
    {
        return str_contains($e::class, 'Database') ||
               str_contains($e::class, 'QueryException') ||
               str_contains($e::class, 'Connection') ||
               str_contains($e->getMessage(), 'database') ||
               str_contains($e->getMessage(), 'connection');
    }

    protected function handleDatabaseException(Throwable $e): JsonResponse
    {
        $this->report($e);

        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            return ResponseHelper::conflict('Resource already exists');
        }

        if (str_contains($e->getMessage(), 'foreign key constraint')) {
            return ResponseHelper::conflict('Cannot perform operation due to related data');
        }

        if (str_contains($e->getMessage(), 'Connection refused') ||
            str_contains($e->getMessage(), 'Connection timeout')) {
            return ResponseHelper::serviceUnavailable('Database connection failed');
        }

        return ResponseHelper::internalServerError('Database error occurred');
    }

    protected function isBusinessLogicException(Throwable $e): bool
    {
        $businessExceptions = [
            'InsufficientFundsException',
            'LoanNotAvailableException',
            'UserNotApprovedException',
            'InvalidLoanStatusException',
        ];

        foreach ($businessExceptions as $exception) {
            if (str_contains($e::class, $exception)) {
                return true;
            }
        }

        return false;
    }

    protected function handleBusinessLogicException(Throwable $e): JsonResponse
    {
        $statusMap = [
            'InsufficientFundsException' => HttpStatus::BAD_REQUEST,
            'LoanNotAvailableException' => HttpStatus::CONFLICT,
            'UserNotApprovedException' => HttpStatus::FORBIDDEN,
            'InvalidLoanStatusException' => HttpStatus::CONFLICT,
        ];

        foreach ($statusMap as $exception => $status) {
            if (str_contains($e::class, $exception)) {
                return ResponseHelper::error($e->getMessage(), $status);
            }
        }

        return ResponseHelper::error($e->getMessage(), HttpStatus::BAD_REQUEST);
    }

    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return ResponseHelper::unauthorized('Authentication required');
    }
}
