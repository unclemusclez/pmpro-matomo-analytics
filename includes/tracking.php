<?php
class PMPro_Matomo_Tracking {
    private $site_id;
    private $tracker_url;
    private $is_enabled;
    private $tracker;

    public function __construct() {
        // Initialize defaults
        $this->site_id = '';
        $this->tracker_url = '';
        $this->is_enabled = false;

        // Check for WP-Piwik to get settings
        if ( class_exists( 'WP_Piwik' ) ) {
            if ( ! isset( $GLOBALS['wp-piwik'] ) ) {
                $GLOBALS['wp-piwik'] = new WP_Piwik();
            }
            $wp_piwik = $GLOBALS['wp-piwik'];

            // Get Site ID
            if ( method_exists( $wp_piwik, 'getOption' ) ) {
                $this->site_id = $wp_piwik->getOption( 'site_id' );
            }

            // Attempt to retrieve Tracker URL
            if ( method_exists( $wp_piwik, 'getMatomoUrl' ) ) {
                $this->tracker_url = rtrim( $wp_piwik->getMatomoUrl() ?: '', '/' );
            } elseif ( method_exists( $wp_piwik, 'getPiwikUrl' ) ) {
                $this->tracker_url = rtrim( $wp_piwik->getPiwikUrl() ?: '', '/' );
            }

            // // Fallback to getOption('piwik_url')
            // if ( empty( $this->tracker_url ) && method_exists( $wp_piwik, 'getOption' ) ) {
            //     $this->tracker_url = rtrim( $wp_piwik->getOption( 'piwik_url' ) ?: '', '/' );
            // }

            // Fallback to global settings
            if ( empty( $this->tracker_url ) ) {
                $global_settings = get_option( 'wp_piwik_global_settings', [] );
                $this->tracker_url = isset( $global_settings['piwik_url'] ) ? rtrim( $global_settings['piwik_url'], '/' ) : '';
            }

            // // Debug WP-Piwik internals
            // if ( empty( $this->tracker_url ) && $this->site_id ) {
            //     $this->tracker_url = 'https://analytics.saltrivercanyon.com'; // Temporary fallback
            //     error_log( 'PMPro Matomo: Tracker URL not found in WP-Piwik settings, using fallback: ' . $this->tracker_url );
            // }

            // Validate URL and initialize MatomoTracker
            if ( $this->site_id && $this->tracker_url && filter_var( $this->tracker_url, FILTER_VALIDATE_URL ) ) {
                $this->is_enabled = true;
                if ( file_exists( PMPRO_MATOMO_DIR . '/includes/MatomoTracker.php' ) ) {
                    require_once PMPRO_MATOMO_DIR . '/includes/MatomoTracker.php';
                    MatomoTracker::$URL = $this->tracker_url;
                    $this->tracker = new MatomoTracker( $this->site_id );
                    $this->register_hooks();
                } else {
                    $this->is_enabled = false;
                    error_log( 'PMPro Matomo: MatomoTracker.php not found at ' . PMPRO_MATOMO_DIR . '/includes/MatomoTracker.php' );
                }
            }
        }

        // Debug logging
        error_log( 'PMPro Matomo Tracking: Initialized - Site ID: ' . ($this->site_id ?: 'not set') . ', Tracker URL: ' . ($this->tracker_url ?: 'not set') . ', Enabled: ' . ($this->is_enabled ? 'yes' : 'no') );
        if ( class_exists( 'WP_Piwik' ) ) {
            error_log( 'PMPro Matomo: WP-Piwik Global Settings: ' . print_r( get_option( 'wp_piwik_global_settings', [] ), true ) );
            error_log( 'PMPro Matomo: WP-Piwik Site Settings: ' . print_r( get_option( 'wp_piwik_settings', [] ), true ) );
        }
    }

    public function register_hooks() {
        add_action( 'wp_head', [ $this, 'add_tracking_code' ], 10 );
        add_action( 'pmpro_after_checkout', [ $this, 'track_membership_signup' ], 10, 2 );
        add_action( 'pmpro_after_change_membership_level', [ $this, 'track_level_change' ], 10, 3 );
    }

    public function add_tracking_code() {
        if ( ! $this->tracker ) {
            return;
        }
        $this->tracker->doTrackPageView( get_the_title() );
        ?>
        <!-- Matomo PHP Tracker -->
        <script type="text/javascript">
            console.log('Matomo page view tracked: <?php echo esc_js( get_the_title() ); ?>');
        </script>
        <?php
    }

    public function track_membership_signup( $user_id, $order ) {
        if ( ! $this->tracker ) {
            return;
        }
        $level = pmpro_getLevel( $order->membership_id );
        $price = $order->total;
        $this->tracker->doTrackEvent( 'Membership', 'Signup', $level->name, $price );
        $this->tracker->doTrackGoal( 1, $price ); // Assuming Goal ID 1
        ?>
        <script type="text/javascript">
            console.log('Matomo event tracked: Membership Signup - <?php echo esc_js( $level->name ); ?> - <?php echo esc_js( $price ); ?>');
        </script>
        <?php
    }

    public function track_level_change( $level_id, $user_id, $cancel_level ) {
        if ( ! $this->tracker ) {
            return;
        }
        $level = pmpro_getLevel( $level_id );
        $level_name = $level ? $level->name : 'None (Cancelled)';
        $this->tracker->doTrackEvent( 'Membership', 'Level Change', $level_name );
        ?>
        <script type="text/javascript">
            console.log('Matomo event tracked: Membership Level Change - <?php echo esc_js( $level_name ); ?>');
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