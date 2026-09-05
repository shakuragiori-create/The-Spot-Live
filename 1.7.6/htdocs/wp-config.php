<?php
define('WP_CACHE', true); // Added by SpeedyCache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'if042193014_wp804' );
define( 'DB_USER', '42193014_2' );
define( 'DB_PASSWORD', '4S7R!]p7OC' );
define( 'DB_HOST', 'sql306.byetcluster.com' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
define('WP_HOME', 'http://thespot-fastfood-tea.gt.tc');
define('WP_SITEURL', 'http://thespot-fastfood-tea.gt.tc');


/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '1npvfb5ivlicimsrevllaafadruhrdsdjpwgwdpavushj0ymz36kxlzsqvkynhgt' );
define( 'SECURE_AUTH_KEY',  'yjd1ogwl3xitqxlcpt7ateaaqy6dyqqxxwmncnn3yfkpwqhjsnjqgqlw2z2qqzv4' );
define( 'LOGGED_IN_KEY',    'ced7fawbymlmphjpj57ykohyffz0mwvdolzkxrdafloziytzngt07gla1du4bjcm' );
define( 'NONCE_KEY',        '7r2rg4dab0uz0isvsj1qw6tchz5zsduxbzxlzqdwheoq3kbntukka650niuwk3wu' );
define( 'AUTH_SALT',        'r124xier4kvmwbncjgcdogbghagcem26kzuex0ge1hrbo5zkt4cslimfmf9feyha' );
define( 'SECURE_AUTH_SALT', 'rvo9zirmjod1khpczrpavatvopgkmqgpkegwokt1wn9pppl0d2danqjay9lh1npy' );
define( 'LOGGED_IN_SALT',   'vlpqrpiokaq0uje2vooiplller2kprvpxzjjklamcqxejk9ic7rgxwpb71cvcw7g' );
define( 'NONCE_SALT',       'rb7iuhmkbttvcwhmiqnwwnpuly9rulbhabtbexegufcfbtslh1iszqjds5u5run9' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpkr_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
