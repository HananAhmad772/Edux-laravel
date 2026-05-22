<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OpenApiGeneratorService
{
    public function generate(): array
    {
        $serverUrl = rtrim(env('APP_URL', 'http://localhost'), '/') . '/api';

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Edux API',
                'description' => 'Student-first AI learning backend with authentication, progress tracking, quizzes, roadmaps, daily challenges, and badges.',
                'version' => '1.0.0',
            ],
            'servers' => [
                [
                    'url' => $serverUrl,
                    'description' => 'Local API server',
                ],
            ],
            'tags' => [
                ['name' => 'Auth', 'description' => 'Authentication and account management'],
                ['name' => 'Student', 'description' => 'Student-only AI learning features'],
                ['name' => 'Badges', 'description' => 'Badge listing and awarding'],
                ['name' => 'Admin', 'description' => 'Administrative user management'],
                ['name' => 'Other', 'description' => 'Uncategorized API endpoints'],
            ],
            'components' => $this->components(),
            'paths' => $this->buildPaths(),
        ];
    }

    private function components(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Sanctum token',
                ],
            ],
            'schemas' => [
                'FlatSuccessResponse' => $this->flatSuccessSchema(),
                'FlatErrorResponse' => $this->flatErrorSchema(),
                'User' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'example' => '01ks0c1s4e55m6kjkgs9pbm17j'],
                        'first_name' => ['type' => 'string', 'example' => 'Hannan'],
                        'last_name' => ['type' => 'string', 'example' => 'Ahmad'],
                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                        'phone' => ['type' => 'string', 'nullable' => true, 'example' => '03001234567'],
                        'status' => ['type' => 'string', 'example' => 'approved'],
                        'days_since_registration' => ['type' => 'integer', 'example' => 12],
                        'student_profile' => ['type' => 'object', 'nullable' => true],
                    ],
                ],
                'AuthTokenResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'user' => ['$ref' => '#/components/schemas/User'],
                        'token' => ['type' => 'string', 'example' => '1|sanctum-generated-token'],
                    ],
                ],
                'Badge' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'name' => ['type' => 'string', 'example' => 'First Login'],
                        'description' => ['type' => 'string', 'example' => 'Completed registration'],
                        'icon' => ['type' => 'string', 'example' => 'tada'],
                        'earned' => ['type' => 'boolean', 'example' => true],
                        'earned_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    ],
                ],
                'UserBadge' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'name' => ['type' => 'string', 'example' => 'First Login'],
                        'description' => ['type' => 'string', 'example' => 'Completed registration'],
                        'icon' => ['type' => 'string', 'example' => 'tada'],
                        'earned_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    ],
                ],
            ],
        ];
    }

    private function flatSuccessSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'integer', 'example' => 200],
                'message' => ['type' => 'string', 'example' => 'Success'],
                'success' => ['type' => 'boolean', 'example' => true],
                'data' => [
                    'nullable' => true,
                    'description' => 'Response payload, shape varies by endpoint.',
                ],
            ],
            'required' => ['code', 'message', 'success', 'data'],
        ];
    }

    private function flatErrorSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'integer', 'example' => 422],
                'message' => ['type' => 'string', 'example' => 'The selected email is invalid.'],
                'success' => ['type' => 'boolean', 'example' => false],
                'data' => ['nullable' => true, 'example' => null],
            ],
            'required' => ['code', 'message', 'success', 'data'],
        ];
    }

    private function buildPaths(): array
    {
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (!Str::startsWith($uri, 'api/')) {
                continue;
            }

            $path = '/' . ltrim(Str::after($uri, 'api/'), '/');
            $methods = array_values(array_filter($route->methods(), static function (string $method): bool {
                return !in_array($method, ['HEAD', 'OPTIONS'], true);
            }));

            if (empty($methods)) {
                continue;
            }

            foreach ($methods as $method) {
                $paths[$path][strtolower($method)] = $this->buildOperation($route, $path, $method);
            }
        }

        ksort($paths);

        return $paths;
    }

    private function buildOperation($route, string $path, string $method): array
    {
        $middleware = $route->middleware();
        $operation = [
            'tags' => [$this->resolveTag($path)],
            'summary' => $this->buildSummary($route, $path, $method),
            'responses' => $this->buildResponses($path, $method, $middleware),
        ];

        if ($this->requiresBearerAuth($middleware)) {
            $operation['security'] = [['bearerAuth' => []]];
        }

        if ($requestBody = $this->buildRequestBody($path, $method)) {
            $operation['requestBody'] = $requestBody;
        }

        return $operation;
    }

    private function resolveTag(string $path): string
    {
        $segment = trim(explode('/', ltrim($path, '/'))[0] ?? '', '{}');

        return match ($segment) {
            'auth' => 'Auth',
            'student' => 'Student',
            'badges' => 'Badges',
            'admin' => 'Admin',
            default => 'Other',
        };
    }

    private function buildSummary($route, string $path, string $method): string
    {
        $actionMethod = $route->getActionMethod();

        if (is_string($actionMethod) && $actionMethod !== '') {
            return trim(Str::headline($actionMethod));
        }

        return strtoupper($method) . ' ' . $path;
    }

    private function requiresBearerAuth(array $middleware): bool
    {
        return in_array('auth:sanctum', $middleware, true) || in_array('admin', $middleware, true);
    }

    private function buildResponses(string $path, string $method, array $middleware): array
    {
        $responses = [];

        if ($method === 'POST' && (Str::contains($path, ['signup', 'quiz', 'award', 'submit', 'createUser']))) {
            $responses['201'] = [
                'description' => 'Created or submitted successfully',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]],
            ];
        } else {
            $responses['200'] = [
                'description' => 'Success',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]],
            ];
        }

        if (in_array('auth:sanctum', $middleware, true)) {
            $responses['401'] = [
                'description' => 'Unauthorized',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]],
            ];
        }

        if (in_array('admin', $middleware, true)) {
            $responses['403'] = [
                'description' => 'Forbidden',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]],
            ];
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $responses['422'] = [
                'description' => 'Validation error',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]],
            ];
        }

        return $responses;
    }

    private function buildRequestBody(string $path, string $method): ?array
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return null;
        }

        $schema = ['type' => 'object', 'properties' => []];

        if ($path === '/auth/signup') {
            $schema['required'] = ['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'phone'];
            $schema['properties'] = [
                'first_name' => ['type' => 'string', 'example' => 'Hannan'],
                'last_name' => ['type' => 'string', 'example' => 'Ahmad'],
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                'password' => ['type' => 'string', 'example' => 'Password@123'],
                'password_confirmation' => ['type' => 'string', 'example' => 'Password@123'],
                'phone' => ['type' => 'string', 'example' => '03001234567'],
            ];
        } elseif ($path === '/auth/login') {
            $schema['required'] = ['email', 'password'];
            $schema['properties'] = [
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                'password' => ['type' => 'string', 'example' => 'Password@123'],
            ];
        } elseif ($path === '/auth/forgot/password') {
            $schema['properties'] = [
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                'phone' => ['type' => 'string', 'example' => '03001234567'],
            ];
        } elseif ($path === '/auth/verify-otp' || $path === '/auth/reset-password') {
            $schema['required'] = ['email', 'otp'];
            $schema['properties'] = [
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                'otp' => ['type' => 'string', 'example' => '123456'],
            ];

            if ($path === '/auth/reset-password') {
                $schema['required'][] = 'password';
                $schema['required'][] = 'password_confirmation';
                $schema['properties']['password'] = ['type' => 'string', 'example' => 'Password@123'];
                $schema['properties']['password_confirmation'] = ['type' => 'string', 'example' => 'Password@123'];
            }
        } elseif ($path === '/student/questions') {
            $schema['required'] = ['major_subject', 'current_position', 'specialization_field', 'preferred_technologies', 'current_skill_level', 'main_goal', 'time_per_week'];
        } elseif ($path === '/student/quiz') {
            $schema['required'] = ['questions', 'answers', 'score'];
        } elseif ($path === '/student/daily-challenge/submit') {
            $schema['required'] = ['challenge_id', 'submission'];
        } elseif ($path === '/badges/award') {
            $schema['required'] = ['badge_id'];
        } elseif ($path === '/admin/update-status/{id}') {
            $schema['required'] = ['status'];
        } else {
            return null;
        }

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => $schema,
                ],
            ],
        ];
    }
}