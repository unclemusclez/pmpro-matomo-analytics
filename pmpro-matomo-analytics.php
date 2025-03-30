<?php
/**
 * Plugin Name: Paid Memberships Pro - Matomo Integration
 * Plugin URI: https://github.com/unclemusclez/pmpro-matomo
 * Description: Connect Paid Memberships Pro to a Matomo instance to track membership signups, level changes, and user activity.
 * Version: 1.0
 * Author: Devin J. Dawson
 * Author URI: https://waterpistol.co
 * Text Domain: pmpro-matomo
 * Domain Path: /languages
 * License: GPL v3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'PMPRO_MATOMO_DIR', dirname( __FILE__ ) );
define( 'PMPRO_MATOMO_BASENAME', plugin_basename( __FILE__ ) );

// Includes
require_once PMPRO_MATOMO_DIR . '/includes/tracking.php';
require_once PMPRO_MATOMO_DIR . '/includes/admin.php';

/**
 * Load text domain
 */
function pmpro_matomo_load_textdomain() {
    load_plugin_textdomain( 'pmpro-matomo', false, dirname( PMPRO_MATOMO_BASENAME ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pmpro_matomo_load_textdomain' );

/**
 * Admin notice for Matomo dependency
 */
function pmpro_matomo_requirements_check() {
    if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    $has_connect_matomo = class_exists( 'WP_Piwik' );
    $has_matomo_analytics = defined( 'MATOMO_ANALYTICS_FILE' );

    if ( ! $has_connect_matomo && ! $has_matomo_analytics ) {
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The %1$s plugin requires either "Connect Matomo" or "Matomo Analytics" to be installed and active. <a href="%2$s">Install Connect Matomo</a> or <a href="%3$s">Install Matomo Analytics</a>.', 'pmpro-matomo' ),
                esc_html__( 'Paid Memberships Pro - Matomo Integration', 'pmpro-matomo' ),
                esc_url( admin_url( 'plugin-install.php?s=Connect+Matomo&tab=search&type=term' ) ),
                esc_url( admin_url( 'plugin-install.php?s=Matomo+Analytics&tab=search&type=term' ) )
            )
        );
    } else {
        error_log( 'PMPro Matomo: Matomo plugin detected - Connect Matomo: ' . ($has_connect_matomo ? 'yes' : 'no') . ', Matomo Analytics: ' . ($has_matomo_analytics ? 'yes' : 'no') );
    }
}
add_action( 'admin_notices', 'pmpro_matomo_requirements_check' );

/**
 * Initialize tracking if PMPro and Matomo are active, delayed to after plugins_loaded
 */
function pmpro_matomo_init() {
    if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && class_exists( 'WP_Piwik' ) ) {
        // Ensure WP-Piwik is fully initialized
        if ( ! isset( $GLOBALS['wp-piwik'] ) ) {
            $GLOBALS['wp-piwik'] = new WP_Piwik();
        }
        new PMPro_Matomo_Tracking();
    } elseif ( function_exists( 'pmpro_getMembershipLevelForUser' ) && defined( 'MATOMO_ANALYTICS_FILE' ) ) {
        new PMPro_Matomo_Tracking();
    }
}
add_action( 'plugins_loaded', 'pmpro_matomo_init', 20 ); // Increased priority to ensure WP-Piwik loads first