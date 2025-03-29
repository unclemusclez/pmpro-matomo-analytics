<?php
class PMPro_Matomo_Tracking {
    private $site_id;
    private $tracker_url;
    private $is_enabled;

    public function __construct() {
        $this->site_id = get_option( 'pmpro_matomo_site_id', '' );
        $this->tracker_url = get_option( 'pmpro_matomo_tracker_url', '' );
        $this->is_enabled = get_option( 'pmpro_matomo_enable_tracking', 'no' ) === 'yes' && ! empty( $this->site_id ) && ! empty( $this->tracker_url );

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