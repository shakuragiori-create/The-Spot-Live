<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="512x512" href="<?php echo esc_url( home_url( '/wp-content/themes/the-spot-theme/assets/images/favicon.png' ) ); ?>?v=2.3.0">
<link rel="apple-touch-icon" href="<?php echo esc_url( home_url( '/wp-content/themes/the-spot-theme/assets/images/favicon.png' ) ); ?>?v=2.3.0">
<title>Login — The Spot Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--y:#F5C300;--b:#111;--br:#6B3A2A;--cr:#F5EFD6;--w:#fff}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--b);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-card{background:var(--w);border-radius:20px;padding:44px 36px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.login-logo{text-align:center;margin-bottom:28px}
.login-logo img{height:56px;width:auto}
.login-logo h1{font-family:'Poppins',sans-serif;font-size:1.1rem;font-weight:800;color:var(--b);margin-top:10px}
.login-logo p{font-size:.78rem;color:#888;margin-top:4px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.78rem;font-weight:600;color:#555;margin-bottom:6px;letter-spacing:.3px}
.form-group input{width:100%;padding:12px 16px;border:2px solid #e8e8e8;border-radius:10px;font-size:.9rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:var(--y)}
.remember{display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:.82rem;color:#666}
.remember input{width:16px;height:16px;accent-color:var(--y)}
.btn-login{width:100%;padding:14px;background:var(--b);color:var(--y);border:none;border-radius:10px;font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;transition:.2s}
.btn-login:hover{background:var(--br)}
.error{background:#fee;color:#c00;padding:10px 14px;border-radius:8px;font-size:.82rem;margin-bottom:16px;border:1px solid #fcc}
.back-link{display:block;text-align:center;margin-top:20px;font-size:.82rem;color:#888;text-decoration:none}
.back-link:hover{color:var(--y)}
</style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <img src="<?php echo esc_url( RP_PLUGIN_URL . 'admin/images/logo.jpg' ); ?>" alt="The Spot">
        <h1>The Spot Admin</h1>
        <p>Staff Panel — Sign in to continue</p>
    </div>

    <?php if ( ! empty( $error ) ) : ?>
        <div class="error"><?php echo esc_html( $error ); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'rp_panel_login', 'rp_login_nonce' ); ?>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autocomplete="username" autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <label class="remember">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button type="submit" class="btn-login">Sign In</button>
    </form>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="back-link">← Back to website</a>
</div>
</body>
</html>
