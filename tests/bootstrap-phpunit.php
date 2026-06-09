<?php
/**
 * Bootstrap PHPUnit — WS Crawl Tracker
 * Ordre critique des 6 étapes.
 */

// ── Étape 1 : Constantes ─────────────────────────────────────────────────────
define( 'ABSPATH',         '/tmp/wp/' );
define( 'WP_DEBUG',        true );
define( 'OBJECT',          'OBJECT' );
define( 'ARRAY_A',         'ARRAY_A' );
define( 'DAY_IN_SECONDS',  86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'WEEK_IN_SECONDS', 604800 );

define( 'WS_CRAWL_TRACKER_VERSION', '1.0.0' );
define( 'WS_CRAWL_TRACKER_SLUG',    'ws-crawl-tracker' );
define( 'WS_CRAWL_TRACKER_FILE',    dirname( __DIR__ ) . '/ws-crawl-tracker.php' );
define( 'WS_CRAWL_TRACKER_PATH',    dirname( __DIR__ ) . '/' );
define( 'WS_CRAWL_TRACKER_URL',     'https://example.com/wp-content/plugins/ws-crawl-tracker/' );

// ── Étape 2 : Autoloader ─────────────────────────────────────────────────────
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// ── Étape 3 : WP_Mock::bootstrap() ───────────────────────────────────────────
WP_Mock::bootstrap();

// ── Étape 4a : Stubs purement utilitaires (jamais mockés) ────────────────────
if ( ! function_exists( 'absint' ) ) {
    function absint( $v ) { return abs( (int) $v ); }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_textarea' ) ) {
    function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
}
if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
}
if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $t, $d = 'default' ) { return esc_attr( $t ); }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $s ) { return trim( preg_replace( '/\s+/', ' ', (string) $s ) ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
}
if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
}
if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) { return trailingslashit( dirname( $file ) ); }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) { return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
}
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_e' ) ) {
    function _e( $text, $domain = 'default' ) { echo $text; }
}
if ( ! function_exists( 'wp_rand' ) ) {
    function wp_rand( $min = 0, $max = 0 ) { return $max > 0 ? mt_rand( $min, $max ) : mt_rand(); }
}

// ── Étape 4b : Stubs mockables ───────────────────────────────────────────────
require_once __DIR__ . '/stubs.php';

// ── Étape 5 : Classes stubs ───────────────────────────────────────────────────
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $errors = [];
        public function __construct( $code = '', $msg = '', $data = '' ) {
            if ( $code ) $this->errors[ $code ][] = $msg;
        }
        public function get_error_message( $code = '' ) {
            return $code && isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
        }
    }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $t ) { return $t instanceof WP_Error; }
}
if ( ! class_exists( 'wpdb' ) ) {
    class wpdb {
        public $prefix     = 'wp_';
        public $options    = 'wp_options';
        public $last_query = '';
        public $insert_id  = 0;
        public $inserted   = null;
        public function query( $sql ) { $this->last_query = $sql; return true; }
        public function get_results( $sql, $output = ARRAY_A ) { $this->last_query = $sql; return []; }
        public function get_row( $sql, $output = ARRAY_A ) { $this->last_query = $sql; return []; }
        public function get_var( $sql ) { $this->last_query = $sql; return 0; }
        public function insert( $table, $data, $formats = [] ) { $this->inserted = $data; $this->insert_id = 123; return true; }
        public function prepare( $sql, ...$args ) {
            if ( count( $args ) === 1 && is_array( $args[0] ) ) { $args = $args[0]; }
            $i = 0;
            return preg_replace_callback( '/%[sdf]/', function ( $m ) use ( &$i, $args ) {
                $v = $args[ $i ] ?? '';
                $i++;
                return $m[0] === '%d' ? (string) (int) $v : "'" . $v . "'";
            }, $sql );
        }
        public function get_charset_collate() { return 'DEFAULT CHARSET=utf8mb4'; }
    }
}

// ── Étape 6 : Classes plugin ──────────────────────────────────────────────────
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-loader.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-i18n.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-activator.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-deactivator.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-detector.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-repository.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-analyzer.php';
require_once WS_CRAWL_TRACKER_PATH . 'admin/class-ws-crawl-tracker-admin.php';
