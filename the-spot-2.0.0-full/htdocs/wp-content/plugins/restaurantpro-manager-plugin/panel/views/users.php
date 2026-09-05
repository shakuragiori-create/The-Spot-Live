<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! in_array( $role, [ 'admin', 'superadmin' ], true ) ) { echo '<p class="text-danger">Access denied.</p>'; return; }

global $wpdb;
$rp_roles = [
    'rp_super_admin' => 'Super Admin',
    'rp_restaurant_admin' => 'Admin',
    'rp_manager' => 'Manager',
    'rp_cashier' => 'Cashier',
    'rp_kitchen_staff' => 'Kitchen',
    'rp_waiter' => 'Waiter',
];
$staff_users = get_users([ 'role__in' => array_keys( $rp_roles ), 'orderby' => 'display_name' ]);
$leave_table = $wpdb->prefix . 'rp_staff_leave';
$leave_records = $wpdb->get_results( "SELECT l.*, u.display_name FROM {$leave_table} l LEFT JOIN {$wpdb->users} u ON u.ID=l.staff_user_id ORDER BY l.leave_from DESC, l.id DESC LIMIT 100" );
?>

<h1 class="page-title">Staff Management</h1>

<div class="p-card" style="background:linear-gradient(135deg,#111827,#1f2937);color:#fff">
    <h3 class="p-card-title mb-12" style="color:#fff">Staff & Security</h3>
    <p style="margin:0;color:#d1d5db;font-size:.88rem">Admin can create staff accounts, change roles, set salary and staff details, reset passwords, and track leave. For security, existing WordPress passwords are never displayed in plain text; use <strong>Set New Password</strong> to change one.</p>
</div>

<div class="p-card">
    <h3 class="p-card-title mb-12">Add Staff Member</h3>
    <form method="post">
        <?php wp_nonce_field( 'rp_users_action' ); ?>
        <input type="hidden" name="rp_user_action" value="add">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
            <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-input" required placeholder="staff_username"></div>
            <div class="form-group"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-input" value="Aakash Dhungana"></div>
            <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input" required minlength="6"></div>
            <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-select" required><option value="">Select role...</option><?php foreach($rp_roles as $k=>$v): ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Phone</label><input name="phone" class="form-input"></div>
            <div class="form-group"><label class="form-label">Email</label><input name="email" type="email" class="form-input"></div>
            <div class="form-group"><label class="form-label">Designation</label><input name="designation" class="form-input" placeholder="Cashier / Waiter / Chef"></div>
            <div class="form-group"><label class="form-label">Monthly Salary (Rs.)</label><input name="salary" type="number" step="0.01" class="form-input" value="0"></div>
            <div class="form-group"><label class="form-label">Joining Date</label><input name="joining_date" type="date" class="form-input"></div>
            <div class="form-group"><label class="form-label">Emergency Contact</label><input name="emergency_contact" class="form-input"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Address</label><input name="address" class="form-input"></div>
        </div>
        <button type="submit" class="btn btn-primary">Add Staff</button>
    </form>
</div>

<div class="p-card">
    <h3 class="p-card-title mb-12">Staff (<?php echo count( $staff_users ); ?>)</h3>
    <?php foreach ( $staff_users as $user ) :
        $user_role_key = $user->roles[0] ?? '';
        $role_label = $rp_roles[ $user_role_key ] ?? ucfirst( $user_role_key );
        $salary = (float)get_user_meta($user->ID,'rp_salary',true);
    ?>
        <details style="border-bottom:1px solid #eee;padding:12px 0">
            <summary style="display:flex;align-items:center;gap:12px;cursor:pointer;list-style:none">
                <span class="topbar-avatar" style="width:38px;height:38px;font-size:.85rem;overflow:hidden"><?php $staff_pic=get_user_meta($user->ID,'rp_profile_picture',true); if($staff_pic): ?><img src="<?php echo esc_url($staff_pic); ?>" alt="" style="width:100%;height:100%;object-fit:cover"><?php else: echo esc_html( mb_substr( $user->display_name ?: 'A', 0, 1 ) ); endif; ?></span>
                <div style="flex:1;min-width:0"><div class="fw-700" style="font-size:.88rem"><?php echo esc_html( $user->display_name ); ?></div><div class="text-sm text-muted">@<?php echo esc_html( $user->user_login ); ?> • Rs.<?php echo number_format($salary,0); ?>/month</div></div>
                <span class="badge badge-<?php echo esc_attr( $role_label === 'Admin' ? 'new' : ( $role_label === 'Kitchen' ? 'preparing' : 'accepted' ) ); ?>"><?php echo esc_html( $role_label ); ?></span>
            </summary>
            <div style="margin-top:14px;padding:14px;background:#fafafa;border-radius:10px">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'rp_users_action' ); ?><input type="hidden" name="rp_user_action" value="update"><input type="hidden" name="user_id" value="<?php echo (int)$user->ID; ?>">
                    <div class="profile-picture-editor">
                        <?php $profile_pic=get_user_meta($user->ID,'rp_profile_picture',true); $profile_initial=mb_strtoupper(mb_substr($user->display_name ?: 'A',0,1)); ?>
                        <div class="profile-picture-preview"><?php if($profile_pic): ?><img src="<?php echo esc_url($profile_pic); ?>" alt=""><?php else: ?><span><?php echo esc_html($profile_initial); ?></span><?php endif; ?></div>
                        <div><strong>Profile picture</strong><p class="text-sm text-muted">Upload JPG, PNG or WebP. The image is stored directly for the panel profile.</p><input type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" class="form-input"><label class="profile-remove"><input type="checkbox" name="remove_profile_picture" value="1"> Remove current picture</label></div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px">
                        <div class="form-group"><label class="form-label">Display Name</label><input name="display_name" class="form-input" value="<?php echo esc_attr($user->display_name); ?>"></div>
                        <div class="form-group"><label class="form-label">Email</label><input name="email" type="email" class="form-input" value="<?php echo esc_attr($user->user_email); ?>"></div>
                        <div class="form-group"><label class="form-label">Phone</label><input name="phone" class="form-input" value="<?php echo esc_attr(get_user_meta($user->ID,'rp_phone',true)); ?>"></div>
                        <div class="form-group"><label class="form-label">Designation</label><input name="designation" class="form-input" value="<?php echo esc_attr(get_user_meta($user->ID,'rp_designation',true)); ?>"></div>
                        <div class="form-group"><label class="form-label">Monthly Salary</label><input name="salary" type="number" step="0.01" class="form-input" value="<?php echo esc_attr($salary); ?>"></div>
                        <div class="form-group"><label class="form-label">Joining Date</label><input name="joining_date" type="date" class="form-input" value="<?php echo esc_attr(get_user_meta($user->ID,'rp_joining_date',true)); ?>"></div>
                        <div class="form-group"><label class="form-label">Emergency Contact</label><input name="emergency_contact" class="form-input" value="<?php echo esc_attr(get_user_meta($user->ID,'rp_emergency_contact',true)); ?>"></div>
                        <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-select"><?php foreach($rp_roles as $k=>$v): ?><option value="<?php echo esc_attr($k); ?>" <?php selected($user_role_key,$k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?></select></div>
                        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Address</label><input name="address" class="form-input" value="<?php echo esc_attr(get_user_meta($user->ID,'rp_address',true)); ?>"></div>
                        <div class="form-group"><label class="form-label">Set New Password</label><input name="new_password" type="password" minlength="6" class="form-input" placeholder="Leave blank to keep current"></div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-primary" type="submit">Save Staff Details</button></div>
                </form>

                <div style="margin-top:14px;padding-top:14px;border-top:1px solid #e5e7eb">
                    <h4 style="margin:0 0 10px">Record Leave</h4>
                    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end">
                        <?php wp_nonce_field( 'rp_users_action' ); ?><input type="hidden" name="rp_user_action" value="leave"><input type="hidden" name="user_id" value="<?php echo (int)$user->ID; ?>">
                        <div class="form-group"><label class="form-label">From</label><input name="leave_from" type="date" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">To</label><input name="leave_to" type="date" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Type</label><select name="leave_type" class="form-select"><option value="casual">Casual</option><option value="sick">Sick</option><option value="annual">Annual</option><option value="unpaid">Unpaid</option><option value="other">Other</option></select></div>
                        <div class="form-group"><label class="form-label">Status</label><select name="leave_status" class="form-select"><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option></select></div>
                        <div class="form-group"><label class="form-label">Reason</label><input name="leave_reason" class="form-input"></div>
                        <button class="btn btn-secondary" type="submit">Save Leave</button>
                    </form>
                </div>
                <?php if ( $user->ID !== get_current_user_id() ) : ?><form method="post" style="margin-top:10px"><?php wp_nonce_field('rp_users_action'); ?><input type="hidden" name="rp_user_action" value="delete"><input type="hidden" name="user_id" value="<?php echo (int)$user->ID; ?>"><button class="btn btn-sm btn-danger" onclick="return confirm('Remove this staff member?')">Remove Staff</button></form><?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
    <?php if ( ! $staff_users ) : ?><div class="empty-state"><div class="empty-icon"><?php echo rp_panel_icon('users'); ?></div><p>No staff members added yet</p></div><?php endif; ?>
</div>

<div class="p-card mt-16">
    <h3 class="p-card-title mb-12" data-i18n="Menu Permissions">Menu Permissions</h3>
    <p class="text-sm text-muted" style="margin-bottom:16px">Control which panel pages each staff member can access. Admins and Super Admins always have full access.</p>
    <?php
    $perm_table = $wpdb->prefix . 'rp_staff_permissions';
    $perm_pages = [
        'pos' => 'POS',
        'dashboard' => 'Dashboard',
        'orders' => 'Orders',
        'kitchen' => 'Kitchen',
        'tables' => 'Tables',
        'reservations' => 'Reservations',
        'messages' => 'Messages',
        'reports' => 'Reports',
        'accounts' => 'Accounts',
        'menu' => 'Menu',
        'gallery' => 'Gallery',
        'inventory' => 'Inventory',
        'expenses' => 'Expenses',
        'shifts' => 'Cash Shift',
    ];
    $non_admin_roles = [ 'rp_manager', 'rp_cashier', 'rp_kitchen_staff', 'rp_waiter' ];
    $perm_users = get_users([ 'role__in' => $non_admin_roles, 'orderby' => 'display_name' ]);
    foreach ( $perm_users as $pu ) :
        $existing = $wpdb->get_results( $wpdb->prepare( "SELECT page_slug, allowed FROM {$perm_table} WHERE user_id = %d", $pu->ID ), ARRAY_A );
        $user_perms = [];
        foreach ( $existing as $ep ) $user_perms[ $ep['page_slug'] ] = (int) $ep['allowed'];
        $has_custom = ! empty( $existing );
    ?>
    <details style="border-bottom:1px solid #eee;padding:10px 0">
        <summary style="cursor:pointer;display:flex;align-items:center;gap:10px;list-style:none">
            <strong><?php echo esc_html( $pu->display_name ); ?></strong>
            <span class="text-sm text-muted">@<?php echo esc_html( $pu->user_login ); ?></span>
            <span class="badge badge-<?php echo $has_custom ? 'new' : 'accepted'; ?>" style="margin-left:auto"><?php echo $has_custom ? 'Custom' : 'Default'; ?></span>
        </summary>
        <form method="post" style="margin-top:10px;padding:10px;background:#fafafa;border-radius:8px">
            <?php wp_nonce_field( 'rp_permissions_action' ); ?>
            <input type="hidden" name="rp_permissions_action" value="save">
            <input type="hidden" name="perm_user_id" value="<?php echo (int) $pu->ID; ?>">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px">
                <?php foreach ( $perm_pages as $slug => $label ) :
                    $checked = $has_custom ? ( ! empty( $user_perms[ $slug ] ) ) : true;
                ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:.88rem;cursor:pointer">
                    <input type="checkbox" name="perm_<?php echo esc_attr( $slug ); ?>" value="1" <?php checked( $checked ); ?>>
                    <?php echo esc_html( $label ); ?>
                </label>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-sm btn-primary" type="submit" style="margin-top:10px" data-i18n="Save Permissions">Save Permissions</button>
        </form>
    </details>
    <?php endforeach; ?>
    <?php if ( empty( $perm_users ) ) : ?><p class="text-muted">No non-admin staff to configure.</p><?php endif; ?>
</div>

<div class="p-card mt-16">
    <div class="p-card-header"><h3 class="p-card-title">Leave History</h3></div>
    <?php if($leave_records): foreach($leave_records as $l): ?>
        <div class="order-item"><span class="order-num"><?php echo esc_html(wp_date('d M',strtotime($l->leave_from))); ?>–<?php echo esc_html(wp_date('d M',strtotime($l->leave_to))); ?></span><div class="order-meta"><strong><?php echo esc_html($l->display_name ?: 'Deleted Staff'); ?></strong><span class="text-sm text-muted"><?php echo esc_html(ucfirst($l->leave_type)); ?> • <?php echo esc_html($l->reason); ?></span></div><span class="badge badge-<?php echo $l->status==='approved'?'completed':($l->status==='pending'?'new':'cancelled'); ?>"><?php echo esc_html(ucfirst($l->status)); ?></span></div>
    <?php endforeach; else: ?><div class="empty-state"><p>No leave records yet.</p></div><?php endif; ?>
</div>
