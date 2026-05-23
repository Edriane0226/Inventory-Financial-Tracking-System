# Audit Trail for Owner and Employee Actions Design

## Problem and Goal
The system currently lacks a unified audit history for critical business mutations. The goal is to add an Owner-only Audit Trail module that records both Owner and Employee actions across core functions, supports filtering by function/module, and allows exporting filtered results to CSV and PDF.

## Scope
In scope:
1. Add an immutable `audit_trails` storage model for create/update/delete actions.
2. Capture actions from Inventory, Stock-In, Stock-Out, Sales, Financial, and User Management functions.
3. Add a new Management menu entry: **Audit Trail** (Owner-only visibility).
4. Build an Owner-only Audit Trail page with function/module-based filtering.
5. Add CSV and PDF export endpoints that export the currently filtered dataset.
6. Store summary metadata and before/after JSON snapshots when available.

Out of scope:
1. Historical backfill of pre-existing records.
2. Non-Owner access to Audit Trail pages/exports.
3. Asynchronous export jobs or archival/retention policies.

## Chosen Approach
**Approach A (approved): explicit controller-level logging via a shared service**

Why:
1. Keeps logging behavior deterministic and easy to verify per endpoint.
2. Captures richer app-context metadata than database-trigger-only approaches.
3. Aligns with existing controller guard/redirect patterns in the codebase.

## Architecture
Add the following pieces:
1. `audit_trails` table and `AuditTrailModel` for append-only audit entries.
2. `AuditTrailService` that builds and writes normalized entries.
3. Owner-only `AuditTrailController` for listing/filtering and export.
4. New audit trail view under the management workflow.
5. Route wiring for page access and export endpoints.
6. Management navigation update to include **Audit Trail**.

Access model:
1. Owner and Employee actions are both logged as actors.
2. Only Owner users can view the Audit Trail page and export files.

## Components
### Database (`audit_trails`)
Proposed fields:
1. `id` (PK)
2. `actor_user_id` (FK users.id, nullable for system-safe handling)
3. `actor_role` (Owner/Employee snapshot at event time)
4. `module` (`inventory`, `stock_in`, `stock_out`, `sales`, `financial`, `user_management`)
5. `action` (`create`, `update`, `delete`)
6. `entity_type` (e.g., `product`, `stock_in`, `bill`, `expense`, `user`)
7. `entity_id` (string/int-compatible storage)
8. `summary` (human-readable event summary)
9. `before_data` (JSON, nullable)
10. `after_data` (JSON, nullable)
11. `request_method` (GET/POST/etc. snapshot when relevant)
12. `request_path` (route/path snapshot)
13. `ip_address` (nullable)
14. `created_at`

### Service (`AuditTrailService`)
Responsibilities:
1. Normalize module/action/entity metadata.
2. Encode before/after arrays to JSON with explicit null handling.
3. Build consistent summary text.
4. Persist immutable rows through `AuditTrailModel`.
5. Surface write failures explicitly to caller (no silent swallowing).

### Controller Layer
1. Existing mutation endpoints (create/update/delete) call `AuditTrailService` after successful DB mutations.
2. Update/delete flows load prior state first to capture `before_data`.
3. Create/update flows capture resulting state as `after_data`.
4. Delete flows set `after_data` to null and keep `before_data`.
5. New `AuditTrailController` provides:
   - index/listing page
   - CSV export endpoint
   - PDF export endpoint

### UI / Navigation
1. Add **Audit Trail** item under **Management** in `menu.php`.
2. Owner-only menu visibility, consistent with current management rules.
3. Audit page includes filter controls: module, action, actor, from/to date.
4. Default listing range: **All records**.

## Data Flow
### Logging flow per tracked function
1. User submits a create/update/delete request.
2. Endpoint validates input and authorization.
3. Endpoint loads `before_data` when action is update/delete.
4. Endpoint performs mutation.
5. Endpoint computes `after_data` when action is create/update.
6. Endpoint calls `AuditTrailService->log(...)`.
7. Service writes immutable row into `audit_trails`.

### Filtering and “separate per functions”
1. User selects module/action/actor/date filters on the Audit Trail page.
2. Query builder applies filters to `audit_trails`.
3. UI shows grouped-by-module context via module field/badge.
4. Export endpoints reuse the exact same filtered query.

## Export Design
### CSV export
1. Endpoint returns UTF-8 CSV with header row and filtered records.
2. Includes metadata columns and before/after JSON columns.
3. Returns a valid file even when result set is empty (headers + filter context).

### PDF export
1. Endpoint renders a printable HTML report from filtered records.
2. HTML is converted to downloadable PDF via a PHP PDF library integrated into the app.
3. Report includes selected filter summary at top for audit traceability.

## Error Handling
1. Owner authorization enforced on listing and export endpoints.
2. Invalid filter values produce explicit validation feedback.
3. Audit write failures are surfaced explicitly; they are not ignored.
4. JSON encoding failures are handled as explicit write errors.
5. Export errors return clear user-facing error flash messages.

## Testing Strategy
1. Feature access tests:
   - Owner can access Audit Trail page and exports.
   - Employee cannot access Audit Trail page and exports.
2. Feature logging tests:
   - create/update/delete actions in each scoped module create audit rows.
   - before/after snapshots are stored with expected null behavior.
3. Filter tests:
   - module/action/actor/date filters return expected subsets.
4. Export tests:
   - CSV content type, file name, and row structure.
   - PDF content type and file name for filtered exports.
5. Navigation test:
   - Management menu includes Owner-only Audit Trail item.

## Implementation Notes
1. Reuse existing `requireOwner()`-style guard behavior for consistency.
2. Keep module keys centralized (constant map) to avoid drift between logging and filters.
3. Ensure export query and page query share one filter-building path.
4. Start logging only from deployment onward; no historical backfill logic.
