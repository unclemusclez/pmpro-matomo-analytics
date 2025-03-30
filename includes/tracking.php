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

        // Get our plugin's settings first
        $settings = get_option('pmpro_matomo_settings', []);
        $this->tracker_url = !empty($settings['tracker_url']) ? rtrim($settings['tracker_url'], '/') : '';
        $this->site_id = !empty($settings['site_id']) ? $settings['site_id'] : '';

        // Fallback to WP-Piwik if our settings are incomplete
        if ((empty($this->tracker_url) || empty($this->site_id)) && class_exists('WP_Piwik')) {
            if (!isset($GLOBALS['wp-piwik'])) {
                $GLOBALS['wp-piwik'] = new WP_Piwik();
            }
            $wp_piwik = $GLOBALS['wp-piwik'];

            if (method_exists($wp_piwik, 'getOption') && empty($this->site_id)) {
                $this->site_id = $wp_piwik->getOption('site_id') ?: '';
            }

            // Use your suggested safe URL methods
            if (empty($this->tracker_url)) {
                if (method_exists($wp_piwik, 'getMatomoUrl')) {
                    $this->tracker_url = rtrim($wp_piwik->getMatomoUrl() ?: '', '/');
                } elseif (method_exists($wp_piwik, 'getPiwikUrl')) {
                    $this->tracker_url = rtrim($wp_piwik->getPiwikUrl() ?: '', '/');
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
    }

    public function register_hooks() {
        add_action('wp_footer', [$this, 'add_tracking_code'], 20);
        add_action('pmpro_after_checkout', [$this, 'track_membership_signup'], 10, 2);
        add_action('pmpro_after_change_membership_level', [$this, 'track_level_change'], 10, 3);
    }

    public function add_tracking_code() {
        if (!$this->is_enabled) {
            return;
        }
        ?>
        <script type="text/javascript">
            console.log('PMPro Matomo: Tracking enabled - Site ID: <?php echo esc_js($this->site_id); ?>, Tracker URL: <?php echo esc_js($this->tracker_url); ?>');
        </script>
        <?php
    }

    public function track_membership_signup($user_id, $order) {
        if (!$this->is_enabled) {
            return;
        }
        $level = pmpro_getLevel($order->membership_id);
        $price = $order->total;
        ?>
        <script type="text/javascript">
            if (typeof _paq !== 'undefined') {
                _paq.push(['trackEvent', 'Membership', 'Signup', '<?php echo esc_js($level->name); ?>', <?php echo floatval($price); ?>]);
                _paq.push(['trackGoal', 1, <?php echo floatval($price); ?>]); // Goal ID 1
                console.log('PMPro Matomo: Event tracked - Membership Signup: <?php echo esc_js($level->name); ?> - <?php echo esc_js($price); ?>');
            } else {
                console.log('PMPro Matomo: _paq not found - ensure WP-Piwik is configured and active');
            }
        </script>
        <?php
    }

    public function track_level_change($level_id, $user_id, $cancel_level) {
        if (!$this->is_enabled) {
            return;
        }
        $level = pmpro_getLevel($level_id);
        $level_name = $level ? $level->name : 'None (Cancelled)';
        ?>
        <script type="text/javascript">
            if (typeof _paq !== 'undefined') {
                _paq.push(['trackEvent', 'Membership', 'Level Change', '<?php echo esc_js($level_name); ?>']);
                console.log('PMPro Matomo: Event tracked - Membership Level Change: <?php echo esc_js($level_name); ?>');
            } else {
                console.log('PMPro Matomo: _paq not found - ensure WP-Piwik is configured and active');
            }
        </script>
        <?php
    }

    public function get_site_id() {
        return $this->site_id;
    }

    public function get_tracker_url() {
        return $this->tracker_url;
    }
}