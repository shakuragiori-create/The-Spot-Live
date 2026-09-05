# The Spot v2.0.0 Changelog

## Release Date: 2026-09-05

### Bug Fixes

1. **Mobile Order Submission (Critical)** — `#mobileSubmitOrder` button was not connected to the order submission function. Mobile order bar (count/total) was never updated. Both now share a single `submitNewOrder()` function.
   - Files: `panel/js/orders.js`, `panel/views/order-new.php`

2. **Dashboard Statistics Accuracy** — "Orders Today" counted cancelled orders. "Pending KOT" counted KOTs whose parent order was cancelled. Both now exclude cancelled/canceled status.
   - Files: `panel/views/dashboard.php`, `ajax/class-rp-ajax-dashboard.php`

3. **Staff Chat Mobile** — Chat used full-page reload on mobile (<800px), losing thread state. Now uses AJAX navigation on all screen sizes. Auto-detects mobile thread view via `?with=` parameter.
   - Files: `panel/views/messages.php`

### New Features

4. **Comprehensive Nepali Translation** — Expanded from ~70 terms to 280+ terms covering all UI: navigation, orders, kitchen, POS, reports, chat, settings, inventory, expenses, cash shifts, activity log, system health, error messages, and more. Loaded as external `translations.js` (only when language is `ne`). MutationObserver handles dynamically added content.
   - Files: `panel/js/translations.js` (new), `panel/views/partials/header.php`

5. **Super Admin Role** — New `rp_super_admin` WordPress role with all capabilities including `rp_manage_permissions` and `rp_view_system_health`. Full panel access identical to admin plus permission management.
   - Files: `includes/class-rp-roles.php`, `panel/class-rp-panel.php`, `panel/views/users.php`

6. **Granular Menu Permissions** — Per-staff page access control. Admins can toggle which panel pages (POS, Kitchen, Orders, etc.) each non-admin staff member can access. Stored in `rp_staff_permissions` table. Both frontend (nav hiding) and backend (page access denial) enforced.
   - Files: `includes/class-rp-database.php`, `panel/class-rp-panel.php`, `panel/views/users.php`

7. **System Health Diagnostics** — New `/panel/system-health` page showing: WordPress/PHP timezone, current time in multiple zones, database time, Asia/Kathmandu check, WordPress/PHP/plugin versions, DB schema version, HTTPS status, nonce system health, file permissions, and table existence checks.
   - Files: `panel/views/system-health.php` (new), `panel/class-rp-panel.php`

8. **Enhanced Activity Logging** — Added logging for: order creation, order status changes, payment receipts, KOT status changes, POS order creation, user login/logout, permission updates, inventory/expense/shift/backup operations (already existed).
   - Files: `ajax/class-rp-ajax-orders.php`, `ajax/class-rp-ajax-kitchen.php`, `ajax/class-rp-ajax-pos.php`, `panel/class-rp-panel.php`

### Improvements

9. **UI Modernization** — Replaced all emoji icons (reports, orders, kitchen, menu, users, reservations, POS) with SVG icons via `rp_panel_icon()`. Professional, consistent icon set across all views.
   - Files: `panel/views/reports.php`, `panel/views/orders.php`, `panel/views/order-view.php`, `panel/views/kitchen.php`, `panel/views/menu.php`, `panel/views/users.php`, `panel/views/reservations.php`, `panel/views/pos.php`

10. **Cache Safety** — Plugin version bumped to `2.0.0`. All CSS/JS assets use `?v=2.0.0` query strings. Service worker cache name updated to `the-spot-panel-v2-0-0`. SW registration uses dynamic version from PHP.
    - Files: `restaurantpro-manager.php`, `panel/class-rp-panel.php`, `panel/views/partials/header.php`

11. **Database Migration** — DB version bumped to `2.0.0`. New `rp_staff_permissions` table created via idempotent `dbDelta()`. Existing tables preserved. Automatic upgrade on plugin load via `check_db_upgrade()`.
    - Files: `restaurantpro-manager.php`, `includes/class-rp-database.php`

### Security

12. **Security Audit Verified** — All 15 AJAX endpoints confirmed to have:
    - Nonce verification (`wp_verify_nonce`)
    - Capability checks (`current_user_can`)
    - Input sanitization (`absint`, `sanitize_text_field`, `sanitize_textarea_field`)
    - Server-side financial validation (`RP_Billing::calculate()`)
    - No SQL injection vectors (all queries use `$wpdb->prepare()`)

### Architecture Preserved

- WordPress custom plugin architecture maintained
- Existing folder structure unchanged
- All custom DB tables preserved
- All existing roles preserved alongside new Super Admin
- Cancellation cascade (order -> KOT -> table release) unchanged
- Server-side billing validation unchanged
