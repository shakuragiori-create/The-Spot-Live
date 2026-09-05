<?php
/**
 * The Spot — Fast Food & Tea · theme setup.
 *
 * @package The_Spot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bump this whenever assets/main.css or assets/js/main.js changes, so browsers
 * (and the host's page cache) fetch the new file instead of a stale copy.
 */
define( 'TS_ASSET_VERSION', '2.3.0' );

require_once get_template_directory() . '/inc/theme-data.php';
require_once get_template_directory() . '/inc/menu-fallback.php';

/**
 * Theme supports.
 */
function the_spot_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'html5',
		[ 'search-form', 'gallery', 'caption', 'style', 'script' ]
	);
	add_theme_support(
		'custom-logo',
		[
			'height'      => 100,
			'width'       => 280,
			'flex-height' => true,
			'flex-width'  => true,
		]
	);
}
add_action( 'after_setup_theme', 'the_spot_setup' );

/**
 * Front-end assets. Deliberately self-hosted — the only third-party requests the
 * public site makes are the Google Fonts stylesheet and the menu QR image.
 */
function the_spot_enqueue() {
	wp_enqueue_style(
		'the-spot-main',
		get_template_directory_uri() . '/assets/main.css',
		[],
		TS_ASSET_VERSION
	);

	wp_enqueue_script(
		'the-spot-js',
		get_template_directory_uri() . '/assets/js/main.js',
		[],
		TS_ASSET_VERSION,
		true
	);

	wp_localize_script(
		'the-spot-js',
		'TS_DATA',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'rp_public_nonce' ),
			'live'    => ts_plugin_active() ? 1 : 0,
		]
	);
}
add_action( 'wp_enqueue_scripts', 'the_spot_enqueue' );

/**
 * Use The Spot favicon everywhere on the public WordPress site instead of the
 * WordPress/site-icon value. This keeps the brand icon consistent on every URL.
 */
function the_spot_favicon() {
    $icon = get_template_directory_uri() . '/assets/images/favicon.png?v=2.3.0';
    echo '<link rel="icon" type="image/png" sizes="512x512" href="' . esc_url( $icon ) . '">\n';
    echo '<link rel="apple-touch-icon" href="' . esc_url( $icon ) . '">\n';
}
add_action( 'init', function() {
    remove_action( 'wp_head', 'wp_site_icon', 99 );
    remove_action( 'login_head', 'wp_site_icon', 99 );
} );
add_action( 'wp_head', 'the_spot_favicon', 99 );
