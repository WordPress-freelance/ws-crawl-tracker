<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Suppression de la table de hits.
$table = $wpdb->prefix . 'wsct_hits';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

// Suppression des options.
delete_option( 'wsct_settings' );
delete_option( 'wsct_db_version' );

// Nettoyage des transients du plugin (sessions + cache DNS).
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '\\_transient\\_wsct\\_%'
        OR option_name LIKE '\\_transient\\_timeout\\_wsct\\_%'"
);

if ( function_exists( 'wp_cache_flush' ) ) {
    wp_cache_flush();
}

// Nettoyage du cron.
$timestamp = wp_next_scheduled( 'wsct_daily_purge' );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'wsct_daily_purge' );
}
