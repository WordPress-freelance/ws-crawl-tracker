<?php

defined( 'ABSPATH' ) || exit;

class WS_Crawl_Tracker {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = WS_CRAWL_TRACKER_SLUG;
        $this->version     = WS_CRAWL_TRACKER_VERSION;
        $this->load_dependencies();
        $this->set_locale();
        $this->define_public_hooks();
        $this->define_admin_hooks();
        $this->define_cron_hooks();
    }

    private function load_dependencies() {
        require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-loader.php';
        require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-i18n.php';
        require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-detector.php';
        require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-repository.php';
        require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-analyzer.php';
        require_once WS_CRAWL_TRACKER_PATH . 'public/class-ws-crawl-tracker-public.php';
        require_once WS_CRAWL_TRACKER_PATH . 'admin/class-ws-crawl-tracker-admin.php';
        $this->loader = new WS_Crawl_Tracker_Loader();
    }

    private function set_locale() {
        $i18n = new WS_Crawl_Tracker_i18n();
        $this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
    }

    private function define_public_hooks() {
        $public = new WS_Crawl_Tracker_Public( $this->plugin_name, $this->version );
        // Détection tôt, après résolution de la requête.
        $this->loader->add_action( 'template_redirect', $public, 'detect', 1 );
        // Enregistrement au shutdown : le status code est figé.
        $this->loader->add_action( 'shutdown', $public, 'record' );
    }

    private function define_admin_hooks() {
        $admin = new WS_Crawl_Tracker_Admin( $this->plugin_name, $this->version );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu',            $admin, 'add_menu' );
        $this->loader->add_filter( 'admin_body_class',      $admin, 'add_admin_body_class' );
        $this->loader->add_action( 'admin_head',            $admin, 'inline_reset_css' );
        $this->loader->add_action( 'admin_post_wsct_save_settings', $admin, 'handle_save_settings' );
        $this->loader->add_action( 'admin_post_wsct_export',        $admin, 'handle_export_csv' );
        $this->loader->add_action( 'wp_ajax_wsct_get_data',     $admin, 'ajax_get_data' );
        $this->loader->add_action( 'wp_ajax_wsct_get_session',  $admin, 'ajax_get_session' );
        $this->loader->add_action( 'wp_ajax_wsct_purge',        $admin, 'ajax_purge' );
    }

    private function define_cron_hooks() {
        $admin = new WS_Crawl_Tracker_Admin( $this->plugin_name, $this->version );
        $this->loader->add_action( 'wp',                $admin, 'maybe_schedule_purge' );
        $this->loader->add_action( 'wsct_daily_purge',  $admin, 'run_purge' );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() { return $this->plugin_name; }
    public function get_version()     { return $this->version; }
    public function get_loader()      { return $this->loader; }
}
