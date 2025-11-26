# API Migration Plan

## Overview
This document outlines the API changes introduced in this implementation and provides a migration plan for existing clients.

## New API Endpoints

### 1. Get Current Roadmap with Topics
```
GET /auth/student/roadmap/current
```

**Response:**
```json
{
  "status": true,
  "message": "Current roadmap with topics retrieved successfully",
  "data": {
    "roadmap": { /* original roadmap object */ },
    "parsed_roadmap": { /* structured roadmap data */ },
    "topics": {
      "today": { "step": "Week 1-2", "topic_index": 1, "topic": "HTML basics" },
      "yesterday": null,
      "tomorrow": { "step": "Week 1-2", "topic_index": 2, "topic": "CSS fundamentals" }
    },
    "user_progress": { /* user progress object */ }
  },
  "code": 200
}
```

### 2. Advance User Progress
```
POST /auth/student/roadmap/advance
```

**Response:**
```json
{
  "status": true,
  "message": "User progress advanced successfully",
  "data": { /* updated user progress object */ },
  "code": 200
}
```

## Backward Compatibility

All existing API endpoints remain unchanged:
- `POST /auth/student/generate-roadmap`
- `GET /auth/student/roadmap/latest`
- `POST /auth/student/chatbot`

The chatbot endpoint now includes topic restrictions but maintains the same request/response format.

## Migration Steps

1. **Database Migrations**
   - Run `php artisan migrate` to create new tables:
     - `user_progress`
     - `messages`

2. **Service Updates**
   - No action required - services are automatically registered

3. **Frontend Updates**
   - Update AIMentorPage to use new endpoints
   - Implement dynamic code playground
   - Add topic navigation UI

## Rollback Plan

If issues are encountered:

1. Revert database migrations:
   ```bash
   php artisan migrate:rollback
   ```

2. Restore previous service files:
   - `app/Services/AIRoadmapService.php`
   - `app/Services/ProfileService.php`
   - `app/Http/Controllers/Api/AuthController.php`

3. Remove new files:
   - `app/Services/AIChatbotMediatorService.php`
   - `app/Models/UserProgress.php`
   - `app/Models/Message.php`
   - `database/migrations/2025_11_23_000000_create_user_progress_table.php`
   - `database/migrations/2025_11_23_000001_create_messages_table.php`

## Testing

Before deployment:
1. Run all existing tests to ensure no regressions
2. Run new tests for updated functionality
3. Test API endpoints with sample data
4. Verify frontend integration

## Deployment

1. Deploy backend changes
2. Run database migrations
3. Deploy frontend changes
4. Monitor for issues