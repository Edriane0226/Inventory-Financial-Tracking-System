# Financial Analytics Total Breakdown Design

## Problem and Goal
Financial Analytics currently shows period totals (daily, weekly, monthly) but not the transaction rows that produced those totals. The goal is to let Owners open each period and see all underlying revenue and expense rows used in the calculation, while keeping the dashboard readable.

## Approved Scope
In scope:
1. Show drill-down breakdowns for **daily, weekly, and monthly** totals.
2. Include both revenue and expense sources in each breakdown.
3. Keep breakdowns on the existing `/financial` dashboard.
4. Use collapsible UI sections to keep totals compact by default.
5. Return all matching rows for each selected period.

Out of scope:
1. New analytics pages outside `/financial`.
2. Deduplicating records across periods (overlap is accepted).
3. Replacing current total formulas.

## Chosen Approach
**Approach A (approved): Lazy-loaded collapsible drilldowns**

Why:
1. Preserves current dashboard flow and avoids adding new pages.
2. Reduces initial page load by fetching detailed rows only when needed.
3. Supports full traceability from total values to source rows.

## Architecture
Use the existing `FinancialAnalyticsController` and dashboard view with one new read endpoint:

1. Existing `index()` still computes and renders totals.
2. New Owner-only endpoint `GET /financial/breakdown` accepts `period=daily|weekly|monthly`.
3. Endpoint builds the same date window logic used by totals and returns source rows grouped by:
   - `receipts` (revenue),
   - `paid_bills` (expense),
   - `product_expenses` (expense).
4. Dashboard adds collapsible sections under each period block:
   - “Show source rows” trigger,
   - on first open, fetch period breakdown via AJAX/fetch,
   - cache period response in-page for subsequent opens.

## Components
### Routing
1. Add `GET financial/breakdown` -> `FinancialAnalyticsController::breakdown`.

### Controller
1. Add `breakdown()`:
   - Owner guard.
   - Validate `period`.
   - Resolve `from`/`to` using existing date-window logic.
   - Return JSON payload with grouped rows and subtotals.
2. Add helper queries for period row retrieval:
   - `receiptRows($from, $to)`
   - `paidBillRows($from, $to)`
   - `productExpenseRows($from, $to)`
3. Keep existing metric methods (`sumRevenue`, `sumPaidBills`, `sumProductExpenses`) unchanged for totals.

### View (`app/Views/financial/dashboard.php`)
1. Add collapsible containers for Daily, Weekly, and Monthly sections.
2. Add loading/empty/error placeholders per section.
3. Render grouped row tables and source subtotals once data is loaded.
4. Show reconciliation summary:
   - Revenue total = receipts subtotal
   - Expense total = paid bills subtotal + product expenses subtotal

## Data Flow
For each period (daily/weekly/monthly):
1. Dashboard first renders existing totals.
2. User expands “Show source rows”.
3. Client requests `/financial/breakdown?period=<period>`.
4. Server computes the period window and fetches:
   - Receipts rows by `created_at`.
   - Paid bills rows by `bill_date` and `status = paid`.
   - Product expense rows by `expense_date`.
5. Server returns:
   - source rows,
   - per-source subtotals,
   - reconciliation totals for revenue and expenses.
6. UI renders all rows for that period inside collapsible tables.

## Response Shape (JSON)
```json
{
  "period": "daily",
  "from": "2026-04-11 00:00:00",
  "to": "2026-04-11 23:59:59",
  "sources": {
    "receipts": { "subtotal": 0, "rows": [] },
    "paid_bills": { "subtotal": 0, "rows": [] },
    "product_expenses": { "subtotal": 0, "rows": [] }
  },
  "totals": {
    "revenue": 0,
    "expenses": 0
  }
}
```

## Error Handling
1. Non-owner access follows current redirect/authorization behavior.
2. Missing/invalid `period` returns `400` JSON with explicit error message.
3. Missing source tables return empty rows and `0` subtotals (consistent with current analytics fallbacks).
4. Frontend fetch failure shows inline error within the relevant collapsible panel without hiding existing totals.

## Testing Strategy
1. Feature test: `/financial/breakdown` owner access and non-owner restriction.
2. Feature test: `period` validation and `400` JSON path.
3. Feature test: response payload shape and grouped source keys.
4. Feature test: subtotal math reflects current rules:
   - revenue from receipts,
   - expenses from paid bills + product expenses.
5. View/feature assertion: dashboard includes period drilldown triggers.

## Notes
1. Duplicate appearance of a row across daily/weekly/monthly is expected and approved.
2. Keep this iteration focused on traceability of totals; no charting or forecast additions.
