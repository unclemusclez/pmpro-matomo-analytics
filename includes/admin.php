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

    // Initialize tracking to get settings
    $tracking = new PMPro_Matomo_Tracking();
    $site_id = $tracking->get_site_id();
    $tracker_url = $tracking->get_tracker_url();

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Paid Memberships Pro - Matomo Settings', 'pmpro-matomo' ); ?></h1>
        <p><?php esc_html_e( 'This plugin uses the Matomo PHP Tracker SDK with settings from WP-Piwik.', 'pmpro-matomo' ); ?></p>
        <ul>
            <li><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-piwik' ) ); ?>"><?'. esc_html__( 'Configure WP-Piwik', 'pmpro-matomo' ); ?></a></li>
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