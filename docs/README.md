# Wajenzi ERP — Documentation Index

## Workflow Documentation

Each file below documents one workflow: trigger, actors, status flow, gates, notifications, HTTP routes, DB schema, and key files.

### Project Lifecycle

| File | What it covers |
|---|---|
| [project-approval-workflow.md](project-approval-workflow.md) | Client registration + Project creation — RingleSoft MD approval for both |
| [project-schedule-workflow.md](project-schedule-workflow.md) | Architect schedule — MD approval, activity execution, B7 structural trigger |
| [boq-workflow.md](boq-workflow.md) | BOQ Preparation Plan → BOQ — QS prepares after structural design approval |
| [qs-workflow.md](qs-workflow.md) | QS full role — BOQ Plan → BOQ → procurement oversight, dashboard, downstream |
| [structural-design-approval-workflow.md](structural-design-approval-workflow.md) | Civil Engineer — 3 stages, work schedule gate, RingleSoft final sign-off |
| [service-design-approval-workflow.md](service-design-approval-workflow.md) | Service Engineer — 4 MEP stages (Electrical, FADS, ICT, HVAC), work schedule gate |
| [engineer-workflow.md](engineer-workflow.md) | Site Engineer daily reports — work activities, labor, materials, payments, MD approval |

### Procurement

| File | What it covers |
|---|---|
| [procurement-workflow.md](procurement-workflow.md) | Material Request → Quotations (×3) → Comparison → Purchase Order → Delivery → Inspection → Payment |

### Sales & Marketing

| File | What it covers |
|---|---|
| [sales-daily-report-workflow.md](sales-daily-report-workflow.md) | Sales daily report with lead follow-ups — RingleSoft MD approval |
| [field-marketing-workflow.md](field-marketing-workflow.md) | Field sessions, door-to-door visits, targets (no approval) |
| [whatsapp-marketing-workflow.md](whatsapp-marketing-workflow.md) | WhatsApp lead pipeline, campaigns, call log, conversion to client |
| [content-creator-workflow.md](content-creator-workflow.md) | Task assignment, Kanban, platform targets, manager approval |

### Human Resources

| File | What it covers |
|---|---|
| [hr-workflows.md](hr-workflows.md) | Leave Request, Advance Salary, Loan — all single-step RingleSoft MD approval |

### Finance

| File | What it covers |
|---|---|
| [finance-workflows.md](finance-workflows.md) | Imprest (with retirement), Petty Cash Refill, Expense, VAT Payment, Statutory Payment |

### Site Management

| File | What it covers |
|---|---|
| [site-visit-workflow.md](site-visit-workflow.md) | Site Visit (auto-submit), Site Daily Report — RingleSoft MD approval |
| [qa-workflow.md](qa-workflow.md) | Material Inspection (goods received → stock update) + Labor Inspection (artisan QA → payment unlock) |

---

## Other Documentation

| File | What it covers |
|---|---|
| [system-overview.md](system-overview.md) | High-level system architecture and module map |
| [client-portal-guide.md](client-portal-guide.md) | Client portal features and access |
| [architect-bonus-scheme.md](architect-bonus-scheme.md) | Architect performance bonus calculation |
| [labor-charge-procurement.md](labor-charge-procurement.md) | Labor charge and procurement integration |
| [mobile-app.md](mobile-app.md) | Mobile app features |
| [executive-summary.md](executive-summary.md) | Executive summary of the system |

---

## RingleSoft Approval Flows Reference

| ID | Flow Name | Model | Approver |
|---|---|---|---|
| 1 | Project Approval | `Project` | Managing Director |
| 3 | Project Client | `ProjectClient` | Managing Director |
| 5 | VAT Payment | `VatPayment` | Managing Director |
| 6 | Expense | `Expense` | Managing Director |
| 7 | Statutory Payment | `StatutoryPayment` | Managing Director |
| 9 | Advance Salary | `AdvanceSalary` | Managing Director |
| 10 | Loan | `Loan` | Managing Director |
| 11 | Leave Request | `LeaveRequest` | Managing Director |
| 12 | Project Site Visit | `ProjectSiteVisit` | Managing Director |
| 13 | Petty Cash Refill | `PettyCashRefillRequest` | Managing Director |
| 14 | Imprest Request | `ImprestRequest` | Managing Director |
| 15 | Sales Daily Report | `SalesDailyReport` | Managing Director |
| 17 | Material Request | `ProjectMaterialRequest` | Managing Director |
| 18 | Quotation Comparison | `QuotationComparison` | Managing Director |
| 19 | Material Inspection | `MaterialInspection` | Managing Director |
| 22 | BOQ | `ProjectBoq` | Managing Director |
| 23 | Material Transfer | `MaterialTransfer` | Managing Director |
| 24 | Schedule Activity | `ProjectScheduleActivity` | Managing Director |
| 26 | Project Schedule | `ProjectSchedule` | Managing Director |
| 27 | BOQ Plan | `ProjectBoqPlan` | Managing Director |
| 28 | Service Design | `ProjectServiceDesign` | Managing Director |
| — | Structural Design | `ProjectStructuralDesign` | Managing Director |
