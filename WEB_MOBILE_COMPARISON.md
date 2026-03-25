# Wajenzi Portal Feature Comparison - Web vs Mobile

## Overview
This document compares the features available in the Web Portal vs Mobile App (Flutter).

---

## ✅ Staff Portal (Internal Employees)

### Authentication
| Feature | Web | Mobile API |
|---------|-----|------------|
| Login with email/password | ✅ `/login` | ✅ `/api/v1/auth/login` |
| Session/Token management | ✅ Session | ✅ Sanctum Token |
| Logout | ✅ | ✅ |
| Profile view | ✅ | ✅ `/api/v1/auth/user` |
| Profile update | ✅ | ✅ `/api/v1/auth/profile` |

### Dashboard
| Feature | Web | Mobile API |
|---------|-----|------------|
| Main dashboard stats | ✅ `/dashboard` | ✅ `/api/v1/dashboard` |
| Activities list | ✅ | ✅ `/api/v1/dashboard/activities` |
| Calendar view | ✅ | ✅ `/api/v1/dashboard/calendar` |
| Recent activities | ✅ | ✅ `/api/v1/dashboard/recent-activities` |
| Project status | ✅ | ✅ `/api/v1/dashboard/project-status` |
| Invoices overview | ✅ | ✅ `/api/v1/dashboard/invoices` |
| Follow-ups | ✅ | ✅ `/api/v1/dashboard/followups` |

### Staff Management
| Feature | Web | Mobile API |
|---------|-----|------------|
| Staff list | ✅ `/staff` | ✅ `/api/v1/employee-profile/staff-list` |
| Employee profile | ✅ `/employee_profile` | ✅ `/api/v1/employee-profile` |
| Bank details | ✅ `/payroll/staff_bank_details` | ⚠️ Not implemented |
| Adjustments | ✅ `/payroll/adjustment` | ⚠️ Not implemented |

### Projects
| Feature | Web | Mobile API |
|---------|-----|------------|
| Project list | ✅ `/projects` | ✅ (via dashboard) |
| Project details | ✅ | ✅ (via project endpoints) |

### HR & Attendance
| Feature | Web | Mobile API |
|---------|-----|------------|
| Leave requests | ✅ `/leaves/leave_request` | ✅ `/api/v1/leave-requests` |
| Leave balance | ✅ | ✅ `/api/v1/leave-requests/balance` |
| Leave types | ✅ | ✅ `/api/v1/leave-requests/types` |
| Attendance | ✅ `/attendance` | ✅ `/api/v1/attendance` |
| Check-in | ✅ | ✅ `/api/v1/attendance/check-in` |
| Check-out | ✅ | ✅ `/api/v1/attendance/check-out` |
| Attendance status | ✅ | ✅ `/api/v1/attendance/status` |

### Finance
| Feature | Web | Mobile API |
|---------|-----|------------|
| Billing documents | ✅ | ✅ `/api/v1/billing/documents` |
| Billing payments | ✅ | ✅ `/api/v1/billing/payments` |
| Billing clients | ✅ | ✅ `/api/v1/billing/clients` |
| Expenses | ✅ `/expenses` | ✅ `/api/v1/expenses` |
| Accounting | ✅ `/accounting` | ⚠️ Not implemented |

### Procurement
| Feature | Web | Mobile API |
|---------|-----|------------|
| Procurement dashboard | ✅ `/procurement_dashboard` | ⚠️ Not implemented |
| Material requests | ✅ | ✅ `/api/v1/material-requests` |
| Supplier quotations | ✅ | ⚠️ Not implemented |
| Purchases | ✅ | ⚠️ Not implemented |
| Inspections | ✅ | ⚠️ Not implemented |

### Approvals
| Feature | Web | Mobile API |
|---------|-----|------------|
| Pending approvals | ✅ | ✅ `/api/v1/approvals` |
| Approve/Reject | ✅ | ✅ `/api/v1/approvals/{type}/{id}/approve` |

### Settings
| Feature | Web | Mobile API |
|---------|-----|------------|
| System settings | ✅ `/settings` | ⚠️ Not implemented |

---

## ✅ Client Portal (External Customers)

### Authentication
| Feature | Web | Mobile API |
|---------|-----|------------|
| Login | ✅ `/client/login` | ✅ `/api/client/auth/login` |
| Logout | ✅ | ✅ |
| Profile view | ✅ | ✅ `/api/client/auth/me` |
| Profile update | ✅ | ✅ `/api/client/auth/profile` |
| Change password | ✅ | ✅ `/api/client/auth/password` |

### Dashboard
| Feature | Web | Mobile API |
|---------|-----|------------|
| Dashboard with stats | ✅ `/client/dashboard` | ✅ `/api/client/dashboard` |
| Projects list | ✅ | ✅ `/api/client/projects` |

### Project Features
| Feature | Web | Mobile API |
|---------|-----|------------|
| Project overview | ✅ `/client/project/{id}` | ✅ `/api/client/projects/{id}` |
| Bill of Quantities | ✅ `/client/project/{id}/boq` | ✅ `/api/client/projects/{id}/boq` |
| Schedule | ✅ `/client/project/{id}/schedule` | ✅ `/api/client/projects/{id}/schedule` |
| Financials | ✅ `/client/project/{id}/financials` | ✅ `/api/client/projects/{id}/financials` |
| Documents | ✅ `/client/project/{id}/documents` | ✅ `/api/client/projects/{id}/documents` |
| Reports | ✅ `/client/project/{id}/reports` | ✅ `/api/client/projects/{id}/reports` |
| Gallery | ✅ `/client/project/{id}/gallery` | ✅ `/api/client/projects/{id}/gallery` |

### Billing
| Feature | Web | Mobile API |
|---------|-----|------------|
| Billing overview | ✅ `/client/billing` | ✅ `/api/client/billing` |
| Invoice PDF download | ✅ | ✅ |
| Quote PDF download | ✅ | ✅ |
| Proforma PDF download | ✅ | ✅ |

---

## Legend
- ✅ **Implemented** - Feature works on both platforms
- ⚠️ **Partial** - Only on Web or only partial implementation
- ❌ **Not implemented** - Not yet available

---

## Test Coverage

### Laravel Tests (PHPUnit)
```
Tests: 94 passed, 1 skipped (287 assertions)

- ClientApiTest: 21 tests ✅
- ClientPortalTest: 18 tests ✅
- StaffApiTest: 26 tests ✅
- StaffPortalTest: 15 tests ✅
- ProcurementWorkflowTest: 14 tests ✅
```

### Flutter Tests
```
All tests passed! ✅
```

---

## Known Issues

### Web Portal
1. ✅ Fixed: `/payroll/staff_bank_details` - Now uses eager loading for staff and bank relationships
2. ✅ Verified: `/api/v1/approvals` - Uses proper status column filtering
3. ✅ Verified: `/api/v1/expenses/categories` - Uses CostCategory model (not ExpenseCategory - naming was misleading)

### Mobile App
1. Some features require manual testing in Flutter app
2. Offline mode needs verification
3. Push notifications need FCM configuration

---

## Recommendations

### High Priority
1. ✅ Completed: Fix `/payroll/staff_bank_details` null error - Added eager loading
2. ✅ Completed: Verify `/api/v1/approvals` query - Already properly using status column
3. ✅ Completed: Verify ExpenseCategory - Actually uses CostCategory which exists

### Medium Priority
1. Implement staff bank details in mobile API
2. Implement procurement endpoints in mobile API
3. Implement settings in mobile API

### Low Priority
1. Add offline sync functionality
2. Add biometric authentication
3. Add push notifications
