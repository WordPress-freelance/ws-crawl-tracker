<?php

defined( 'ABSPATH' ) || exit;

class WS_Crawl_Tracker_Admin {

    private $plugin_name;
    private $version;

    const MENU_SLUG  = 'ws-crawl-tracker';
    const SCREEN_ID  = 'toplevel_page_ws-crawl-tracker';

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    // -------------------------------------------------------------------------
    // Menu
    // -------------------------------------------------------------------------

    public function add_menu() {
        add_menu_page(
            __( 'Crawl Tracker', 'ws-crawl-tracker' ),
            __( 'Crawl Tracker', 'ws-crawl-tracker' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_page' ],
            'dashicons-search',
            66
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Tableau de bord', 'ws-crawl-tracker' ),
            __( 'Tableau de bord', 'ws-crawl-tracker' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Réglages', 'ws-crawl-tracker' ),
            __( 'Réglages', 'ws-crawl-tracker' ),
            'manage_options',
            self::MENU_SLUG . '-settings',
            [ $this, 'render_settings' ]
        );
    }

    public function render_page() {
        require WS_CRAWL_TRACKER_PATH . 'admin/partials/ws-crawl-tracker-admin-dashboard.php';
    }

    public function render_settings() {
        require WS_CRAWL_TRACKER_PATH . 'admin/partials/ws-crawl-tracker-admin-settings.php';
    }

    // -------------------------------------------------------------------------
    // Enqueue
    // -------------------------------------------------------------------------

    private function is_plugin_screen() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return false;
        }
        return false !== strpos( $screen->id, self::MENU_SLUG );
    }

    public function enqueue_styles() {
        if ( ! $this->is_plugin_screen() ) {
            return;
        }
        wp_enqueue_style(
            $this->plugin_name,
            WS_CRAWL_TRACKER_URL . 'admin/css/ws-crawl-tracker-admin.css',
            [],
            $this->version
        );
    }

    public function enqueue_scripts() {
        if ( ! $this->is_plugin_screen() ) {
            return;
        }

        wp_enqueue_script(
            'chartjs',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        wp_enqueue_script(
            $this->plugin_name,
            WS_CRAWL_TRACKER_URL . 'admin/js/ws-crawl-tracker-admin.js',
            [ 'chartjs' ],
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_name,
            'wsctData',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wsct_admin' ),
                'i18n'    => [
                    'loading'    => __( 'Chargement…', 'ws-crawl-tracker' ),
                    'noData'     => __( 'Aucune donnée sur cette période.', 'ws-crawl-tracker' ),
                    'error'      => __( 'Erreur de chargement.', 'ws-crawl-tracker' ),
                    'hits'       => __( 'Hits', 'ws-crawl-tracker' ),
                    'verified'   => __( 'Vérifiés', 'ws-crawl-tracker' ),
                    'confirmPurge' => __( 'Vider toutes les données de crawl ? Cette action est irréversible.', 'ws-crawl-tracker' ),
                ],
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Fix cadre blanc (Avada & thèmes tiers)
    // -------------------------------------------------------------------------

    public function add_admin_body_class( $classes ) {
        if ( $this->is_plugin_screen() ) {
            $classes .= ' wsct-page';
        }
        return $classes;
    }

    public function inline_reset_css() {
        if ( ! $this->is_plugin_screen() ) {
            return;
        }
        echo '<style id="wsct-reset">
        .wsct-page #wpwrap,
        .wsct-page #wpcontent,
        .wsct-page #wpbody,
        .wsct-page #wpbody-content { background:#14121C !important; }
        .wsct-page #wpbody,
        .wsct-page #wpbody-content { padding:0 !important; }
        .wsct-page .wrap,
        .wsct-page #wpcontent .wrap { margin:0 !important; padding:0 !important; background:#14121C !important; max-width:none !important; }
        .wsct-page #wpfooter { background:#14121C !important; }
        .ws-admin-wrap .ws-logo-mark { width:26px !important; height:auto !important; flex-shrink:0 !important; }
        .ws-admin-wrap .ws-title-logo { width:34px !important; height:34px !important; min-width:34px !important; flex-shrink:0 !important; }
        .ws-admin-wrap svg.ws-title-logo, .ws-admin-wrap svg.ws-logo-mark { max-width:none !important; }
        </style>';
    }

    // -------------------------------------------------------------------------
    // AJAX — données du dashboard
    // -------------------------------------------------------------------------

    public function ajax_get_data() {
        check_ajax_referer( 'wsct_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission refusée.', 'ws-crawl-tracker' ) ] );
            return;
        }

        $days    = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
        $days    = max( 1, min( 365, $days ) );
        $bot_key = isset( $_POST['bot'] ) ? sanitize_key( wp_unslash( $_POST['bot'] ) ) : '';
        $bot_key = '' !== $bot_key ? $bot_key : null;

        $repo     = new WS_Crawl_Tracker_Repository();
        $analyzer = new WS_Crawl_Tracker_Analyzer();

        $stats     = $repo->get_stats( $days, $bot_key );
        $by_bot    = $repo->get_hits_by_bot( $days );
        $timeline  = $repo->get_timeline_daily( $days, $bot_key );
        $top_pages = $repo->get_top_pages( $days, 25, $bot_key );
        $statuses  = $repo->get_status_breakdown( $days, $bot_key );
        $hourly    = $repo->get_hourly_distribution( $days, $bot_key );
        $sessions  = $repo->get_recent_sessions( $days, 30, $bot_key );
        $recent    = $repo->get_recent_hits( 100, $bot_key );
        $recos     = $analyzer->build( $stats, $by_bot, $statuses, $top_pages );

        wp_send_json_success( [
            'stats'           => $stats,
            'by_bot'          => $by_bot,
            'timeline'        => $timeline,
            'top_pages'       => $top_pages,
            'status_breakdown'=> $statuses,
            'hourly'          => $hourly,
            'sessions'        => $sessions,
            'recent'          => $recent,
            'recommendations' => $recos,
        ] );
    }

    public function ajax_get_session() {
        check_ajax_referer( 'wsct_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission refusée.', 'ws-crawl-tracker' ) ] );
            return;
        }

        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
        if ( '' === $session_id ) {
            wp_send_json_error( [ 'message' => __( 'Session invalide.', 'ws-crawl-tracker' ) ] );
            return;
        }

        $repo = new WS_Crawl_Tracker_Repository();
        $path = $repo->get_session_path( $session_id );

        wp_send_json_success( [ 'path' => $path ] );
    }

    public function ajax_purge() {
        check_ajax_referer( 'wsct_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission refusée.', 'ws-crawl-tracker' ) ] );
            return;
        }

        $repo = new WS_Crawl_Tracker_Repository();
        $repo->truncate();

        wp_send_json_success( [ 'message' => __( 'Données supprimées.', 'ws-crawl-tracker' ) ] );
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'ws-crawl-tracker' ) );
        }
        check_admin_referer( 'wsct_save_settings' );

        $existing = get_option( 'wsct_settings', [] );
        $bots     = $existing['bots'] ?? WS_Crawl_Tracker_Activator::default_bots();

        // Activation par bot.
        $enabled_bots = isset( $_POST['wsct_bots'] ) && is_array( $_POST['wsct_bots'] )
            ? array_map( 'sanitize_key', wp_unslash( $_POST['wsct_bots'] ) )
            : [];
        foreach ( $bots as $key => &$bot ) {
            $bot['enabled'] = in_array( $key, $enabled_bots, true ) ? 1 : 0;
        }
        unset( $bot );

        $settings = [
            'enabled'        => ! empty( $_POST['wsct_enabled'] ) ? 1 : 0,
            'verify_dns'     => ! empty( $_POST['wsct_verify_dns'] ) ? 1 : 0,
            'retention_days' => isset( $_POST['wsct_retention_days'] ) ? absint( $_POST['wsct_retention_days'] ) : 90,
            'bots'           => $bots,
        ];
        if ( $settings['retention_days'] < 1 ) {
            $settings['retention_days'] = 90;
        }

        update_option( 'wsct_settings', $settings );

        wp_safe_redirect( add_query_arg(
            [ 'page' => self::MENU_SLUG . '-settings', 'saved' => '1' ],
            admin_url( 'admin.php' )
        ) );
        $this->terminate();
    }

    /**
     * Arrête l'exécution après une redirection. Isolé pour être surchargeable
     * en test (où `exit` tuerait le process PHPUnit).
     *
     * @codeCoverageIgnore
     */
    protected function terminate() {
        exit;
    }

    // -------------------------------------------------------------------------
    // Cron de purge
    // -------------------------------------------------------------------------

    public function maybe_schedule_purge() {
        if ( ! wp_next_scheduled( 'wsct_daily_purge' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wsct_daily_purge' );
        }
    }

    public function run_purge() {
        $settings  = get_option( 'wsct_settings', [] );
        $retention = isset( $settings['retention_days'] ) ? absint( $settings['retention_days'] ) : 90;
        if ( $retention < 1 ) {
            return;
        }
        $repo = new WS_Crawl_Tracker_Repository();
        $repo->purge_older_than( $retention );
    }
}
