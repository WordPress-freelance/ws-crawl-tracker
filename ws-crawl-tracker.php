<?php
/**
 * Plugin Name:       WS Crawl Tracker
 * Plugin URI:        https://wordpress-freelance.com/plugins/ws-crawl-tracker/
 * Description:       Tracez le passage de Googlebot et des autres robots SEO/IA sur votre site. Timeline du crawl, graphe de navigation, heatmap des pages et statistiques détaillées dans une table dédiée.
 * Version:           1.0.0
 * Author:            WebStrategy
 * Author URI:        https://wordpress-freelance.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ws-crawl-tracker
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'WS_CRAWL_TRACKER_VERSION', '1.0.0' );
define( 'WS_CRAWL_TRACKER_SLUG',    'ws-crawl-tracker' );
define( 'WS_CRAWL_TRACKER_FILE',    __FILE__ );
define( 'WS_CRAWL_TRACKER_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WS_CRAWL_TRACKER_URL',     plugin_dir_url( __FILE__ ) );

require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-activator.php';
require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker-deactivator.php';

register_activation_hook( __FILE__,   [ 'WS_Crawl_Tracker_Activator',   'activate' ] );
register_deactivation_hook( __FILE__, [ 'WS_Crawl_Tracker_Deactivator', 'deactivate' ] );

function ws_crawl_tracker_run() {
    require_once WS_CRAWL_TRACKER_PATH . 'includes/class-ws-crawl-tracker.php';
    $plugin = new WS_Crawl_Tracker();
    $plugin->run();
}
add_action( 'plugins_loaded', 'ws_crawl_tracker_run' );
