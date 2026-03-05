# Architect Bonus Scheme System

**Version:** 1.0
**Module:** Architect Performance & Bonus Management

---

## 1. Purpose

A performance-based bonus system for architects. Bonuses are calculated from project value, schedule adherence, design quality, and client approval efficiency. All calculations happen on the backend — architects only see their earned units and bonus amounts.

---

## 2. Bonus Unit Structure

**1 Unit = 10,000 TZS**

Maximum units are determined by project budget tier:

| S/N | Project Amount (TZS) | Max Units |
|-----|----------------------|-----------|
| 1   | 1M and below         | 1         |
| 2   | 1M - 3.5M           | 2         |
| 3   | 3.5M - 7M           | 4         |
| 4   | 7M - 12M            | 5         |
| 5   | 12M - 15M           | 7         |
| 6   | 15M - 20M           | 10        |
| 7   | 20M - 30M           | 15        |
| 8   | 30M - 40M           | 20        |
| 9   | 40M - 50M           | 25        |
| 10  | 50M - 60M           | 30        |
| 11  | 60M - 70M           | 35        |
| 12  | 70M - 80M           | 40        |
| 13  | 80M - 90M           | 45        |
| 14  | 90M - 100M          | 50        |

---

## 3. Performance Factors

### 3.1 Schedule Performance (SP)

```
SP = Scheduled Duration / Actual Duration
SP = min(SP, 1.1)   // Capped at 1.1 to prevent rushing
```

- If architect finishes early, SP > 1.0 (max 1.1)
- If architect finishes late, SP < 1.0
- Cap at 1.1 prevents gaming (rushing at the cost of quality)

### 3.2 Design Quality Score (DQ)

- Rated by the design manager
- Scale: 0.4 to 1.0
- Evaluates design coordination, documentation quality, safety checks

### 3.3 Client Approval Efficiency (CA)

```
CA = 1 / Number of Client Revisions
```

| Revisions | CA Score |
|-----------|----------|
| 1         | 1.0      |
| 2         | 0.5      |
| 3         | 0.33     |
| 4         | 0.25     |

---

## 4. Performance Score Formula

```
PS = (W_schedule x SP) + (W_quality x DQ) + (W_client x CA)
```

Default weights:

| Factor             | Weight | Reason                          |
|--------------------|--------|---------------------------------|
| Schedule (SP)      | 0.40   | Keeps projects on schedule      |
| Design Quality (DQ)| 0.40   | Protects firm reputation        |
| Client Approval (CA)| 0.20  | Improves client satisfaction    |

Weights are **configurable** in the database. Alternative models:

| Model               | SP   | DQ   | CA   |
|----------------------|------|------|------|
| Speed-focused firm   | 0.50 | 0.30 | 0.20 |
| Quality-focused firm | 0.30 | 0.50 | 0.20 |
| Client-focused firm  | 0.35 | 0.35 | 0.30 |

---

## 5. Final Bonus Calculation

```
Final Units = min(Max Units x PS, Max Units)
Bonus Amount = Final Units x 10,000 TZS
```

**No Bonus Rule:** If delay exceeds 50% of the scheduled duration, bonus = 0.

Example: Scheduled = 10 days, if actual > 15 days, no bonus.

---

## 6. Worked Example

```
Project Budget:      25,000,000 TZS
Max Units (tier):    15

Scheduled Duration:  10 days
Actual Duration:     12 days

SP = 10 / 12 = 0.83
DQ = 0.9 (rated by manager)
CA = 1 / 2 = 0.5 (2 client revisions)

PS = (0.4 x 0.83) + (0.4 x 0.9) + (0.2 x 0.5)
PS = 0.332 + 0.36 + 0.10
PS = 0.792

Final Units = 15 x 0.792 = 11.88 -> 12 (rounded)
Bonus = 12 x 10,000 = 120,000 TZS
```

---

## 7. Visibility Rules

### Architect View (restricted)
- Task Name
- Deadline
- Current Bonus Units
- Bonus Amount
- Task Status

### Admin View (full access)
- All architect-visible fields plus:
- Project Budget
- Performance factor breakdown (SP, DQ, CA)
- Weight configuration
- Internal calculations

---

## 8. Task Data Model

Each bonus task stores:

| Field                    | Type     | Visibility |
|--------------------------|----------|------------|
| task_id                  | auto     | admin      |
| project_name             | string   | all        |
| architect_id             | FK(users)| admin      |
| project_budget           | decimal  | admin only |
| start_date               | date     | all        |
| scheduled_completion_date| date     | all        |
| actual_completion_date   | date     | all        |
| max_units                | integer  | admin      |
| design_quality_score     | decimal  | admin      |
| client_revisions         | integer  | admin      |
| current_units            | decimal  | all        |
| bonus_amount             | decimal  | all        |
| status                   | enum     | all        |

---

## 9. Database Schema

### Tables

**`bonus_unit_tiers`** - Project amount to max units mapping
- id, min_amount, max_amount, max_units, timestamps

**`bonus_weight_configs`** - Configurable performance weights
- id, factor (schedule/quality/client), weight, timestamps

**`architect_bonus_tasks`** - Main bonus task records
- id, project_name, architect_id, project_budget, lead_id (nullable),
  project_schedule_id (nullable), start_date, scheduled_completion_date,
  actual_completion_date, max_units, design_quality_score, client_revisions,
  performance_score, final_units, bonus_amount, status, created_by, timestamps

---

## 10. Reports

The system generates:

1. **Monthly Architect Bonus Report** - All bonuses per month with totals
2. **Task Completion Performance Report** - SP/DQ/CA breakdown per task
3. **Per-Architect Summary** - Total units earned, total bonus amount, avg performance

---

## 11. Security Requirements

- Project budgets are hidden from architects (backend-only)
- Only admins can create tasks and edit budgets
- Bonus calculations run server-side only
- Architects cannot modify any scoring fields
- Weight configuration is admin-only

---

## 12. Implementation Checklist

- [ ] Migration: `bonus_unit_tiers` table + seed default tiers
- [ ] Migration: `bonus_weight_configs` table + seed default weights (40/40/20)
- [ ] Migration: `architect_bonus_tasks` table
- [ ] Model: `BonusUnitTier`
- [ ] Model: `BonusWeightConfig`
- [ ] Model: `ArchitectBonusTask`
- [ ] Service: `BonusCalculationService` (PS formula, no-bonus rule, unit lookup)
- [ ] Controller: `ArchitectBonusController` (admin CRUD + architect read-only)
- [ ] View: Admin bonus task list (full details, create/edit/score)
- [ ] View: Admin bonus task form (create, assign, set budget)
- [ ] View: Admin scoring form (DQ rating, client revisions, completion date)
- [ ] View: Architect bonus dashboard (restricted fields only)
- [ ] View: Reports (monthly, per-architect, performance)
- [ ] Routes and sidebar menu integration
- [ ] Settings page for weight configuration
