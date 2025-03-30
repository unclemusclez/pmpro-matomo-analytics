<?php
function pmpro_matomo_admin_menu() {
    add_options_page(
        __('PMPro Matomo', 'pmpro-matomo'),
        __('PMPro Matomo', 'pmpro-matomo'),
        'manage_options',
        'pmpro_matomo_settings',
        'pmpro_matomo_settings_page'
    );
}
add_action('admin_menu', 'pmpro_matomo_admin_menu');

function pmpro_matomo_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'pmpro-matomo'));
    }

    if (isset($_POST['pmpro_matomo_save_settings'])) {
        check_admin_referer('pmpro_matomo_settings_nonce');
        $options = array(
            'tracker_url' => sanitize_text_field($_POST['pmpro_matomo_tracker_url']),
            'site_id' => sanitize_text_field($_POST['pmpro_matomo_site_id'])
        );
        update_option('pmpro_matomo_settings', $options);
        error_log('PMPro Matomo: Settings saved - ' . print_r($options, true));
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'pmpro-matomo') . '</p></div>';
    }

    $tracking = new PMPro_Matomo_Tracking();
    $options = get_option('pmpro_matomo_settings', array('tracker_url' => '', 'site_id' => ''));
    $site_id = $tracking->get_site_id();
    $tracker_url = $tracking->get_tracker_url();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Paid Memberships Pro - Matomo Settings', 'pmpro-matomo'); ?></h1>
        <p><?php esc_html_e('Configure Matomo tracking settings below.', 'pmpro-matomo'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('pmpro_matomo_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="pmpro_matomo_tracker_url"><?php esc_html_e('Matomo Tracker URL', 'pmpro-matomo'); ?></label></th>
                    <td>
                        <input type="url" name="pmpro_matomo_tracker_url" id="pmpro_matomo_tracker_url" value="<?php echo esc_attr($options['tracker_url']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('e.g., https://your-matomo-domain.com/', 'pmpro-matomo'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="pmpro_matomo_site_id"><?php esc_html_e('Matomo Site ID', 'pmpro-matomo'); ?></label></th>
                    <td>
                        <input type="text" name="pmpro_matomo_site_id" id="pmpro_matomo_site_id" value="<?php echo esc_attr($options['site_id']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('e.g., 3', 'pmpro-matomo'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="pmpro_matomo_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'pmpro-matomo'); ?>" />
            </p>
        </form>
        <h2><?php esc_html_e('Current Matomo Configuration', 'pmpro-matomo'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Matomo Site ID', 'pmpro-matomo'); ?></th>
                <td><?php echo esc_html($site_id ?: __('Not configured', 'pmpro-matomo')); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Matomo Tracker URL', 'pmpro-matomo'); ?></th>
                <td><?php echo esc_html($tracker_url ?: __('Not configured', 'pmpro-matomo')); ?></td>
            </tr>
        </table>
        <p><?php printf(
            esc_html__('Requires WP-Piwik for base tracking. Configure WP-Piwik %shere%s if needed.', 'pmpro-matomo'),
            '<a href="' . esc_url(admin_url('options-general.php?page=wp-piwik')) . '">',
            '</a>'
        ); ?></p>
    </div>
    <?php
}

function pmpro_matomo_pmpro_not_detected() {
    if (!isset($_REQUEST['page']) || strpos($_REQUEST['page'], 'pmpro') === false) {
        return;
    }

    if (!function_exists('pmpro_getMembershipLevelForUser')) {
        printf(
            '<div class="notice notice-error"><p>%s <a href="%s" target="_blank">%s</a> %s</p></div>',
            esc_html__('Paid Memberships Pro - Matomo Integration', 'pmpro-matomo'),
            esc_url('https://wordpress.org/plugins/paid-memberships-pro/'),
            esc_html__('requires Paid Memberships Pro', 'pmpro-matomo'),
            esc_html__('to be installed and active.', 'pmpro-matomo')
        );
    }
}
add_action('admin_notices', 'pmpro_matomo_pmpro_not_detected');