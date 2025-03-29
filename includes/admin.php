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

    if ( isset( $_POST['pmpro_matomo_save'] ) && check_admin_referer( 'pmpro_matomo_save_settings' ) ) {
        update_option( 'pmpro_matomo_enable_tracking', sanitize_text_field( $_POST['enable_tracking'] ) );
        update_option( 'pmpro_matomo_site_id', sanitize_text_field( $_POST['site_id'] ) );
        update_option( 'pmpro_matomo_tracker_url', esc_url_raw( $_POST['tracker_url'] ) );
        echo '<div class="updated"><p>' . __( 'Settings saved.', 'pmpro-matomo' ) . '</p></div>';
    }

    $enable_tracking = get_option( 'pmpro_matomo_enable_tracking', 'no' );
    $site_id = get_option( 'pmpro_matomo_site_id', '' );
    $tracker_url = get_option( 'pmpro_matomo_tracker_url', '' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Paid Memberships Pro - Matomo Settings', 'pmpro-matomo' ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'pmpro_matomo_save_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="enable_tracking"><?php esc_html_e( 'Enable Tracking', 'pmpro-matomo' ); ?></label></th>
                    <td>
                        <select name="enable_tracking" id="enable_tracking">
                            <option value="yes" <?php selected( $enable_tracking, 'yes' ); ?>><?php esc_html_e( 'Yes', 'pmpro-matomo' ); ?></option>
                            <option value="no" <?php selected( $enable_tracking, 'no' ); ?>><?php esc_html_e( 'No', 'pmpro-matomo' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="site_id"><?php esc_html_e( 'Matomo Site ID', 'pmpro-matomo' ); ?></label></th>
                    <td><input type="text" name="site_id" id="site_id" value="<?php echo esc_attr( $site_id ); ?>" class="regular-text" placeholder="e.g., 1"></td>
                </tr>
                <tr>
                    <th><label for="tracker_url"><?php esc_html_e( 'Matomo Tracker URL', 'pmpro-matomo' ); ?></label></th>
                    <td><input type="url" name="tracker_url" id="tracker_url" value="<?php echo esc_attr( $tracker_url ); ?>" class="regular-text" placeholder="e.g., https://your-matomo-domain.com"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="pmpro_matomo_save" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'pmpro-matomo' ); ?>"></p>
        </form>
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