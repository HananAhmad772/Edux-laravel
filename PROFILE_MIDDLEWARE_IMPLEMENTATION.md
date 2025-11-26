# Profile Middleware Implementation

## Overview
Middleware to ensure students complete their profile setup before accessing dashboard and other student features. Prevents null data issues by requiring profile completion.

## Implementation

### 1. Middleware: `CheckStudentProfile`
**File**: `app/Http/Middleware/CheckStudentProfile.php`

**Functionality**:
- Checks if authenticated user is a student
- Verifies student profile exists in `student_profiles` table
- Validates required fields: `major_subject`, `current_skill_level`, `main_goal`
- Returns 403 with `profile_incomplete: true` flag for API requests
- Redirects to `/student/profile-setup` for web requests
- Allows `/api/auth/student/questions` endpoint (profile completion) to bypass check

**Response Format**:
```json
{
  "status": false,
  "message": "Please complete your profile setup first",
  "code": 403,
  "profile_incomplete": true,
  "redirect_to": "/student/profile-setup"
}
```

### 2. Route Protection
**File**: `routes/api.php`

**Protected Routes** (require profile completion):
- `/api/auth/student/dashboard`
- `/api/auth/student/generate-roadmap`
- `/api/auth/student/roadmaps`
- `/api/auth/student/roadmap/*`
- `/api/auth/student/chatbot`
- `/api/auth/student/chat/history`
- `/api/auth/student/quizzes`
- `/api/auth/student/generate-quiz`
- `/api/auth/student/quiz`

**Unprotected Route** (allows profile completion):
- `/api/auth/student/questions` - Profile completion endpoint

### 3. Profile Setup Page
**File**: `src/pages/Dashboard/ProfileSetupPage.jsx`

**Features**:
- Displays welcome message
- Shows wizard modal (cannot be closed)
- Redirects to dashboard after completion
- Accessible at `/student/profile-setup`

### 4. Dashboard Service Update
**File**: `app/Services/DashboardService.php`

**Changes**:
- Checks for student profile existence
- Returns appropriate message if profile incomplete
- Returns empty dashboard data with `roadmap_needed: true` if profile exists but no roadmap

### 5. Profile Service Update
**File**: `app/Services/ProfileService.php`

**Changes**:
- `updateStudentQuestions()` now creates profile if it doesn't exist
- Handles both creation and update scenarios
- Converts `preferred_technologies` array to comma-separated string

### 6. Frontend Updates

**StudentDashboard.jsx**:
- Handles 403 responses with `profile_incomplete` flag
- Automatically redirects to profile setup page
- Shows appropriate error messages

**App.jsx**:
- Added route for `/student/profile-setup`
- Imported `ProfileSetupPage` component

## Flow

1. **User Registers** → Gets token, no profile created yet
2. **User Logs In** → Tries to access dashboard
3. **Middleware Intercepts** → Checks for profile
4. **Profile Missing** → Returns 403 with `profile_incomplete: true`
5. **Frontend Redirects** → To `/student/profile-setup`
6. **User Completes Wizard** → Calls `/api/auth/student/questions`
7. **Profile Created/Updated** → Middleware allows access
8. **User Accesses Dashboard** → All data loads correctly

## Testing

### Test Cases

1. **New User Registration**:
   - Register as student
   - Try to access dashboard → Should redirect to profile setup
   - Complete wizard → Should access dashboard

2. **Incomplete Profile**:
   - User with profile but missing required fields
   - Try to access dashboard → Should redirect to profile setup

3. **Complete Profile**:
   - User with complete profile
   - Access dashboard → Should work normally

4. **API Direct Access**:
   - Call `/api/auth/student/dashboard` without profile
   - Should return 403 with `profile_incomplete: true`

## Required Profile Fields

The middleware checks for these required fields:
- `major_subject`
- `current_skill_level`
- `main_goal`

If any are missing, user is redirected to complete profile.

## Notes

- Middleware only applies to students (`user_type === 'student'`)
- Profile completion endpoint (`/api/auth/student/questions`) bypasses middleware
- Dashboard API gracefully handles missing roadmap (returns empty data with message)
- Frontend automatically handles redirects based on API responses

