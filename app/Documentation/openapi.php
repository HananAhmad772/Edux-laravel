<?php

$serverUrl = rtrim(env('APP_URL', 'http://localhost'), '/') . '/api';

$flatResponseSchema = [
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

$flatErrorResponseSchema = [
    'type' => 'object',
    'properties' => [
        'code' => ['type' => 'integer', 'example' => 422],
        'message' => ['type' => 'string', 'example' => 'The selected email is invalid.'],
        'success' => ['type' => 'boolean', 'example' => false],
        'data' => ['nullable' => true, 'example' => null],
    ],
    'required' => ['code', 'message', 'success', 'data'],
];

return [
    'openapi' => '3.0.3',
    'info' => [
        'title' => 'Edux API',
        'description' => 'Student-focused AI learning backend for registration, learning progress, roadmaps, quizzes, daily challenges, and badges.',
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
    ],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'Sanctum token',
            ],
        ],
        'schemas' => [
            'FlatSuccessResponse' => $flatResponseSchema,
            'FlatErrorResponse' => $flatErrorResponseSchema,
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
                    'icon' => ['type' => 'string', 'example' => '🎉'],
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
                    'icon' => ['type' => 'string', 'example' => '🎉'],
                    'earned_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
        ],
    ],
    'paths' => [
        '/auth/signup' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Register a student account',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'phone'],
                                'properties' => [
                                    'first_name' => ['type' => 'string', 'example' => 'Hannan'],
                                    'last_name' => ['type' => 'string', 'example' => 'Ahmad'],
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                                    'password' => ['type' => 'string', 'example' => 'Password@123'],
                                    'password_confirmation' => ['type' => 'string', 'example' => 'Password@123'],
                                    'phone' => ['type' => 'string', 'example' => '03001234567'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'User registered successfully',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]],
                    ],
                    '422' => [
                        'description' => 'Validation error',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]],
                    ],
                ],
            ],
        ],
        '/auth/login' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Login and receive Sanctum token',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'password'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                                    'password' => ['type' => 'string', 'example' => 'Password@123'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Login successful',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]],
                    ],
                    '401' => [
                        'description' => 'Invalid credentials',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]],
                    ],
                ],
            ],
        ],
        '/auth/forgot/password' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Send password reset OTP',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                                    'phone' => ['type' => 'string', 'example' => '03001234567'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'OTP sent', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '422' => ['description' => 'Validation error', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/verify-otp' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Verify reset OTP',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'otp'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                                    'otp' => ['type' => 'string', 'example' => '123456'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'OTP verified', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '422' => ['description' => 'Validation error', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/reset-password' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Reset password using OTP',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'otp', 'password', 'password_confirmation'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'hanan@example.com'],
                                    'otp' => ['type' => 'string', 'example' => '123456'],
                                    'password' => ['type' => 'string', 'example' => 'Password@123'],
                                    'password_confirmation' => ['type' => 'string', 'example' => 'Password@123'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Password reset', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '422' => ['description' => 'Validation error', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/profile' => [
            'get' => [
                'tags' => ['Auth'],
                'summary' => 'Get the authenticated user profile',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Profile fetched', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '401' => ['description' => 'Unauthorized', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/update-profile' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Update the authenticated student profile',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'first_name' => ['type' => 'string', 'example' => 'Hannan'],
                                    'last_name' => ['type' => 'string', 'example' => 'Ahmad'],
                                    'phone' => ['type' => 'string', 'example' => '03001234567'],
                                    'dob' => ['type' => 'string', 'format' => 'date', 'example' => '2000-01-01'],
                                    'gender' => ['type' => 'string', 'example' => 'male'],
                                    'class_year' => ['type' => 'string', 'example' => '2026'],
                                    'institute' => ['type' => 'string', 'example' => 'Example University'],
                                    'major_subject' => ['type' => 'string', 'example' => 'Computer Science'],
                                    'bio' => ['type' => 'string', 'example' => 'A motivated learner.'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Profile updated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '422' => ['description' => 'Validation error', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/logout' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Logout from current device',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Logged out', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '401' => ['description' => 'Unauthorized', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/logout-all' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Logout from all devices',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Logged out from all devices', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/change-password' => [
            'post' => [
                'tags' => ['Auth'],
                'summary' => 'Change the authenticated user password',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['current_password', 'password', 'password_confirmation'],
                                'properties' => [
                                    'current_password' => ['type' => 'string', 'example' => 'OldPass@123'],
                                    'password' => ['type' => 'string', 'example' => 'NewPass@123'],
                                    'password_confirmation' => ['type' => 'string', 'example' => 'NewPass@123'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Password changed', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '422' => ['description' => 'Validation error', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
        '/auth/student/questions' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Save student onboarding questions',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Questions saved', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/generate-quiz' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Generate an AI quiz for the student',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Quiz generated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/quiz' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Store a student quiz submission',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Quiz stored', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/quizzes' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'Get all student quizzes',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Quizzes returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/generate-roadmap' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Generate a personalized learning roadmap',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Roadmap generated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/roadmaps' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'List student roadmaps',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Roadmaps returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/roadmap/latest' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'Get the latest student roadmap',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Latest roadmap returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/roadmap/current' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'Get the current roadmap view',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Current roadmap returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/roadmap/advance' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Advance roadmap progress',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Progress advanced', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/chatbot' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Chat with the AI mentor',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Chat response returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/chat/history' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'Get chat history',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Chat history returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/progress' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'Get extended student dashboard progress',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Progress returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/daily-challenge' => [
            'get' => [
                'tags' => ['Student'],
                'summary' => 'Generate a daily challenge',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Challenge returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/auth/student/daily-challenge/submit' => [
            'post' => [
                'tags' => ['Student'],
                'summary' => 'Submit a daily challenge answer',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['challenge_id', 'submission'],
                                'properties' => [
                                    'challenge_id' => ['type' => 'string', 'example' => 'ch_123'],
                                    'submission' => ['type' => 'string', 'example' => 'My solution text'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Submission evaluated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/badges' => [
            'get' => [
                'tags' => ['Badges'],
                'summary' => 'List all badges and earned status',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Badges returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/badges/user' => [
            'get' => [
                'tags' => ['Badges'],
                'summary' => 'Get badges earned by the authenticated user',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'User badges returned', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                ],
            ],
        ],
        '/badges/award' => [
            'post' => [
                'tags' => ['Badges'],
                'summary' => 'Award a badge to the authenticated user',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['badge_id'],
                                'properties' => [
                                    'badge_id' => ['type' => 'integer', 'example' => 1],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Badge awarded', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatSuccessResponse']]]],
                    '400' => ['description' => 'Already has badge or invalid request', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                    '404' => ['description' => 'Badge not found', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/FlatErrorResponse']]]],
                ],
            ],
        ],
    ],
];
