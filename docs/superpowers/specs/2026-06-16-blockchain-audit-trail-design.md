# Blockchain Audit Trail Design

Date: 2026-06-16

## Goal

Add a blockchain audit trail to the existing PHP bookstore website without replacing the current MySQL/MariaDB system of record. The website continues to create and manage users, products, orders, comments, categories, and news in the database. Blockchain is added as an immutable proof layer for important business events.

The first implementation must be safe for a school project and for local development:

- Checkout and admin actions must keep working when blockchain is disabled.
- Blockchain failures must not roll back successful MySQL transactions.
- Personal data must not be written to a public chain.
- Audit data must be explainable from the admin UI.

## Current System Context

The application is a PHP and MySQL/MariaDB bookstore site under `htdocs/`.

Key flows:

- `index.php` starts the public route shell and includes views through `url.php`.
- `xuly_thanhtoan.php` creates `donhang` and `donhang_chitiet` records inside a transaction.
- `admin/order_action.php` updates order status.
- Product, sale, category, comment, customer, news, and account operations are handled by admin action files and admin templates.
- `includes/app.php` already centralizes shared helpers such as database config, CSRF, admin guards, quantity normalization, shipping fee validation, JSON responses, and cache helpers.

This design should follow those existing patterns by adding a focused audit helper/service rather than scattering hashing and receipt logic across unrelated files.

## Scope

Audit proofs will be generated for all important business events:

- Orders: create order, update status, cancel order, mark delivered.
- Products: create, update, delete normal products.
- Sales products: create, update, delete, convert sale item to normal product.
- Categories: create, update, delete.
- Comments: create, admin reply, delete or moderation action.
- News: create, update, delete.
- User/account changes: profile updates and password changes as privacy-preserving hashes only.

The implementation will not add crypto payments, customer wallet login, token rewards, or NFT ownership in the first phase. Those can be layered on later, but they are intentionally outside this design.

## Non-Goals

- Do not store names, phone numbers, email addresses, physical addresses, comment bodies, passwords, or raw order item details on-chain.
- Do not make blockchain confirmation a prerequisite for successful checkout.
- Do not require users to install MetaMask or connect a wallet to buy books.
- Do not change the existing database into a blockchain-backed database.
- Do not add broad refactors unrelated to audit capture.

## Architecture

### PHP Audit Service

Add a new service under `htdocs/includes/`, for example `BlockchainAuditService.php`.

Responsibilities:

- Build canonical audit payloads from event metadata.
- Remove or hash sensitive fields before storage and before blockchain submission.
- Produce stable hashes from canonical JSON.
- Insert local audit records in MySQL.
- Optionally submit proof hashes to the blockchain when `BLOCKCHAIN_ENABLED=1`.
- Mark local records as `pending`, `confirmed`, `failed`, or `disabled`.
- Provide retry-safe behavior for failed submissions.

The service should expose a small interface:

```php
recordAuditEvent(mysqli $db, string $entityType, int $entityId, string $action, array $payload, array $actor = []): int
markSubmitted(mysqli $db, int $eventId, array $receipt): void
markFailed(mysqli $db, int $eventId, string $message): void
```

The implementation can begin with local proof creation only, then add blockchain submission once the contract and RPC settings are available.

### Database Tables

Add a migration section to create local audit tables.

`blockchain_audit_events`:

- `id`
- `entity_type`
- `entity_id`
- `action`
- `actor_type`
- `actor_id`
- `payload_hash`
- `payload_json`
- `pii_policy`
- `status`
- `error_message`
- `created_at`
- `updated_at`

`blockchain_receipts`:

- `id`
- `audit_event_id`
- `network`
- `chain_id`
- `contract_address`
- `tx_hash`
- `block_number`
- `block_hash`
- `confirmed_at`
- `created_at`

The payload JSON is stored locally so the application can recompute the hash and prove the blockchain hash matches a known local event. Sensitive values inside payload JSON must be omitted, masked, or hashed.

### Smart Contract

Create a minimal Solidity contract named `BookstoreAuditTrail`.

Responsibilities:

- Accept proof records from an authorized backend/admin wallet.
- Store or emit the proof hash with enough indexed data for lookup.
- Prevent arbitrary public addresses from writing fake bookstore proofs.

Suggested contract shape:

```solidity
event ProofRecorded(
    bytes32 indexed payloadHash,
    string entityType,
    uint256 indexed entityId,
    string action,
    address indexed recorder,
    uint256 recordedAt
);

function recordProof(
    bytes32 payloadHash,
    string calldata entityType,
    uint256 entityId,
    string calldata action
) external onlyAuthorized;
```

Use OpenZeppelin `Ownable` or `AccessControl` for write authorization. `Ownable` is enough for a single deployment/admin wallet. `AccessControl` is better if there will be multiple recorder wallets.

### Configuration

Add environment-driven settings:

- `BLOCKCHAIN_ENABLED=0|1`
- `BLOCKCHAIN_NETWORK`
- `BLOCKCHAIN_CHAIN_ID`
- `BLOCKCHAIN_RPC_URL`
- `BLOCKCHAIN_CONTRACT_ADDRESS`
- `BLOCKCHAIN_RECORDER_PRIVATE_KEY`
- `BLOCKCHAIN_EXPLORER_BASE_URL`

The app must behave safely when these are missing. Missing or disabled settings should produce local `disabled` or `pending` records, not fatal checkout/admin errors.

## Data Flow

### Order Creation

1. Existing checkout validates CSRF, session, cart items, shipping fee, and customer data.
2. Existing transaction creates `donhang` and `donhang_chitiet`.
3. After MySQL commit succeeds, the audit service builds an `order_created` payload.
4. Payload includes non-sensitive data such as order id, user id, item ids/types/quantities, total, shipping fee, payment method, and timestamp.
5. Payload excludes raw name, phone, email, and address.
6. The audit service stores a local audit event and payload hash.
7. If blockchain is enabled, it submits the proof hash and stores the transaction receipt.
8. Checkout success page can show "proof pending" or "proof recorded" without blocking the order.

### Order Status Update

1. Admin updates status through `admin/order_action.php`.
2. After the status update succeeds, the audit service records `order_status_updated`.
3. Payload includes order id, previous status, new status, admin actor id/name hash, and timestamp.
4. The admin order list and order detail page can show the latest proof status.

### Catalog, Category, Comment, News, and User Changes

For each successful mutation:

1. Capture a before/after summary where useful.
2. Remove or hash sensitive fields.
3. Record local audit event.
4. Submit blockchain proof if enabled.
5. Never fail the original business action solely because blockchain submission failed.

## UI Changes

### Customer-Facing

Order detail page:

- Show a compact verification badge.
- Show proof status: pending, confirmed, failed, or disabled.
- If confirmed, show transaction hash as a link using `BLOCKCHAIN_EXPLORER_BASE_URL`.

Product and sale detail pages:

- Optionally show "Catalog proof available" when the latest product audit has a confirmed receipt.

### Admin-Facing

Add an admin route, for example `route=blockchain_audit`.

The page should show:

- Recent audit events.
- Entity type, entity id, action, actor, payload hash.
- Status badge.
- Transaction hash and block number when available.
- Retry action for failed or pending events when configuration allows it.

Admin order, product, sale, category, comment, and news screens can show small status indicators, but the dedicated audit page is the primary review surface.

## Error Handling

- MySQL business transaction failure: no audit event should be created.
- Audit local insert failure: log server error, but avoid exposing sensitive details to users.
- Blockchain disabled: create local event with `disabled` or `pending_local` status.
- RPC failure: mark event `failed` with sanitized error message.
- Transaction submitted but not confirmed: mark `pending`.
- Duplicate retry: reuse the same audit event and payload hash; do not create a new proof for the same mutation unless the mutation actually happened again.

## Privacy Rules

Never put the following fields on-chain:

- Customer name.
- Phone number.
- Email.
- Address.
- Password hash or password-derived values.
- Raw comment body.
- Raw private admin notes.

Allowed on-chain:

- Payload hash.
- Entity type.
- Entity id.
- Action name.
- Recorder address.
- Timestamp emitted by chain.

Allowed in local payload JSON:

- Non-sensitive ids and status changes.
- Monetary totals.
- Item ids/types/quantities.
- Hashed or masked actor/customer identifiers.
- Hashes of private text if the application needs tamper evidence without disclosing content.

## Testing Strategy

Add focused tests for:

- Stable canonical JSON hashing.
- PII stripping and masking.
- Audit event creation after checkout.
- Audit event creation after admin order status update.
- Audit capture for product, sale, category, comment, news, and profile mutations.
- Disabled blockchain mode.
- Failed blockchain submission mode.
- Receipt display formatting.

Existing regression tests should continue to run. Because PHP CLI is not currently available in this environment, implementation verification may require installing or using the local PHP runtime already used by the web server.

## Rollout Plan

1. Add database tables and local audit service.
2. Wire local audit events into checkout and order status updates.
3. Add admin audit page with local proof display.
4. Wire product, sale, category, comment, news, and user/profile mutation events.
5. Add smart contract and deployment notes.
6. Add optional blockchain submission and receipt storage.
7. Add retry controls and explorer links.

This order keeps the website useful even before a live chain is configured.

## Acceptance Criteria

- Every scoped mutation creates a local audit event after the mutation succeeds.
- No scoped mutation writes raw PII to blockchain payloads or smart contract arguments.
- Checkout still succeeds when blockchain is disabled or unreachable.
- Admin can inspect audit event status from the dashboard.
- Confirmed blockchain receipts show transaction hash and block data.
- Tests cover hashing, PII filtering, disabled mode, and at least the checkout/order status audit flows.
