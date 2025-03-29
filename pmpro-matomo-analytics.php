<?php
/**
 * Plugin Name: Paid Memberships Pro - Matomo Integration
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-matomo/
 * Description: Connect Paid Memberships Pro to a self-hosted Matomo instance to track membership signups, level changes, and user activity.
 * Version: 1.0
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
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
 * Initialize tracking if PMPro is active
 */
function pmpro_matomo_init() {
    if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
        new PMPro_Matomo_Tracking();
    }
}
add_action( 'plugins_loaded', 'pmpro_matomo_init' );