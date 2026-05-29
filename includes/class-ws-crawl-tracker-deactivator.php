<?php

defined( 'ABSPATH' ) || exit;

class WS_Crawl_Tracker_Deactivator {

    /**
     * Retire le cron de purge. Les données restent (uninstall.php nettoie tout).
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'wsct_daily_purge' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'wsct_daily_purge' );
        }
    }
}
