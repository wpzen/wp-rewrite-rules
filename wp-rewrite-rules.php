<?php

/**
 * Plugin Name:       WP Rewrite Rules
 * Plugin URI:        https://wpzen.ru/plugins/wp-rewrite-rules/
 * Description:       WP Rewrite Rules is a wrapper for the URL rewriting system.
 * Version:           1.0.0
 * Author:            Pleshakov Valery
 * Author URI:        https://wpzen.ru
 *
 * @package WP_Rewrite_Rules
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include the main WP_Rewrite_Rules class.
if ( ! class_exists( 'WP_Rewrite_Rules', false ) ) {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-rewrite-rules.php';
}

/**
 * Returns the main instance of WP_Rewrite_Rules.
 *
 * @since  1.0.0
 * @return WP_Rewrite_Rules
 */
function wp_rewrite_rules() {
	return WP_Rewrite_Rules::instance();
}
wp_rewrite_rules();