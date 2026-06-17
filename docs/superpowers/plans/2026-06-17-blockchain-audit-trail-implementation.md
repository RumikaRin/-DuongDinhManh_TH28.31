# Blockchain Audit Trail Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a working blockchain audit trail foundation to the PHP bookstore website.

**Architecture:** The website remains MySQL-first. A shared PHP audit service creates privacy-safe canonical payloads, hashes them, stores local audit events, and stores receipt metadata when a real chain transaction is available. The first implementation includes smart contract source and deployment notes, but runtime business flows never fail solely because blockchain is disabled or unconfigured.

**Tech Stack:** PHP 8, mysqli, MySQL/MariaDB, Solidity, OpenZeppelin-compatible contract shape, existing PHP test runner.

---

## File Structure

- Create `htdocs/includes/BlockchainAuditService.php`: canonical payload, PII filtering, hash generation, local event insert, badge helpers.
- Modify `htdocs/database_migrate.php`: create `blockchain_audit_events` and `blockchain_receipts`.
- Modify `htdocs/database_schema.md`: document audit tables.
- Modify `htdocs/tests/security_test.php`: static regression tests for service presence, migration tables, admin route, contract, and hook calls.
- Modify `htdocs/tests/integration_test.php`: service-level tests for stable hash, PII filtering, disabled mode, and local event insert.
- Modify `htdocs/xuly_thanhtoan.php`: record `order_created`.
- Modify `htdocs/admin/order_action.php`: record `order_status_updated`.
- Modify product/sale/category/news/comment/profile mutation files: record matching audit events after successful writes.
- Create `htdocs/admin/template/blockchain_audit.php`: admin audit event list.
- Modify `htdocs/admin/index.php` and `htdocs/admin/header.php`: add route/menu entry.
- Modify `htdocs/chitietdonhang.php`: show latest order proof badge.
- Create `contracts/BookstoreAuditTrail.sol`: minimal authorized proof recorder.
- Create `docs/blockchain-audit-trail.md`: setup and deployment notes.

## Task 1: Tests For Blockchain Audit Foundation

**Files:**
- Modify: `htdocs/tests/security_test.php`
- Modify: `htdocs/tests/integration_test.php`

- [ ] **Step 1: Add static regression checks**

Add checks that require the service, migration tables, admin route, hooks, and contract artifact:

```php
$auditServicePath = dirname(__DIR__) . '/includes/BlockchainAuditService.php';
test_assert_true(is_file($auditServicePath), 'Blockchain audit service exists');
$auditServiceSource = file_get_contents($auditServicePath);
test_assert_true(str_contains($auditServiceSource, 'function blockchain_audit_record'), 'Audit service exposes blockchain_audit_record');

$migrationSource = file_get_contents(dirname(__DIR__) . '/database_migrate.php');
test_assert_true(str_contains($migrationSource, 'blockchain_audit_events'), 'Migration creates blockchain audit events table');
test_assert_true(str_contains($migrationSource, 'blockchain_receipts'), 'Migration creates blockchain receipts table');

$adminIndexSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
test_assert_true(str_contains($adminIndexSource, "case 'blockchain_audit'"), 'Admin router exposes blockchain audit page');

$checkoutSource = file_get_contents(dirname(__DIR__) . '/xuly_thanhtoan.php');
test_assert_true(str_contains($checkoutSource, 'blockchain_audit_record'), 'Checkout records blockchain audit event');

$orderActionSource = file_get_contents(dirname(__DIR__) . '/admin/order_action.php');
test_assert_true(str_contains($orderActionSource, 'blockchain_audit_record'), 'Order status updates record blockchain audit events');

test_assert_true(is_file(dirname(__DIR__, 2) . '/contracts/BookstoreAuditTrail.sol'), 'Bookstore audit smart contract exists');
```

- [ ] **Step 2: Run static tests and verify failure**

Run:

```powershell
& 'D:\xampp\php\php.exe' 'D:\f\Đồ Án tổng hợp\DuongDinhManh_TH28.31\htdocs\tests\security_test.php'
```

Expected: fails because service, migration, admin route, hooks, and contract do not exist yet.

- [ ] **Step 3: Add integration tests for service behavior**

Add tests that require `includes/BlockchainAuditService.php`, assert stable hashes, assert PII removal, and insert a local disabled event in the test database:

```php
require_once dirname(__DIR__) . '/includes/BlockchainAuditService.php';

$payloadA = ['b' => 2, 'a' => 1];
$payloadB = ['a' => 1, 'b' => 2];
test_assert_same(
    blockchain_audit_payload_hash($payloadA),
    blockchain_audit_payload_hash($payloadB),
    'Blockchain audit hash is stable regardless of key order'
);

$safePayload = blockchain_audit_sanitize_payload([
    'hoten' => 'Nguyen Van A',
    'email' => 'a@example.test',
    'sdt' => '0900000000',
    'diachi' => 'Secret address',
    'order_id' => 123,
]);
test_assert_same(false, isset($safePayload['hoten']), 'Audit sanitizer removes customer name');
test_assert_same(false, isset($safePayload['email']), 'Audit sanitizer removes email');
test_assert_same(123, $safePayload['order_id'], 'Audit sanitizer keeps non-sensitive ids');

putenv('BLOCKCHAIN_ENABLED=0');
$auditId = blockchain_audit_record($ketnoi, 'test_entity', 1, 'created', ['order_id' => 1], ['type' => 'system', 'id' => 0]);
test_assert_true($auditId > 0, 'Audit service inserts local event in disabled mode');
$auditRow = $ketnoi->query("SELECT status FROM blockchain_audit_events WHERE id = {$auditId}")->fetch_assoc();
test_assert_same('disabled', $auditRow['status'], 'Disabled blockchain mode records disabled status');
```

- [ ] **Step 4: Run integration tests and verify failure**

Run:

```powershell
& 'D:\xampp\php\php.exe' 'D:\f\Đồ Án tổng hợp\DuongDinhManh_TH28.31\htdocs\tests\run.php'
```

Expected: fails because the audit service and migration do not exist yet.

## Task 2: Audit Service And Migration

**Files:**
- Create: `htdocs/includes/BlockchainAuditService.php`
- Modify: `htdocs/database_migrate.php`
- Modify: `htdocs/database_schema.md`

- [ ] **Step 1: Implement `BlockchainAuditService.php`**

Create service functions:

```php
blockchain_audit_config(): array
blockchain_audit_enabled(): bool
blockchain_audit_canonicalize($value)
blockchain_audit_json(array $payload): string
blockchain_audit_payload_hash(array $payload): string
blockchain_audit_sanitize_payload(array $payload): array
blockchain_audit_actor(array $actor): array
blockchain_audit_record(mysqli $db, string $entityType, int $entityId, string $action, array $payload = [], array $actor = []): int
blockchain_audit_latest_for_entity(mysqli $db, string $entityType, int $entityId): ?array
blockchain_audit_status_badge(?array $event): string
```

The service must create local records only. It sets status to `disabled` when `BLOCKCHAIN_ENABLED` is not `1`, and `pending` when blockchain is enabled but no receipt has been submitted yet.

- [ ] **Step 2: Add migration tables**

Append migration queries for `blockchain_audit_events` and `blockchain_receipts` with indexes on entity, action, status, and audit event id.

- [ ] **Step 3: Document schema**

Add a "Blockchain Audit Trail" section to `database_schema.md` documenting both tables and privacy behavior.

- [ ] **Step 4: Run tests**

Run:

```powershell
& 'D:\xampp\php\php.exe' 'D:\f\Đồ Án tổng hợp\DuongDinhManh_TH28.31\htdocs\tests\run.php'
```

Expected: service tests pass; hook tests still fail until Task 3 and Task 4 are implemented.

## Task 3: Order Audit Hooks

**Files:**
- Modify: `htdocs/xuly_thanhtoan.php`
- Modify: `htdocs/admin/order_action.php`
- Modify: `htdocs/chitietdonhang.php`

- [ ] **Step 1: Record order creation**

After `xuly_thanhtoan.php` commits the order transaction and before clearing the cart result page, call:

```php
blockchain_audit_record($ketnoi, 'order', $order_id, 'order_created', [
    'order_id' => $order_id,
    'user_id' => $id_tv,
    'items' => $valid_items,
    'subtotal' => $tongtien,
    'shipping_fee' => $shipping_fee,
    'total' => $tongtien_final,
    'payment_method' => $payment_method,
], ['type' => 'user', 'id' => $id_tv]);
```

- [ ] **Step 2: Record order status update**

After `admin/order_action.php` successfully updates status, call:

```php
blockchain_audit_record($ketnoi, 'order', $id, 'order_status_updated', [
    'order_id' => $id,
    'previous_status' => $current,
    'new_status' => $status,
], ['type' => 'admin', 'id' => $_SESSION['id_tv'] ?? 0, 'name' => $_SESSION['ten_tv'] ?? 'admin']);
```

- [ ] **Step 3: Show order proof badge**

In `chitietdonhang.php`, load latest order audit event and render `blockchain_audit_status_badge($event)` in the order info area.

- [ ] **Step 4: Run tests**

Run full tests and lint touched files.

## Task 4: Catalog, Comments, News, And Profile Hooks

**Files:**
- Modify: `htdocs/admin/add_products.php`
- Modify: `htdocs/admin/template/edit_product.php`
- Modify: `htdocs/admin/product_action.php`
- Modify: `htdocs/admin/add_sale.php`
- Modify: `htdocs/admin/template/edit_sale.php`
- Modify: `htdocs/admin/sale_action.php`
- Modify: `htdocs/admin/template/categories.php`
- Modify: `htdocs/comment_action.php`
- Modify: `htdocs/admin/comment_moderate.php`
- Modify: `htdocs/admin/add_news.php`
- Modify: `htdocs/admin/edit_news.php`
- Modify: `htdocs/admin/news_action.php`
- Modify: `htdocs/views/edit_hoso.php`
- Modify: `htdocs/xuly_caidat.php`

- [ ] **Step 1: Add service require**

Each mutation file must include:

```php
require_once __DIR__ . '/includes/BlockchainAuditService.php';
```

or the correct relative path for admin files:

```php
require_once __DIR__ . '/../includes/BlockchainAuditService.php';
```

- [ ] **Step 2: Add event calls after successful writes**

Use entity/action pairs:

```php
product/product_created
product/product_updated
product/product_deleted
sale/sale_created
sale/sale_updated
sale/sale_deleted
sale/sale_converted_to_product
category/category_created
category/category_updated
category/category_deleted
comment/comment_created
comment/comment_replied
comment/comment_deleted
news/news_created
news/news_updated
news/news_deleted
user/profile_updated
user/password_changed
```

Payloads must use ids, statuses, prices, category ids, and hashes/masked values instead of raw private fields.

- [ ] **Step 3: Run static tests**

Run `security_test.php`. Expected: hook-related static tests pass.

## Task 5: Admin Audit Page And Contract Artifact

**Files:**
- Create: `htdocs/admin/template/blockchain_audit.php`
- Modify: `htdocs/admin/index.php`
- Modify: `htdocs/admin/header.php`
- Create: `contracts/BookstoreAuditTrail.sol`
- Create: `docs/blockchain-audit-trail.md`

- [ ] **Step 1: Admin page**

Create a paginated read-only audit table showing id, entity type, entity id, action, actor, status, payload hash, tx hash, block number, and created time.

- [ ] **Step 2: Route and menu**

Add `case 'blockchain_audit'` in admin router and a sidebar menu item labeled "Blockchain Audit".

- [ ] **Step 3: Contract**

Create `BookstoreAuditTrail.sol` with owner authorization and a `ProofRecorded` event.

- [ ] **Step 4: Docs**

Document how to run migration, configure env vars, deploy the contract, and explain that unconfigured mode records local proofs only.

- [ ] **Step 5: Run tests and lints**

Run full PHP tests and lint all touched PHP files.

## Task 6: Final Verification

**Files:**
- All touched files

- [ ] **Step 1: Run full suite**

Run:

```powershell
& 'D:\xampp\php\php.exe' 'D:\f\Đồ Án tổng hợp\DuongDinhManh_TH28.31\htdocs\tests\run.php'
```

Expected: `0 failed`.

- [ ] **Step 2: Run PHP lint**

Run `php -l` against every touched PHP file.

- [ ] **Step 3: Inspect git status**

Run:

```powershell
git status --short
```

Report only files touched for this blockchain work and call out pre-existing untracked/moved project state.
