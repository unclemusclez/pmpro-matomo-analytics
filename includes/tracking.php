<?php
class PMPro_Matomo_Tracking {
    private $site_id;
    private $tracker_url;
    private $is_enabled;

    public function __construct() {
        // Initialize defaults
        $this->site_id = '';
        $this->tracker_url = '';
        $this->is_enabled = false;

        // Get settings from admin page first
        $settings = get_option('pmpro_matomo_settings', []);
        $this->tracker_url = !empty($settings['tracker_url']) ? rtrim($settings['tracker_url'], '/') . '/matomo.php' : '';
        $this->site_id = !empty($settings['site_id']) ? $settings['site_id'] : '';

        // Fallback to WP-Piwik if settings are incomplete
        if ((empty($this->tracker_url) || empty($this->site_id)) && class_exists('WP_Piwik')) {
            if (!isset($GLOBALS['wp-piwik'])) {
                $GLOBALS['wp-piwik'] = new WP_Piwik();
            }
            $wp_piwik = $GLOBALS['wp-piwik'];

            if (empty($this->site_id) && method_exists($wp_piwik, 'getOption')) {
                $this->site_id = $wp_piwik->getOption('site_id') ?: '';
            }

            if (empty($this->tracker_url)) {
                if (method_exists($wp_piwik, 'getMatomoUrl')) {
                    $this->tracker_url = rtrim($wp_piwik->getMatomoUrl() ?: '', '/') . '/matomo.php';
                } elseif (method_exists($wp_piwik, 'getPiwikUrl')) {
                    $this->tracker_url = rtrim($wp_piwik->getPiwikUrl() ?: '', '/') . '/matomo.php';
                }
            }
        }

        // Validate settings
        if ($this->site_id && $this->tracker_url && filter_var($this->tracker_url, FILTER_VALIDATE_URL)) {
            $this->is_enabled = true;
            $this->register_hooks();
        } else {
            error_log('PMPro Matomo: Missing site_id or valid tracker_url - Site ID: ' . ($this->site_id ?: 'not set') . ', Tracker URL: ' . ($this->tracker_url ?: 'not set'));
        }

        // Debug logging
        error_log('PMPro Matomo Tracking: Initialized - Site ID: ' . ($this->site_id ?: 'not set') . ', Tracker URL: ' . ($this->tracker_url ?: 'not set') . ', Enabled: ' . ($this->is_enabled ? 'yes' : 'no'));
        error_log('PMPro Matomo: Raw pmpro_matomo_settings - ' . print_r(get_option('pmpro_matomo_settings'), true));
    }

    public function register_hooks() {
        add_action('pmpro_after_checkout', [$this, 'track_membership_signup'], 10, 2);
        add_action('pmpro_after_change_membership_level', [$this, 'track_level_change'], 10, 3);
    }

    private function send_tracking_request($params) {
        if (!$this->is_enabled) {
            return;
        }

        $default_params = [
            'idsite' => $this->site_id,
            'rec' => 1,
            'url' => home_url(add_query_arg([])), // Current page URL
            '_id' => $this->generate_visitor_id(), // Unique visitor ID
            'rand' => wp_rand(100000, 999999), // Random to avoid caching
            'apiv' => 1,
        ];

        $params = array_merge($default_params, $params);

        $response = wp_remote_post($this->tracker_url, [
            'body' => $params,
            'timeout' => 5,
            'sslverify' => true, // Set to false if SSL issues
        ]);

        if (is_wp_error($response)) {
            error_log('PMPro Matomo: Tracking request failed - ' . $response->get_error_message());
        } else {
            error_log('PMPro Matomo: Tracking request sent - ' . print_r($params, true));
        }
    }

    private function generate_visitor_id() {
        // Generate a 16-char hex visitor ID
        if (is_user_logged_in()) {
            return substr(md5(get_current_user_id()), 0, 16);
        }
        return substr(md5(uniqid(rand(), true)), 0, 16);
    }

    public function track_membership_signup($user_id, $order) {
        if (!$this->is_enabled) {
            return;
        }
        $level = pmpro_getLevel($order->membership_id);
        $price = $order->total;

        $this->send_tracking_request([
            'e_c' => 'Membership',
            'e_a' => 'Signup',
            'e_n' => $level->name,
            'e_v' => floatval($price),
            'idgoal' => 0, // Ecommerce interaction
            'revenue' => floatval($price),
        ]);
    }

    public function track_level_change($level_id, $user_id, $cancel_level) {
        if (!$this->is_enabled) {
            return;
        }
        $level = pmpro_getLevel($level_id);
        $level_name = $level ? $level->name : 'None (Cancelled)';

        $this->send_tracking_request([
            'e_c' => 'Membership',
            'e_a' => 'Level Change',
            'e_n' => $level_name,
        ]);
    }

    public function get_site_id() {
        return $this->site_id;
    }

    public function get_tracker_url() {
        return $this->tracker_url;
    }
}