# Dashboard API Documentation

## Overview
A single API endpoint that returns all dashboard data for the student dashboard page. All metrics are calculated dynamically from daily progress records, not stored directly.

## Database Structure

### daily_progress Table
Stores day-wise progress with topics fetched from AI roadmap.

**Columns:**
- `id` (ULID) - Primary key
- `user_id` (ULID) - Foreign key to users
- `roadmap_id` (ULID, nullable) - Foreign key to student_roadmaps
- `progress_date` (date) - The day this progress was recorded
- `step_name` (string) - e.g., "Week 1–2"
- `topic_index` (tinyint) - 1-6 (topic index in the step)
- `topic_name` (string) - Topic name from roadmap
- `topic_completed` (boolean) - Whether topic was completed
- `xp_earned` (integer) - XP earned on this day
- `time_spent_minutes` (integer) - Time spent learning
- `completed_tasks` (JSON) - Array of completed task IDs/names
- `meta` (JSON) - Additional metadata
- `created_at`, `updated_at` - Timestamps

**Unique Constraint:** One record per user per day (`user_id`, `progress_date`)

## API Endpoint

### GET /api/auth/student/dashboard

**Authentication:** Required (Bearer token)

**Response:**
```json
{
  "status": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "current_week": {
      "week_number": 1,
      "week_name": "Week 1–2",
      "step_title": "Foundations",
      "current_day": 3,
      "total_days": 14
    },
    "xp": 120,
    "streak": 4,
    "last_project_score": {
      "score": 8,
      "max_score": 10,
      "passed": true,
      "date": "2025-01-15"
    },
    "today_topic": {
      "topic": "Introduction to REST APIs",
      "topic_index": 1,
      "step": "Week 1–2",
      "step_title": "Foundations"
    },
    "yesterday_topic": {
      "topic": "Database normalization",
      "topic_index": 6,
      "step": "Week 1–2",
      "step_title": "Foundations"
    },
    "roadmap_progress": {
      "percentage": 25.5,
      "completed_steps": 1,
      "total_steps": 5,
      "completed_topics": 8,
      "total_topics": 30,
      "steps": [
        {
          "week": 1,
          "week_name": "Week 1–2",
          "status": "completed",
          "progress": 100.0
        },
        {
          "week": 2,
          "week_name": "Week 3–4",
          "status": "in-progress",
          "progress": 33.3
        },
        {
          "week": 3,
          "week_name": "Week 5–6",
          "status": "pending",
          "progress": 0.0
        }
      ]
    },
    "ai_recommendation": "You struggled with Database normalization — let's review before continuing!"
  }
}
```

## Metrics Calculation

### Current Week
- Extracted from user progress (`current_step` and `current_topic_index`)
- Calculates current day based on step index and topic index
- Assumes ~2 days per topic

### XP (Experience Points)
- Sum of all `xp_earned` from `daily_progress` table
- XP is earned when topics/tasks are completed

### Streak
- Calculates consecutive days with activity (`time_spent_minutes > 0`)
- Checks backwards from today
- If today has no activity, checks from yesterday

### Last Project Score
- Gets the most recent `StudentQuiz` record
- Returns score, max_score (10), passed status (score >= 7), and date

### Today's & Yesterday's Topics
- Extracted from roadmap JSON based on user progress
- Today's topic: Current topic from `current_step` and `current_topic_index`
- Yesterday's topic: Previous topic in same step, or last topic of previous step

### Roadmap Progress
- Calculates percentage based on completed topics
- Counts distinct completed topics from `daily_progress`
- Determines step status:
  - `completed`: All 6 topics completed (100%)
  - `in-progress`: Some topics completed or current step
  - `pending`: No progress yet

### AI Recommendation
- Generated based on:
  - Recent completion rate (last 3 days)
  - Current streak
  - Yesterday's topic performance
- Provides contextual suggestions

## Recording Daily Progress

Use `DashboardService::recordDailyProgress()` to record daily activity:

```php
$dashboardService = app(\App\Services\DashboardService::class);
$progress = $dashboardService->recordDailyProgress($userId, [
    'roadmap_id' => $roadmapId,
    'step_name' => 'Week 1–2',
    'topic_index' => 1,
    'topic_name' => 'Introduction to REST APIs',
    'topic_completed' => true,
    'xp_earned' => 10,
    'time_spent_minutes' => 30,
    'completed_tasks' => ['task1', 'task2']
]);
```

This will create or update the record for today.

## Frontend Integration

The `StudentDashboard.jsx` component now:
1. Fetches data from `/api/auth/student/dashboard` on mount
2. Displays all metrics dynamically
3. Shows loading state while fetching
4. Handles empty states gracefully

## Migration

Run the migration to create the `daily_progress` table:

```bash
php artisan migrate
```

## Notes

- All metrics are calculated on-the-fly, not stored
- Daily progress is stored day-wise for historical tracking
- XP, streak, and progress are derived from daily records
- Topics are fetched from roadmap JSON, not stored separately
- AI recommendations are generated dynamically based on recent activity

