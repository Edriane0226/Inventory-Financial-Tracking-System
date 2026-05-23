# Financial Analytics Controller and Views Design

## Problem and Goal
The project currently has placeholders for Financial Tracking cards but no functional analytics module. The goal is to add an Owner-only Financial Analytics feature set that provides:

1. Automated revenue tracking from sales receipts.
2. Expense recording and dynamic categorization.
3. Net income calculation (`Revenue - Expenses`).
4. Daily financial summaries.
5. Weekly financial reports (ISO week: Monday-Sunday).
6. Monthly financial statements.
7. Profit margin analysis (`Net Income / Revenue * 100`).
8. Financial dashboard overview.
9. Expense history with filtering.
10. Monthly CSV export.

## Scope
In scope:
1. New `FinancialAnalyticsController`.
2. New financial views (`dashboard`, `expenses`) and supporting partial UI blocks.
3. New expense tables and models.
4. Revenue aggregation from `receipts.total_amount`.
5. Add timestamp support on receipts for clean period reporting.
6. CSV export for monthly statement data.
7. Route and navigation wiring.

Out of scope:
1. PDF export.
2. Non-owner access to financial pages.
3. Async job/snapshot reporting pipeline.

## Chosen Approach
**Approach A (approved): normalized analytics module**

Why:
1. Keeps expense categories consistent via relational model.
2. Supports CRUD and filtering cleanly without denormalization issues.
3. Matches existing CodeIgniter MVC patterns and incremental schema migration style.

## Architecture
Add a new Owner-only `FinancialAnalyticsController` that orchestrates:

1. Revenue metrics from `receipts` (using `total_amount` + `created_at`).
2. Expense metrics from new `expenses` table joined with `expense_categories`.
3. Derived KPI values (net income, margin) for daily/weekly/monthly windows.
4. Expense CRUD flows and filtered history retrieval.
5. CSV export for monthly statement aggregates.

UI surfaces:
1. `app/Views/financial/dashboard.php` for overview cards + summary tables.
2. `app/Views/financial/expenses.php` for CRUD and filterable expense history.

## Components
### Database
1. **Receipts timestamp migration**
   - Add `created_at` column to `receipts`.
   - Backfill missing values to current timestamp for legacy rows.

2. **Expense categories table**
   - `id` (PK)
   - `name` (unique)
   - `is_active` (boolean/int)
   - timestamps

3. **Expenses table**
   - `id` (PK)
   - `category_id` (FK expense_categories.id)
   - `amount` (decimal)
   - `note` (text/varchar)
   - `expense_date` (date/datetime)
   - `recorded_by` (FK users.id)
   - timestamps

### Models
1. `ExpenseCategory` model.
2. `Expense` model.

### Controller
`FinancialAnalyticsController` methods:
1. `index()` -> dashboard.
2. `expenses()` -> list with filters + forms.
3. `createExpense()`, `updateExpense($id)`, `deleteExpense($id)`.
4. `createCategory()`, `updateCategory($id)` for explicit category management actions.
5. `exportMonthlyCsv()` -> CSV download endpoint.

### Routing and Navigation
1. Add routes under `/financial` namespace.
2. Add menu navigation item for Owner role.
3. Link Financial Tracking dashboard cards to analytics route.

## Data Flow
### Dashboard metrics flow
1. Controller computes date windows: today, current ISO week, current month.
2. Revenue per window: aggregate `SUM(receipts.total_amount)` by `created_at`.
3. Expenses per window: aggregate `SUM(expenses.amount)` by `expense_date`.
4. Net income: `revenue - expenses`.
5. Margin: `(net_income / revenue) * 100`, with zero-safe guard.
6. View renders KPI cards and windowed summary tables.

### Expense management flow
1. Create/edit validates category, positive amount, and date.
2. Records persist with `recorded_by` from session.
3. History filters apply:
   - date range,
   - category,
   - note keyword.
4. Delete requests remove expense records directly; category deletion is blocked if category is in use.

### Monthly CSV export flow
1. Endpoint validates target month/year.
2. Aggregates monthly revenue/expense/net/margin.
3. Returns CSV with header row and computed values.

## Error Handling
1. **Authorization**: Owner-only guard on all financial routes.
2. **Validation**: return input + field-level errors for invalid form data.
3. **Category safety**: prevent deletion when referenced by expenses.
4. **CSV input safety**: reject invalid month/year ranges with explicit error.
5. **Math safety**: margin = `0` when revenue is `0`.

## Testing Strategy
1. Feature tests for Owner-only route access.
2. Feature/database tests for expense CRUD and validation.
3. Filter behavior tests (date/category/keyword).
4. Aggregation tests for daily/weekly/monthly metrics.
5. CSV export tests:
   - content type/header,
   - row shape and values.

## Implementation Notes
1. Keep SQL logic in controller/model methods consistent with current query builder style used by `StockOutController`.
2. Reuse existing flash messaging + redirect patterns for UX consistency.
3. Keep first iteration focused; avoid adding forecasting, charts libraries, or background jobs.
