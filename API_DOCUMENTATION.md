# Update Profile API Documentation

## Overview
The Update Profile API allows users to update their profile information based on their user type (student, mentor, professional, or company). The API automatically validates and updates the appropriate fields based on the user's type.

## Endpoint
```
POST /api/auth/update-profile
```

## Authentication
- **Required**: Bearer Token (Sanctum)
- **Header**: `Authorization: Bearer {token}`

## Request Format
The API accepts different fields based on the user type. All fields are optional, and only provided fields will be updated.

### Base Fields (Available for all user types)
- `first_name` (string, max: 255)
- `last_name` (string, max: 255)
- `phone` (string, max: 20)

### Student Profile Fields
- `dob` (date, format: Y-m-d)
- `gender` (enum: male, female, other)
- `class_year` (string, max: 255)
- `institute` (string, max: 255)
- `major_subject` (string, max: 255)
- `bio` (string, max: 1000)

### Mentor Profile Fields
- `qualifications` (string, max: 1000)
- `area_of_expertise` (array of strings, each max: 255)
- `experience_years` (integer, min: 0, max: 50)
- `institute` (string, max: 255)
- `bio` (string, max: 1000)

### Professional Profile Fields
- `executive_summary` (string, max: 1000)
- `skills` (array of strings, each max: 255)
- `current_position` (string, max: 255)
- `year_of_experience` (string, max: 50)
- `bio` (string, max: 1000)

### Company Profile Fields
- `company_size` (string, max: 255)
- `industry` (string, max: 255)
- `bio` (string, max: 1000)
- `website_link` (url, max: 255)
- `location` (string, max: 255)

## Response Format

### Success Response (200)
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": "user_id",
        "first_name": "Updated Name",
        "last_name": "Updated Last Name",
        "email": "user@example.com",
        "phone": "1234567890",
        "user_type": "student",
        "status": "pending",
        "student_profile": {
            "dob": "2000-01-01",
            "gender": "male",
            "class_year": "2024",
            "institute": "University Name",
            "major_subject": "Computer Science",
            "bio": "Student bio"
        }
    }
}
```

### Error Response (422 - Validation Error)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### Error Response (404 - User Not Found)
```json
{
    "success": false,
    "message": "User not found"
}
```

## Example Requests

### Update Student Profile
```bash
curl -X POST "https://your-domain.com/api/auth/update-profile" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "1234567890",
    "dob": "2000-01-01",
    "gender": "male",
    "class_year": "2024",
    "institute": "University of Technology",
    "major_subject": "Computer Science",
    "bio": "Passionate about technology and learning"
  }'
```

### Update Mentor Profile
```bash
curl -X POST "https://your-domain.com/api/auth/update-profile" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Dr. Jane",
    "last_name": "Smith",
    "qualifications": "PhD in Computer Science, MBA",
    "area_of_expertise": ["Programming", "Algorithms", "Machine Learning"],
    "experience_years": 10,
    "institute": "Tech University",
    "bio": "Experienced mentor with 10+ years in tech"
  }'
```

### Update Professional Profile
```bash
curl -X POST "https://your-domain.com/api/auth/update-profile" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Bob",
    "last_name": "Johnson",
    "executive_summary": "Senior software engineer with expertise in web development",
    "skills": ["PHP", "Laravel", "JavaScript", "React", "Node.js"],
    "current_position": "Lead Developer",
    "year_of_experience": "7",
    "bio": "Passionate about building scalable web applications"
  }'
```

### Update Company Profile
```bash
curl -X POST "https://your-domain.com/api/auth/update-profile" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Tech Corp",
    "last_name": "Inc",
    "company_size": "100-500",
    "industry": "Technology",
    "bio": "Leading technology company focused on innovation",
    "website_link": "https://techcorp.com",
    "location": "San Francisco, CA"
  }'
```

## Validation Rules

### Date Fields
- `dob`: Must be in Y-m-d format (e.g., "2000-01-01")

### Enum Fields
- `gender`: Must be one of: male, female, other

### Array Fields
- `area_of_expertise`: Array of strings, each string max 255 characters
- `skills`: Array of strings, each string max 255 characters

### URL Fields
- `website_link`: Must be a valid URL format

### String Length Limits
- Most text fields have a maximum length of 255 characters
- Bio fields have a maximum length of 1000 characters
- Qualifications and executive summary have a maximum length of 1000 characters

## Error Handling

The API handles various error scenarios:

1. **Validation Errors (422)**: When request data doesn't meet validation rules
2. **Unauthorized (401)**: When no valid token is provided
3. **User Not Found (404)**: When the authenticated user doesn't exist
4. **Profile Not Found (404)**: When the user's profile doesn't exist for their type
5. **Internal Server Error (500)**: When unexpected errors occur

## Notes

- All fields are optional - only provided fields will be updated
- The API automatically determines the user type from the authenticated user
- Profile updates are performed in a database transaction to ensure data consistency
- The response includes the updated user data with their profile information
- Array fields (like skills, area_of_expertise) should be sent as JSON arrays
- The API follows the same coding patterns and response format as other endpoints in the project
