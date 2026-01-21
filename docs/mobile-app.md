# Wajenzi Mobile App - Implementation Guide

## Overview

This document outlines the implementation of the Wajenzi Construction ERP mobile application. The app provides **full feature parity** with the web application using an **offline-first architecture**.

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Mobile Framework | Flutter 3.x |
| State Management | Riverpod 2.x |
| Local Database | Drift (SQLite) |
| Backend | Laravel 11 |
| API Auth | Laravel Sanctum |
| Sync Strategy | Offline-first with background sync |

---

## Architecture

### Offline-First Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                      User Action                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Save to Local DB                         │
│                    (Drift/SQLite)                           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Add to Sync Queue                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Network Check                            │
├─────────────────────────────────────────────────────────────┤
│  Online?                                                    │
│  ├── YES → Process Queue → API Call → Update Local → Done   │
│  └── NO  → Wait → Background Sync (WorkManager)             │
└─────────────────────────────────────────────────────────────┘
```

### Conflict Resolution

| Scenario | Resolution |
|----------|------------|
| Approval changes | Server wins (always) |
| Data updates | Last-write-wins with timestamps |
| Critical conflicts | User prompt for resolution |

---

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | User login, returns token |
| POST | `/api/v1/auth/logout` | Revoke current token |
| GET | `/api/v1/auth/user` | Get authenticated user |
| PUT | `/api/v1/auth/profile` | Update user profile |
| POST | `/api/v1/auth/device-token` | Register FCM token |

### Attendance (Offline-Critical)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/attendance/check-in` | Record check-in with GPS |
| POST | `/api/v1/attendance/check-out` | Record check-out with GPS |
| GET | `/api/v1/attendance` | List attendance records |
| GET | `/api/v1/attendance/status` | Current day status |

### Site Daily Reports (Offline-Critical)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/site-daily-reports` | List reports |
| POST | `/api/v1/site-daily-reports` | Create report |
| GET | `/api/v1/site-daily-reports/{id}` | Get report detail |
| PUT | `/api/v1/site-daily-reports/{id}` | Update report |
| DELETE | `/api/v1/site-daily-reports/{id}` | Delete report |
| POST | `/api/v1/site-daily-reports/{id}/submit` | Submit for approval |
| POST | `/api/v1/site-daily-reports/{id}/approve` | Approve report |
| POST | `/api/v1/site-daily-reports/{id}/reject` | Reject report |

### Sales Daily Reports

Same CRUD + approval pattern as Site Daily Reports.

### Expenses (Offline-Critical)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/expenses` | List expenses |
| POST | `/api/v1/expenses` | Create expense |
| GET | `/api/v1/expenses/{id}` | Get expense detail |
| PUT | `/api/v1/expenses/{id}` | Update expense |
| DELETE | `/api/v1/expenses/{id}` | Delete expense |
| POST | `/api/v1/expenses/{id}/submit` | Submit for approval |
| POST | `/api/v1/expenses/{id}/approve` | Approve expense |
| POST | `/api/v1/expenses/{id}/reject` | Reject expense |

### Projects

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/projects` | List user's projects |
| GET | `/api/v1/projects/{id}` | Get project detail |
| GET | `/api/v1/projects/{id}/boq` | Get project BOQ |
| GET | `/api/v1/projects/{id}/materials` | Get project materials |

### Site Visits

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/site-visits` | List site visits |
| POST | `/api/v1/site-visits` | Create site visit |
| GET | `/api/v1/site-visits/{id}` | Get visit detail |
| PUT | `/api/v1/site-visits/{id}` | Update visit |
| POST | `/api/v1/site-visits/{id}/submit` | Submit for approval |

### Material Requests

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/material-requests` | List requests |
| POST | `/api/v1/material-requests` | Create request |
| GET | `/api/v1/material-requests/{id}` | Get request detail |
| POST | `/api/v1/material-requests/{id}/submit` | Submit for approval |

### Billing Documents

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/billing/documents` | List documents |
| GET | `/api/v1/billing/documents/{id}` | Get document detail |
| POST | `/api/v1/billing/documents` | Create document |
| PUT | `/api/v1/billing/documents/{id}` | Update document |
| POST | `/api/v1/billing/documents/{id}/send` | Send to client |

### Billing Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/billing/payments` | List payments |
| POST | `/api/v1/billing/payments` | Record payment |
| GET | `/api/v1/billing/payments/{id}` | Get payment detail |

### Sync

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/sync/push` | Push offline changes |
| GET | `/api/v1/sync/pull` | Pull server changes |
| GET | `/api/v1/sync/reference-data` | Get static reference data |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/notifications` | List notifications |
| PUT | `/api/v1/notifications/{id}/read` | Mark as read |
| PUT | `/api/v1/notifications/read-all` | Mark all as read |

---

## Flutter Project Structure

```
lib/
├── core/
│   ├── config/
│   │   ├── app_config.dart          # App-wide configuration
│   │   ├── api_config.dart          # API endpoints, base URL
│   │   └── theme_config.dart        # Theme definitions
│   ├── network/
│   │   ├── api_client.dart          # Dio HTTP client
│   │   ├── api_interceptors.dart    # Auth, logging interceptors
│   │   └── network_info.dart        # Connectivity checker
│   ├── services/
│   │   ├── storage_service.dart     # Secure storage
│   │   ├── sync_service.dart        # Offline sync logic
│   │   └── notification_service.dart # FCM handling
│   └── router/
│       └── app_router.dart          # GoRouter configuration
│
├── data/
│   ├── datasources/
│   │   ├── local/
│   │   │   ├── database.dart        # Drift database
│   │   │   ├── tables/              # Drift table definitions
│   │   │   └── daos/                # Data access objects
│   │   └── remote/
│   │       ├── auth_api.dart
│   │       ├── attendance_api.dart
│   │       ├── reports_api.dart
│   │       └── sync_api.dart
│   ├── models/                      # Freezed data models
│   │   ├── user_model.dart
│   │   ├── attendance_model.dart
│   │   ├── site_daily_report_model.dart
│   │   └── ...
│   └── repositories/                # Repository implementations
│       ├── auth_repository_impl.dart
│       ├── attendance_repository_impl.dart
│       └── ...
│
├── domain/
│   ├── entities/                    # Business entities
│   ├── repositories/                # Repository interfaces
│   └── usecases/                    # Business logic
│
├── presentation/
│   ├── providers/                   # Riverpod providers
│   │   ├── auth_provider.dart
│   │   ├── attendance_provider.dart
│   │   └── ...
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   └── profile_screen.dart
│   │   ├── dashboard/
│   │   │   └── dashboard_screen.dart
│   │   ├── attendance/
│   │   │   ├── attendance_screen.dart
│   │   │   ├── check_in_screen.dart
│   │   │   └── attendance_history_screen.dart
│   │   ├── reports/
│   │   │   ├── site_daily_report_list_screen.dart
│   │   │   ├── site_daily_report_form_screen.dart
│   │   │   └── site_daily_report_detail_screen.dart
│   │   ├── expenses/
│   │   ├── approvals/
│   │   ├── projects/
│   │   ├── billing/
│   │   └── settings/
│   └── widgets/
│       ├── common/
│       │   ├── app_scaffold.dart
│       │   ├── loading_widget.dart
│       │   └── error_widget.dart
│       ├── forms/
│       │   ├── custom_text_field.dart
│       │   ├── date_picker_field.dart
│       │   └── dropdown_field.dart
│       └── cards/
│           ├── report_card.dart
│           ├── expense_card.dart
│           └── approval_card.dart
│
└── l10n/
    ├── app_en.arb                   # English translations
    └── app_sw.arb                   # Swahili translations
```

---

## Drift Database Schema

### Core Tables

```dart
// sync_queue.dart - Track offline operations
class SyncQueue extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get tableName => text()();
  TextColumn get operation => text()(); // create, update, delete
  TextColumn get recordId => text()();
  TextColumn get payload => text()(); // JSON data
  IntColumn get priority => integer().withDefault(const Constant(0))();
  DateTimeColumn get createdAt => dateTime()();
  TextColumn get status => text().withDefault(const Constant('pending'))();
  IntColumn get retryCount => integer().withDefault(const Constant(0))();
  TextColumn get errorMessage => text().nullable()();
}

// users.dart
class Users extends Table {
  IntColumn get id => integer()();
  TextColumn get name => text()();
  TextColumn get email => text()();
  TextColumn get employeeNumber => text().nullable()();
  TextColumn get designation => text().nullable()();
  TextColumn get profileUrl => text().nullable()();
  TextColumn get signatureUrl => text().nullable()();
  DateTimeColumn get syncedAt => dateTime().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}

// attendances.dart
class Attendances extends Table {
  IntColumn get id => integer().autoIncrement()();
  IntColumn get serverId => integer().nullable()();
  IntColumn get userId => integer()();
  DateTimeColumn get recordTime => dateTime()();
  TextColumn get type => text()(); // in, out
  RealColumn get latitude => real().nullable()();
  RealColumn get longitude => real().nullable()();
  TextColumn get ip => text().nullable()();
  TextColumn get comment => text().nullable()();
  BoolColumn get isSynced => boolean().withDefault(const Constant(false))();
  DateTimeColumn get createdAt => dateTime()();
}

// site_daily_reports.dart
class SiteDailyReports extends Table {
  IntColumn get id => integer().autoIncrement()();
  IntColumn get serverId => integer().nullable()();
  DateTimeColumn get reportDate => dateTime()();
  IntColumn get siteId => integer()();
  IntColumn get supervisorId => integer().nullable()();
  IntColumn get preparedById => integer()();
  IntColumn get progressPercentage => integer().nullable()();
  TextColumn get nextSteps => text().nullable()();
  TextColumn get challenges => text().nullable()();
  TextColumn get status => text().withDefault(const Constant('draft'))();
  BoolColumn get isSynced => boolean().withDefault(const Constant(false))();
  DateTimeColumn get createdAt => dateTime()();
  DateTimeColumn get updatedAt => dateTime()();
}

// expenses.dart
class Expenses extends Table {
  IntColumn get id => integer().autoIncrement()();
  IntColumn get serverId => integer().nullable()();
  IntColumn get projectId => integer()();
  IntColumn get categoryId => integer()();
  TextColumn get description => text()();
  RealColumn get amount => real()();
  DateTimeColumn get expenseDate => dateTime()();
  TextColumn get receiptPath => text().nullable()();
  TextColumn get status => text().withDefault(const Constant('draft'))();
  BoolColumn get isSynced => boolean().withDefault(const Constant(false))();
  DateTimeColumn get createdAt => dateTime()();
}
```

---

## Feature Modules

### Priority 0 (MVP) - Offline-Critical

| Module | Screens | Offline Support |
|--------|---------|-----------------|
| Auth | Login, Profile | Token cached |
| Dashboard | Overview, KPIs | Cached data |
| Attendance | Check-in/out, History | Full offline |
| Site Daily Reports | List, Create, Edit, Detail | Full offline |
| Sales Daily Reports | List, Create, Edit, Detail | Full offline |
| Expenses | List, Create, Edit, Detail | Full offline |
| Approvals | Pending List, Actions | Queue offline |

### Priority 1 - Core Features

| Module | Screens | Offline Support |
|--------|---------|-----------------|
| Projects | List, Detail, BOQ | Read-only cache |
| Site Visits | List, Create, Detail | Full offline |
| Material Requests | List, Create, Detail | Full offline |
| Billing | Invoices, Quotes, Payments | Partial offline |
| Settings | Profile, Sync Status | Local only |

### Priority 2 - Full Parity

| Module | Screens | Offline Support |
|--------|---------|-----------------|
| HR/Leave | Leave Requests, Balance | Queue offline |
| Payroll | Payslip List, Detail | Read-only cache |
| Reports | Attendance, Expense Reports | Cache results |

---

## API Response Format

### Success Response

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Sync Response

```json
{
  "success": true,
  "data": {
    "synced": [
      {"local_id": "uuid-1", "server_id": 123, "table": "attendances"}
    ],
    "conflicts": [
      {"local_id": "uuid-2", "server_id": 456, "table": "site_daily_reports", "resolution": "server_wins"}
    ],
    "failed": []
  },
  "server_time": "2024-01-15T10:30:00Z"
}
```

---

## Security Considerations

1. **Token Storage**: Use `flutter_secure_storage` for auth tokens
2. **API Security**: All endpoints require Sanctum authentication
3. **Data Encryption**: SQLite database encryption for sensitive data
4. **Certificate Pinning**: Implement for production builds
5. **Biometric Auth**: Optional local authentication for app access

---

## Testing Strategy

### Backend Testing

1. Feature tests for all API endpoints
2. Test authentication flow
3. Test approval workflow transitions
4. Test sync conflict resolution

### Flutter Testing

1. Unit tests for repositories and use cases
2. Widget tests for form validation
3. Integration tests for offline sync
4. Golden tests for UI consistency

### End-to-End Testing

1. Create report offline → Sync → Verify on web
2. Approve on web → Pull on mobile → Verify status
3. Test conflict resolution scenarios
4. Test background sync with WorkManager

---

## Deployment

### Backend

- API hosted alongside web application
- Same Laravel instance serves both web and API
- Sanctum tokens scoped per device

### Mobile

- Android: Google Play Store
- iOS: Apple App Store
- Internal distribution via Firebase App Distribution

---

## Development Commands

### Backend

```bash
# Install Sanctum
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Run API tests
php artisan test --filter=Api
```

### Flutter

```bash
# Create project
flutter create --org com.wajenzi wajenzi_mobile
cd wajenzi_mobile

# Generate Drift database
dart run build_runner build

# Generate Freezed models
dart run build_runner build --delete-conflicting-outputs

# Run tests
flutter test

# Build for release
flutter build apk --release
flutter build ios --release
```

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2024-01-15 | 0.1.0 | Initial documentation |
