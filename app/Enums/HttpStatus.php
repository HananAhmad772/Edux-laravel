<?php

namespace App\Enums;

class HttpStatus
{
    public const OK = 200;
    public const CREATED = 201;
    public const ACCEPTED = 202;
    public const NO_CONTENT = 204;

    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const NOT_ACCEPTABLE = 406;
    public const CONFLICT = 409;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS = 429;

    public const INTERNAL_SERVER_ERROR = 500;
    public const NOT_IMPLEMENTED = 501;
    public const BAD_GATEWAY = 502;
    public const SERVICE_UNAVAILABLE = 503;
    public const GATEWAY_TIMEOUT = 504;

    public static function getInfo(int $code): array
    {
        $statusInfo = [
            self::OK => [
                'description' => 'OK: The request was successful.',
                'use_case' => 'Use for successful GET requests or operations that return data successfully.'
            ],
            self::CREATED => [
                'description' => 'Created: The resource was successfully created.',
                'use_case' => 'Use for successful POST requests that create a new resource (user, loan, etc.).'
            ],
            self::ACCEPTED => [
                'description' => 'Accepted: The request has been accepted for processing, but processing is not complete.',
                'use_case' => 'Use for asynchronous operations like loan processing, email sending, or background tasks.'
            ],
            self::NO_CONTENT => [
                'description' => 'No Content: The request was successful, but there is no content to return.',
                'use_case' => 'Use for successful DELETE requests or PUT/PATCH requests that don\'t return data.'
            ],
            self::BAD_REQUEST => [
                'description' => 'Bad Request: The server could not understand the request due to invalid syntax.',
                'use_case' => 'Use for validation errors or invalid request parameters.'
            ],
            self::UNAUTHORIZED => [
                'description' => 'Unauthorized: The client must authenticate itself to get the requested response.',
                'use_case' => 'Use when authentication is required but not provided, expired, or invalid tokens.'
            ],
            self::FORBIDDEN => [
                'description' => 'Forbidden: The client does not have access rights to the content.',
                'use_case' => 'Use when user is authenticated but lacks permission (e.g., borrower trying to access lender-only features).'
            ],
            self::NOT_FOUND => [
                'description' => 'Not Found: The server could not find the requested resource.',
                'use_case' => 'Use when a user, loan, loan request, or any resource doesn\'t exist in the database.'
            ],
            self::METHOD_NOT_ALLOWED => [
                'description' => 'Method Not Allowed: The request method is not supported for the requested resource.',
                'use_case' => 'Use when a method (e.g., POST) is not allowed for a specific endpoint.'
            ],
            self::NOT_ACCEPTABLE => [
                'description' => 'Not Acceptable: The server cannot produce a response matching the Accept headers.',
                'use_case' => 'Use when the client requests a format (e.g., XML) that the API doesn\'t support.'
            ],
            self::CONFLICT => [
                'description' => 'Conflict: The request conflicts with the current state of the server.',
                'use_case' => 'Use for business logic conflicts (e.g., duplicate loan requests, SSN already exists).'
            ],
            self::UNPROCESSABLE_ENTITY => [
                'description' => 'Unprocessable Entity: The request was well-formed but contains semantic errors.',
                'use_case' => 'Use for validation errors that are syntactically correct but logically invalid.'
            ],
            self::TOO_MANY_REQUESTS => [
                'description' => 'Too Many Requests: The user has sent too many requests in a given time frame.',
                'use_case' => 'Use for rate limiting, preventing spam loan requests or excessive API calls.'
            ],
            self::INTERNAL_SERVER_ERROR => [
                'description' => 'Internal Server Error: The server encountered a situation it doesn\'t know how to handle.',
                'use_case' => 'Use for unexpected server errors, unhandled exceptions, or database connection failures.'
            ],
            self::NOT_IMPLEMENTED => [
                'description' => 'Not Implemented: The server does not support the functionality required to fulfill the request.',
                'use_case' => 'Use for features that are planned but not yet implemented in the API.'
            ],
            self::BAD_GATEWAY => [
                'description' => 'Bad Gateway: The server received an invalid response from the upstream server.',
                'use_case' => 'Use when external services (payment gateways, credit check APIs) return invalid responses.'
            ],
            self::SERVICE_UNAVAILABLE => [
                'description' => 'Service Unavailable: The server is not ready to handle the request.',
                'use_case' => 'Use when the server is down for maintenance, overloaded, or temporarily unavailable.'
            ],
            self::GATEWAY_TIMEOUT => [
                'description' => 'Gateway Timeout: The server did not receive a timely response from the upstream server.',
                'use_case' => 'Use when external services (payment processing, credit checks) timeout.'
            ],
        ];

        return $statusInfo[$code] ?? [
            'description' => 'Unknown Status Code',
            'use_case' => 'This status code is not documented.'
        ];
    }

    public static function getDescription(int $code): string
    {
        $info = self::getInfo($code);
        return $info['description'];
    }

    public static function getUseCase(int $code): string
    {
        $info = self::getInfo($code);
        return $info['use_case'];
    }

    public static function isSuccess(int $code): bool
    {
        return $code >= 200 && $code < 300;
    }

    public static function isClientError(int $code): bool
    {
        return $code >= 400 && $code < 500;
    }

    public static function isServerError(int $code): bool
    {
        return $code >= 500 && $code < 600;
    }

    public static function getAllCodes(): array
    {
        return [
            'success' => [
                self::OK => self::getInfo(self::OK),
                self::CREATED => self::getInfo(self::CREATED),
                self::ACCEPTED => self::getInfo(self::ACCEPTED),
                self::NO_CONTENT => self::getInfo(self::NO_CONTENT),
            ],
            'client_errors' => [
                self::BAD_REQUEST => self::getInfo(self::BAD_REQUEST),
                self::UNAUTHORIZED => self::getInfo(self::UNAUTHORIZED),
                self::FORBIDDEN => self::getInfo(self::FORBIDDEN),
                self::NOT_FOUND => self::getInfo(self::NOT_FOUND),
                self::METHOD_NOT_ALLOWED => self::getInfo(self::METHOD_NOT_ALLOWED),
                self::NOT_ACCEPTABLE => self::getInfo(self::NOT_ACCEPTABLE),
                self::CONFLICT => self::getInfo(self::CONFLICT),
                self::UNPROCESSABLE_ENTITY => self::getInfo(self::UNPROCESSABLE_ENTITY),
                self::TOO_MANY_REQUESTS => self::getInfo(self::TOO_MANY_REQUESTS),
            ],
            'server_errors' => [
                self::INTERNAL_SERVER_ERROR => self::getInfo(self::INTERNAL_SERVER_ERROR),
                self::NOT_IMPLEMENTED => self::getInfo(self::NOT_IMPLEMENTED),
                self::BAD_GATEWAY => self::getInfo(self::BAD_GATEWAY),
                self::SERVICE_UNAVAILABLE => self::getInfo(self::SERVICE_UNAVAILABLE),
                self::GATEWAY_TIMEOUT => self::getInfo(self::GATEWAY_TIMEOUT),
            ],
        ];
    }
}
