# Migration Rollback Guide

## Overview
All migrations have been updated to ensure smooth rollback and refresh operations. This document explains the migration structure and rollback order.

## Migration Order & Dependencies

### Core Tables (No Dependencies)
1. `0001_01_01_000000_create_users_table.php` - Base users table
2. `2025_09_01_140007_create_students_profiles_table.php` - Student profiles

### Roadmap Tables (Depend on users)
3. `2025_11_11_174558_create_student_roadmaps_table.php` - Creates student_roadmaps
4. `2025_11_11_180307_drop_and_recreate_student_roadmaps_table.php` - **WARNING**: Drops and recreates (causes data loss)
5. `2025_01_15_000001_add_roadmap_json_to_student_roadmaps.php` - Adds columns (safe, checks if exists)

### Dependent Tables (Depend on student_roadmaps)
6. `2025_01_15_000002_create_roadmap_errors_table.php` - Roadmap errors
7. `2025_01_15_000003_create_daily_progress_table.php` - Daily progress
8. `2025_11_23_000000_create_user_progress_table.php` - User progress
9. `2025_11_23_000001_create_messages_table.php` - Messages

## Rollback Safety Features

### 1. Foreign Key Handling
All migrations that create tables with foreign keys now:
- Drop foreign keys in `down()` method before dropping tables
- Check if table exists before attempting operations
- Handle missing columns gracefully

### 2. Column Addition Safety
The `add_roadmap_json_to_student_roadmaps` migration:
- Checks if table exists before adding columns
- Checks if columns exist before adding them
- Safely drops columns in rollback (checks existence first)

### 3. Drop and Recreate Migration
The `drop_and_recreate_student_roadmaps_table` migration:
- Drops dependent tables first to avoid FK errors
- Recreates the main table
- **WARNING**: This causes data loss - use with caution

## Commands

### Fresh Migration (Drops all tables and recreates)
```bash
php artisan migrate:fresh
```

### Rollback All Migrations
```bash
php artisan migrate:rollback
```

### Rollback Specific Number of Steps
```bash
php artisan migrate:rollback --step=5
```

### Refresh (Rollback + Migrate)
```bash
php artisan migrate:refresh
```

## Important Notes

1. **Data Loss Warning**: The `drop_and_recreate_student_roadmaps_table` migration will cause data loss. Consider removing it if not needed.

2. **Migration Order**: Migrations run in chronological order based on timestamp. The `2025_01_15` migrations run before `2025_11_11` migrations, but the add-column migration is safe because it checks for table/column existence.

3. **Foreign Key Constraints**: All dependent tables are properly handled - they drop foreign keys before dropping tables.

4. **Safe Operations**: All migrations now use `Schema::hasTable()` and `Schema::hasColumn()` checks to prevent errors.

## Troubleshooting

### Error: "Base table or view not found"
- This means a migration is trying to modify a table that doesn't exist
- All migrations now check for table existence before operations
- Run `php artisan migrate:fresh` to start clean

### Error: "Foreign key constraint fails"
- Dependent tables must be dropped before parent tables
- All migrations now handle this correctly
- If you see this, run migrations in order or use `migrate:fresh`

### Error: "Duplicate column name"
- The add-column migration checks if columns exist before adding
- This should not occur, but if it does, the migration will skip adding existing columns

## Recommended Approach

For a fresh start:
```bash
php artisan migrate:fresh
```

For production (preserving data):
- Only run new migrations: `php artisan migrate`
- Rollback carefully: `php artisan migrate:rollback --step=1`

