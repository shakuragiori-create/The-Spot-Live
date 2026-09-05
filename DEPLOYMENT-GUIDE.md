# The Spot v2.0.0 Deployment Guide

## Prerequisites
- WordPress 6.x
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Timezone set to `Asia/Kathmandu` in WordPress Settings > General

## Deployment Steps

### 1. Backup Current Installation
```bash
# Backup the database
wp db export backup-pre-2.0.sql

# Backup uploads directory (not included in ZIP)
tar -czf uploads-backup.tar.gz htdocs/wp-content/uploads/
```

### 2. Extract ZIP
```bash
# Extract the-spot-2.0.0.zip
unzip the-spot-2.0.0.zip

# The ZIP contains the full htdocs/ directory structure
# IMPORTANT: wp-config.php is excluded from ZIP — keep your existing one
```

### 3. Deploy Files
```bash
# Option A: Wipe and replace (recommended for clean deployment)
# Back up wp-config.php and uploads first!
cp htdocs/wp-config.php /tmp/wp-config-backup.php
cp -r htdocs/wp-content/uploads/ /tmp/uploads-backup/

# Replace all files
rsync -av --delete the-spot-2.0.0/htdocs/ /path/to/htdocs/

# Restore wp-config.php and uploads
cp /tmp/wp-config-backup.php htdocs/wp-config.php
cp -r /tmp/uploads-backup/ htdocs/wp-content/uploads/

# Option B: Overlay (preserves existing files, adds new ones)
rsync -av the-spot-2.0.0/htdocs/ /path/to/htdocs/
```

### 4. Post-Deployment
1. Visit `/panel/` — the DB upgrade runs automatically (creates `rp_staff_permissions` table, bumps DB version to 2.0.0)
2. Visit WordPress Admin > Settings > Permalinks and click "Save Changes" to flush rewrite rules (needed for new `/panel/system-health` route)
3. Verify System Health at `/panel/system-health`:
   - WordPress Timezone should show `Asia/Kathmandu`
   - All database tables should show green checkmarks
   - Plugin Version should show `2.0.0`

### 5. Verify Features
- [ ] Login works
- [ ] Dashboard shows correct stats (no cancelled orders in counts)
- [ ] Mobile order creation works (test on phone)
- [ ] Chat works on mobile (navigate contacts, send message)
- [ ] Nepali language switch works (dropdown in topbar)
- [ ] Super Admin role visible in Staff > Add Staff
- [ ] Menu Permissions section visible in Staff page
- [ ] System Health page accessible from sidebar
- [ ] POS order creation works
- [ ] Kitchen display excludes cancelled orders

## Rollback
If issues arise, restore from backup:
```bash
# Restore database
wp db import backup-pre-2.0.sql

# Restore files
# (use your pre-upgrade file backup)
```

## New Routes
- `/panel/system-health` — System diagnostics (admin/superadmin only)

## New Database Table
- `{prefix}_rp_staff_permissions` — Per-staff page access permissions
