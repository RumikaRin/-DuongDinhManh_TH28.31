# Project Stabilization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stabilize the bookstore application, repair the category feature, align the database with the code, and close the highest-risk correctness and security gaps.

**Architecture:** Keep the existing plain-PHP structure, but centralize shared configuration, security helpers, category utilities, and schema migration. Remove schema/data mutations from page rendering and action handlers. Add lightweight PHP tests that can run without a framework.

**Tech Stack:** PHP 8.1, MySQL/MariaDB, vanilla JavaScript, PowerShell/XAMPP

---

### Task 1: Add Shared Runtime Helpers And Regression Tests

**Files:**
- Create: `htdocs/includes/app.php`
- Create: `htdocs/tests/run.php`
- Create: `htdocs/tests/category_test.php`
- Create: `htdocs/tests/security_test.php`
- Modify: `htdocs/dbconnect.php`

- [ ] Write tests for slug generation, safe routes, CSRF validation, admin authorization, quantity limits, and shipping fees.
- [ ] Run tests and confirm they fail before helpers exist.
- [ ] Implement the shared helpers and environment-aware database configuration.
- [ ] Run tests and confirm they pass.

### Task 2: Repair Categories And Database Migration

**Files:**
- Create: `htdocs/database_migrate.php`
- Modify: `htdocs/header.php`
- Modify: `htdocs/danhmuc.php`
- Modify: `htdocs/database_create.sql`
- Modify: `htdocs/create_missing_tables.sql`

- [ ] Add schema/category migration checks.
- [ ] Remove runtime table creation and hard-coded category assignment from the header.
- [ ] Use shared slug generation and category loading.
- [ ] Render a useful empty/error state instead of a blank dropdown.
- [ ] Run migration, category tests, PHP lint, and database checks.

### Task 3: Align Comments Schema And Runtime

**Files:**
- Modify: `htdocs/comment_handler.php`
- Modify: `htdocs/comment_action.php`
- Modify: `htdocs/admin/template/comments.php`
- Modify: `htdocs/admin/comment_moderate.php`
- Modify: `htdocs/home.php`

- [ ] Remove runtime DDL.
- [ ] Standardize product type values to `sanpham` and `sale`.
- [ ] Require CSRF for comment mutations and admin moderation.
- [ ] Run schema checks and comment smoke tests.

### Task 4: Secure Admin Actions And Routing

**Files:**
- Modify: `htdocs/url.php`
- Modify: `htdocs/admin/header.php`
- Modify: `htdocs/admin/product_action.php`
- Modify: `htdocs/admin/sale_action.php`
- Modify: `htdocs/admin/order_action.php`
- Modify: `htdocs/admin/customer_action.php`
- Modify: `htdocs/admin/news_action.php`
- Modify: `htdocs/admin/template/categories.php`

- [ ] Centralize admin authorization and database connection.
- [ ] Restrict mutations to POST plus CSRF.
- [ ] Remove dead routes and unsafe debug responses.
- [ ] Verify unauthenticated actions are rejected.

### Task 5: Harden Cart And Checkout

**Files:**
- Modify: `htdocs/giohang.php`
- Modify: `htdocs/update_giohang.php`
- Modify: `htdocs/remove_giohang.php`
- Modify: `htdocs/thanhtoan.php`
- Modify: `htdocs/xuly_thanhtoan.php`

- [ ] Enforce valid cart types and bounded quantities.
- [ ] Use POST plus CSRF for cart mutations.
- [ ] Calculate product totals and approved shipping fees on the server.
- [ ] Wrap order creation in a transaction.
- [ ] Run cart helper tests and checkout static checks.

### Task 6: Final Verification And Audit Report

**Files:**
- Create: `htdocs/PROJECT_AUDIT.md`

- [ ] Run the full PHP test runner.
- [ ] Lint every PHP file.
- [ ] Run database schema checks.
- [ ] Verify category dropdown and category page in the browser.
- [ ] Document completed fixes, remaining risks, and recommended next phase.
