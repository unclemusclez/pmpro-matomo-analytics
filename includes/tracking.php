<?php
class PMPro_Matomo_Tracking {
    private $site_id;
    private $tracker_url;
    private $is_enabled;

    public function __construct() {
        // Check for Connect Matomo settings first
        if ( class_exists( 'WP_Piwik' ) && isset( $GLOBALS['wp-piwik'] ) ) {
            $settings = get_option( 'wp-piwik_settings', [] );
            $this->site_id = isset( $settings['site_id'] ) ? $settings['site_id'] : '';
            $this->tracker_url = isset( $settings['piwik_url'] ) ? rtrim( $settings['piwik_url'], '/' ) : ''; // Corrected to 'piwik_url'
            $this->is_enabled = ! empty( $settings['track_mode'] ) && $settings['track_mode'] !== 'disabled';
            // Debug logging
            error_log( 'PMPro Matomo: Connect Matomo detected. Site ID: ' . $this->site_id . ', Tracker URL: ' . $this->tracker_url . ', Enabled: ' . ($this->is_enabled ? 'yes' : 'no') );
        }
        // Fallback to Matomo Analytics settings
        elseif ( defined( 'MATOMO_ANALYTICS_FILE' ) ) {
            $settings = new \WpMatomo\Settings();
            $this->site_id = \WpMatomo\Site::get_matomo_site_id( get_current_blog_id() );
            $this->tracker_url = $settings->get_tracker_api_url_in_matomo_dir();
            $this->is_enabled = $settings->is_tracking_enabled();
            error_log( 'PMPro Matomo: Matomo Analytics detected. Site ID: ' . $this->site_id . ', Tracker URL: ' . $this->tracker_url );
        } else {
            $this->is_enabled = false;
            error_log( 'PMPro Matomo: No Matomo plugin detected.' );
        }

        if ( $this->is_enabled ) {
            $this->register_hooks();
        }
    }

    public function register_hooks() {
        add_action( 'wp_head', [ $this, 'add_tracking_code' ], 10 );
        add_action( 'pmpro_after_checkout', [ $this, 'track_membership_signup' ], 10, 2 );
        add_action( 'pmpro_after_change_membership_level', [ $this, 'track_level_change' ], 10, 3 );
    }

    public function add_tracking_code() {
        if ( ! $this->site_id || ! $this->tracker_url ) {
            return;
        }
        ?>
        <!-- Matomo -->
        <script type="text/javascript">
            var _paq = window._paq = window._paq || [];
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function() {
                var u = "<?php echo esc_js( rtrim( $this->tracker_url, '/' ) ); ?>/";
                _paq.push(['setTrackerUrl', u + 'matomo.php']);
                _paq.push(['setSiteId', '<?php echo esc_js( $this->site_id ); ?>']);
                var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                g.type = 'text/javascript'; g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
            })();
        </script>
        <noscript><p><img src="<?php echo esc_url( $this->tracker_url ); ?>/matomo.php?idsite=<?php echo esc_attr( $this->site_id ); ?>&rec=1" style="border:0;" alt="" /></p></noscript>
        <!-- End Matomo -->
        <?php
    }

    public function track_membership_signup( $user_id, $order ) {
        $level = pmpro_getLevel( $order->membership_id );
        $price = $order->total;
        ?>
        <script type="text/javascript">
            var _paq = window._paq || [];
            _paq.push(['trackEvent', 'Membership', 'Signup', '<?php echo esc_js( $level->name ); ?>', <?php echo esc_js( $price ); ?>]);
            _paq.push(['trackGoal', 1, <?php echo esc_js( $price ); ?>]); // Assuming Goal ID 1 for signups
        </script>
        <?php
    }

    public function track_level_change( $level_id, $user_id, $cancel_level ) {
        $level = pmpro_getLevel( $level_id );
        $level_name = $level ? $level->name : 'None (Cancelled)';
        ?>
        <script type="text/javascript">
            var _paq = window._paq || [];
            _paq.push(['trackEvent', 'Membership', 'Level Change', '<?php echo esc_js( $level_name ); ?>']);
        </script>
        <?php
    }
}