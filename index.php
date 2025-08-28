<?php
/*
Plugin Name: Vanterra Forms
Description: Utilities for forms including attribution tracking and future enhancements.
Author: Vanterra
Version: 1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Basic plugin constants for future extensibility
define( 'VANTERRA_FORMS_FILE', __FILE__ );
define( 'VANTERRA_FORMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'VANTERRA_FORMS_URL', plugins_url( '/', __FILE__ ) );
define( 'VANTERRA_FORMS_ASSETS_URL', VANTERRA_FORMS_URL . 'assets/' );
define( 'VANTERRA_FORMS_INC_DIR', trailingslashit( VANTERRA_FORMS_DIR . 'includes' ) );

// Default options on activation
function vanterra_forms_activate() {
    add_option( 'vanterra_enable_attribution', 1 );
}
register_activation_hook( __FILE__, 'vanterra_forms_activate' );

// Create GF form on plugin activation if GF is available
function vanterra_forms_on_activate_deferred() {
    if ( ! class_exists( 'GFAPI' ) ) {
        return;
    }
    if ( function_exists( 'vanterra_forms_maybe_create_gf_form' ) ) {
        vanterra_forms_maybe_create_gf_form();
    }
}
// Run late on init to ensure GF loaded when activation occurs
add_action( 'init', 'vanterra_forms_on_activate_deferred', 20 );

// Add Settings link on the Plugins page
function vanterra_forms_action_links( $links ) {
    $settings_url = admin_url( 'options-general.php?page=vanterra-forms' );
    $links[] = '<a href="' . esc_url( $settings_url ) . '">Settings</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'vanterra_forms_action_links' );

// Includes
if ( file_exists( VANTERRA_FORMS_INC_DIR . 'admin.php' ) ) {
    require_once VANTERRA_FORMS_INC_DIR . 'admin.php';
}
if ( file_exists( VANTERRA_FORMS_INC_DIR . 'attribution.php' ) ) {
    require_once VANTERRA_FORMS_INC_DIR . 'attribution.php';
}
if ( file_exists( VANTERRA_FORMS_INC_DIR . 'gf.php' ) ) {
    require_once VANTERRA_FORMS_INC_DIR . 'gf.php';
}

