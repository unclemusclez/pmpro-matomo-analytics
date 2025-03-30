<?php
function pmpro_matomo_admin_menu() {
    add_options_page(
        __( 'PMPro Matomo', 'pmpro-matomo' ),
        __( 'PMPro Matomo', 'pmpro-matomo' ),
        'manage_options',
        'pmpro_matomo_settings',
        'pmpro_matomo_settings_page'
    );
}
add_action( 'admin_menu', 'pmpro_matomo_admin_menu' );

function pmpro_matomo_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'pmpro-matomo' ) );
    }

    $has_connect_matomo = class_exists( 'WP_Piwik' );
    $has_matomo_analytics = defined( 'MATOMO_ANALYTICS_FILE' );
    $site_id = '';
    $tracker_url = '';

    if ( $has_connect_matomo && isset( $GLOBALS['wp-piwik'] ) && method_exists( $GLOBALS['wp-piwik'], 'getOption' ) ) {
        $wp_piwik = $GLOBALS['wp-piwik'];
        $site_id = $wp_piwik->getOption( 'site_id' );
        $tracker_url = $wp_piwik->getOption( 'piwik_path' );
    } elseif ( $has_connect_matomo ) {
        $global_settings = get_option( 'wp_piwik_global_settings', [] );
        $site_settings = get_option( 'wp_piwik_settings', [] );
        $site_id = isset( $site_settings['site_id'] ) ? $site_settings['site_id'] : (isset( $global_settings['default_site'] ) ? $global_settings['default_site'] : '');
        $tracker_url = isset( $site_settings['piwik_path'] ) ? $site_settings['piwik_path'] : (isset( $global_settings['piwik_path'] ) ? $global_settings['piwik_path'] : '');
    } elseif ( $has_matomo_analytics ) {
        $settings = new \WpMatomo\Settings();
        $site_id = \WpMatomo\Site::get_matomo_site_id( get_current_blog_id() );
        $tracker_url = $settings->get_tracker_api_url_in_matomo_dir();
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Paid Memberships Pro - Matomo Settings', 'pmpro-matomo' ); ?></h1>
        <p><?php esc_html_e( 'This plugin uses settings from "Connect Matomo" or "Matomo Analytics". Please configure Matomo in their respective settings pages:', 'pmpro-matomo' ); ?></p>
        <ul>
            <?php if ( $has_connect_matomo ) : ?>
                <li><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-piwik' ) ); ?>"><?php esc_html_e( 'Configure Connect Matomo', 'pmpro-matomo' ); ?></a></li>
            <?php endif; ?>
            <?php if ( $has_matomo_analytics ) : ?>
                <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=matomo-analytics' ) ); ?>"><?php esc_html_e( 'Configure Matomo Analytics', 'pmpro-matomo' ); ?></a></li>
            <?php endif; ?>
        </ul>
        <h2><?php esc_html_e( 'Current Matomo Configuration', 'pmpro-matomo' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Matomo Site ID', 'pmpro-matomo' ); ?></th>
                <td><?php echo esc_html( $site_id ?: __( 'Not configured', 'pmpro-matomo' ) ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Matomo Tracker URL', 'pmpro-matomo' ); ?></th>
                <td><?php echo esc_html( $tracker_url ?: __( 'Not configured', 'pmpro-matomo' ) ); ?></td>
            </tr>
        </table>
    </div>
    <?php
}

/**
 * Admin notice if PMPro is not installed
 */
function pmpro_matomo_pmpro_not_detected() {
    if ( ! isset( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
        printf(
            '<div class="notice notice-error"><p>%s <a href="%s" target="_blank">%s</a> %s</p></div>',
            esc_html__( 'Paid Memberships Pro - Matomo Integration', 'pmpro-matomo' ),
            esc_url( 'https://wordpress.org/plugins/paid-memberships-pro/' ),
            esc_html__( 'requires Paid Memberships Pro', 'pmpro-matomo' ),
            esc_html__( 'to be installed and active.', 'pmpro-matomo' )
        );
    }
}
add_action( 'admin_notices', 'pmpro_matomo_pmpro_not_detected' );