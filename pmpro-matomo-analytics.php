<?php
/**
 * Plugin Name: Paid Memberships Pro - Matomo Integration
 * Plugin URI: https://github.com/yourusername/pmpro-matomo-analytics
 * Description: Integrates Matomo tracking with Paid Memberships Pro using the PHP Tracker SDK.
 * Version: 1.0
 * Author: Devin J. Dawson
 * Author URI: https://unclemusclez.com
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

function pmpro_matomo_load_textdomain() {
    load_plugin_textdomain( 'pmpro-matomo', false, dirname( PMPRO_MATOMO_BASENAME ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pmpro_matomo_load_textdomain' );

function pmpro_matomo_requirements_check() {
    if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    if ( ! class_exists( 'WP_Piwik' ) ) {
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The %1$s plugin requires "Connect Matomo" to be installed and active. <a href="%2$s">Install Connect Matomo</a>.', 'pmpro-matomo' ),
                esc_html__( 'Paid Memberships Pro - Matomo Integration', 'pmpro-matomo' ),
                esc_url( admin_url( 'plugin-install.php?s=Connect+Matomo&tab=search&type=term' ) )
            )
        );
    } else {
        error_log( 'PMPro Matomo: WP-Piwik plugin detected.' );
    }
}
add_action( 'admin_notices', 'pmpro_matomo_requirements_check' );

function pmpro_matomo_init() {
    if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && class_exists( 'WP_Piwik' ) ) {
        new PMPro_Matomo_Tracking();
    }
}
add_action( 'init', 'pmpro_matomo_init', 20 );