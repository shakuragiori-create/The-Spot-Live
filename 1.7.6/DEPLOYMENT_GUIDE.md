# The Spot Restaurant - Complete Admin Panel & POS System
## Deployment Guide & URLs

---

## 🌐 **LIVE URLS**

### Public Website
```
https://thespot-fastfood-tea.gt.tc/
```

### Admin Panel (Separate Interface)
```
https://thespot-fastfood-tea.gt.tc/panel/
```

**Login:** Use your WordPress admin credentials

---

## 📍 **ADMIN PANEL PAGES**

| Page | URL | Access |
|------|-----|--------|
| **Login** | `/panel/login/` | All |
| **POS (Point of Sale)** | `/panel/pos/` | Admin, Receptionist, Waiter |
| **Dashboard** | `/panel/` | Admin, Receptionist |
| **Orders** | `/panel/orders/` | Admin, Receptionist, Waiter |
| **Kitchen** | `/panel/kitchen/` | Admin, Kitchen Staff |
| **Tables** | `/panel/tables/` | Admin, Receptionist, Waiter |
| **Reservations** | `/panel/reservations/` | Admin, Receptionist |
| **Accounts/Reports** | `/panel/reports/` | **Admin ONLY** |
| **Menu** | `/panel/menu/` | **Admin ONLY** |
| **Staff** | `/panel/users/` | **Admin ONLY** |
| **Settings** | `/panel/settings/` | **Admin ONLY** |

---

## 🎯 **KEY FEATURES**

### 1. **POS System** (`/panel/pos/`)
- ✅ Full cart management with +/− steppers
- ✅ Category filters & live search
- ✅ Customer name & phone
- ✅ Table selection
- ✅ Discount (% or amount)
- ✅ VAT toggle (configurable %)
- ✅ Service charge toggle (configurable %)
- ✅ Multiple payment methods (Cash, eSewa, Khalti, Card, QR)
- ✅ Change calculation
- ✅ Kitchen ticket (KOT) generation
- ✅ Direct bill printing

### 2. **Accounts & Reports** (`/panel/reports/`) - **Admin Only**
- ✅ Date range filters (from/to)
- ✅ Quick presets (Today, Yesterday, Last 7 Days, This Month)
- ✅ Table-wise filter
- ✅ **KPI Cards:**
  - Net Revenue
  - Total Orders
  - Average Bill
  - Gross Sales
  - Total Discounts
  - Service Charge Collected
  - VAT Collected

- ✅ **Table-wise Earnings:** Shows orders & revenue per table
- ✅ **Payment Methods Breakdown:** Cash, eSewa, Khalti, Card, QR
- ✅ **Hourly Breakdown:** Revenue by hour of the day
- ✅ **Transaction Log:**
  - Invoice number
  - Date & Time
  - Table
  - Customer name & phone
  - Number of items
  - Total amount
  - Payment method
  - Direct link to view/print bill

- ✅ **CSV Export:** Download complete transaction history

---

## 📦 **DEPLOYMENT STEPS**

### Step 1: Upload Files via File Manager
Upload the entire plugin folder to:
```
htdocs/wp-content/plugins/restaurantpro-manager-plugin/
```

Upload the theme folder to:
```
htdocs/wp-content/themes/the-spot-theme/
```

### Step 2: Activate Plugin
1. Go to: `https://thespot-fastfood-tea.gt.tc/wp-admin/plugins.php`
2. Find **"RestaurantPro Manager"**
3. Click **Activate**

### Step 3: Flush Rewrite Rules (IMPORTANT!)
1. Go to: **WP Admin → Settings → Permalinks**
2. Click **Save Changes** (no need to change anything)
3. This activates the `/panel/` URLs

### Step 4: Import Menu
1. Go to: **WP Admin → Settings → Tools**
2. Click **"Import Menu"** button
3. Wait for it to complete (~90 items)

### Step 5: Configure Billing
1. Go to: **WP Admin → Settings → Billing & Tax**
2. Set:
   - VAT Rate: 13%
   - Service Charge: 10%
   - Invoice Prefix: INV-
   - Bill footer note

### Step 6: Clear Cache
1. Go to: **SpeedyCache** in WP Admin
2. Click **"Clear All Cache"**

### Step 7: Test
1. Visit: `https://thespot-fastfood-tea.gt.tc/panel/`
2. Login with your admin credentials
3. Go to **POS** and create a test order
4. Check **Accounts** to see the report

---

## 🔧 **TROUBLESHOOTING**

### Panel shows 404 error
**Solution:** Go to WP Admin → Settings → Permalinks → Save Changes

### Menu items not showing in POS
**Solution:** Go to Settings → Tools → Import Menu

### User deletion error
**Fixed:** Updated code to include `wp-admin/includes/user.php`

### CSS/JS not loading
**Solution:** Clear SpeedyCache and hard refresh (Ctrl+Shift+R)

### "Install App" only creates a browser shortcut on Android (Sep 2026 fix)
**Root cause:** The PWA assets were being served through virtual `/panel/` URLs.
On some shared-hosting configurations, those rewrite endpoints are unreliable for
browser install checks.
**Fixed:** The panel now uses the real static `/manifest.webmanifest` and `/sw.js`
files at the site root, while the manifest scope/start URL remain `/panel/`. The
service worker is registered for the `/panel/` scope and panel data is deliberately
kept network-only so POS/Kitchen status changes are never served from stale cache.
The duplicate service-worker registration was also removed.
**After updating:** Clear the browser/site data or uninstall any old shortcut, then
open `/panel/` while logged in. On supported Android Chrome versions, the browser
should offer **Install app** rather than only **Create shortcut**.
**Self-check:** open `/panel/pwa-check` on the phone. It now checks the static
manifest, service worker, icons, and HTTPS status. If anything is red, that item
needs attention on the hosting side.

### Cancelled orders still appearing in Kitchen (Sep 2026 fix)
**Fixed:** Cancelling an order now immediately changes every related KOT to
`cancelled`, releases its table, and prevents any stale kitchen action from
resurrecting the order as Ready. The Kitchen page and Kitchen AJAX feed also filter
out cancelled orders, including older/stale KOT records. The cancelled KOT is
retained in the database for history/audit but is no longer an active kitchen ticket.

### Order creation intermittently fails ("Security check failed" / "Permission denied") (Sep 2026 fix)
**Root cause:** Two realistic failure modes, both silent and easy to mistake
for "orders are broken":
1. The security token (nonce) used by POS/Orders defaults to expiring in
   about 24 hours — a POS tablet left open overnight will start rejecting
   "Charge"/"Send to Kitchen" with a generic error.
2. If a role reset (core update, another plugin, etc.) ever strips the
   restaurant capabilities from a staff account, orders fail with
   "Permission denied" and no obvious explanation.
**Fixed:** Token lifetime extended to 48 hours, staff capabilities now
self-heal on every page load, the POS auto-reloads with a clear message on a
stale-token failure instead of getting stuck, and any remaining save failure
now shows the actual database error instead of a dead end — so if it ever
happens again, what's wrong is visible immediately.

### Backup SQL files were publicly downloadable (Sep 2026 fix)
`wp-content/uploads/restaurantpro-backups/` sits inside the public uploads
folder and contained full database exports (including hashed passwords) with
no access restriction — anyone who found/guessed a filename could download
your database. An `.htaccess` now blocks direct access to that folder; the
in-panel **Backup → Download Backup Now** button is unaffected since it
streams the file through PHP rather than linking to it directly.

### Clearing old orders & accounts to start fresh (Sep 2026)
A ready-to-run script has been added at
`wp-content/uploads/restaurantpro-backups/RESET-orders-and-accounts.sql`.
It empties past orders, order items, kitchen tickets, ledger/account entries,
salary records, cash shift closings, and the related activity/notification
logs — while leaving your menu, staff accounts, tables, reservations,
inventory, and settings untouched. Run it once in phpMyAdmin against your
live database (see the comments at the top of the file for exact steps —
uploading these files alone does not run it for you).

---

## 👥 **USER ROLES & ACCESS**

| Role | Can Access |
|------|-----------|
| **Administrator** | Everything |
| **Restaurant Admin** | Everything |
| **Manager / Cashier** | Dashboard, POS, Orders, Tables, Reservations |
| **Kitchen Staff** | Kitchen only |
| **Waiter** | POS, Orders, Tables |

---

## 💾 **DATABASE TABLES**

All data is stored in these tables:
- `wp_rp_orders` - All orders with invoice, customer, payment details
- `wp_rp_order_items` - Line items with name snapshot
- `wp_rp_tables` - Table management
- `wp_rp_kot` - Kitchen tickets
- `wp_rp_reservations` - Table reservations
- `wp_rp_messages` - Contact form submissions
- `wp_rp_settings` - Plugin settings

---

## 📊 **ACCOUNTS FEATURES**

### Daily Reports
- View any date range
- See total revenue, orders, average bill
- Filter by specific table
- Export to CSV

### Transaction History
Every completed order records:
- Invoice number (auto-generated: INV-000001)
- Date & Time
- Table number
- Customer name & phone
- All line items with quantities & prices
- Subtotal
- Discount applied
- Service charge
- VAT amount
- Grand total
- Payment method
- Amount paid & change given

### CSV Export
Downloads all transactions with complete details for accounting software.

---

## 🎨 **FRONTEND THEME**

The public website (`thespot-fastfood-tea.gt.tc`) is a fully animated single-page site with:
- Dynamic menu from database
- Working reservation form
- Contact form (saves to Messages)
- Photo gallery
- Customer testimonials
- Opening hours
- Social media links

All editable from WordPress admin when plugin is active.

---

## 📱 **RESPONSIVE**

Both the panel and public site work perfectly on:
- Desktop
- Tablet
- Mobile

---

## ✅ **PROJECT STATUS: COMPLETE**

All requested features have been implemented:
- ✅ Separate admin panel (not in WP dashboard)
- ✅ Full POS system with cart, discounts, VAT, service
- ✅ Accounts page with daily/table-wise reports
- ✅ Transaction log with customer names
- ✅ CSV export
- ✅ Bill printing (80mm thermal + A4)
- ✅ All data recorded for future reference
- ✅ User deletion issue fixed

---

## 📞 **SUPPORT**

If you encounter any issues:
1. Check the troubleshooting section above
2. Verify plugin is activated
3. Ensure permalinks are flushed
4. Clear SpeedyCache

---

**Last Updated:** September 1, 2026
**Version:** 1.1.0


## PWA hosting fix (InfinityFree)

If the host returns HTTP 403 for direct `.webmanifest` or `.js` files, the app now avoids those extensions entirely. The panel serves browser-critical PWA resources through these WordPress endpoints:

- `/panel/pwa-manifest` — returns `application/manifest+json`
- `/panel/pwa-sw` — returns `application/javascript` and controls `/panel/`
- `/panel/pwa-check` — verifies the live endpoints

After deployment:
1. Replace the site files with this package.
2. In WordPress, go to **Settings → Permalinks** and click **Save Changes** once.
3. Open `https://thespot-fastfood-tea.gt.tc/panel/pwa-check`.
4. The Manifest and Service Worker checks should both be HTTP 200.
5. On Android Chrome, remove the old shortcut, open `/panel/`, refresh, and use Chrome's **Install app** option when offered.

Do not use the old root `/manifest.webmanifest` or `/sw.js` URLs for the PWA; the new extensionless panel endpoints are intentional to avoid the host's 403 restriction.
