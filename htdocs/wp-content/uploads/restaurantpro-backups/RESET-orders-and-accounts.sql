-- =============================================================
-- The Spot — Reset Orders & Accounts History
-- =============================================================
-- What this does:
--   Empties every table that stores past orders, bills, kitchen
--   tickets, ledger/account entries, staff salary records, cash
--   shift closings, and the activity/notification logs that
--   reference them — so you start today with a clean slate.
--
-- What this does NOT touch (left exactly as-is):
--   Menu items & categories, staff/user accounts and roles,
--   tables list, reservations, inventory items & stock,
--   expenses, contact messages, and all plugin/site settings.
--
-- How to run it:
--   1. Log in to cPanel → phpMyAdmin (or your hosting DB tool).
--   2. Select your WordPress database (the one this site uses).
--   3. Open the "SQL" tab, paste this whole file in, and click Go.
--   4. This only needs to be run ONCE, against your LIVE database.
--      Re-uploading this backup zip's files does not run this SQL
--      for you — the files and the database are separate; this
--      script is what actually clears the data.
--
-- Table prefix used below (wpkr_) matches this site's current
-- database. If you ever change prefixes, update it here too.
-- =============================================================

TRUNCATE TABLE `wpkr_rp_order_items`;
TRUNCATE TABLE `wpkr_rp_kot`;
TRUNCATE TABLE `wpkr_rp_orders`;

TRUNCATE TABLE `wpkr_rp_ledger_entries`;
TRUNCATE TABLE `wpkr_rp_ledger_accounts`;
TRUNCATE TABLE `wpkr_rp_salary_records`;
TRUNCATE TABLE `wpkr_rp_cash_shifts`;

TRUNCATE TABLE `wpkr_rp_activity_log`;
TRUNCATE TABLE `wpkr_rp_notifications`;

-- Invoice numbers are derived from the order row id (see
-- RP_Billing::invoice_no), so the TRUNCATE above already resets
-- your next invoice back to INV-000001 — no extra step needed.
