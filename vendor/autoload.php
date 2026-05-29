<?php
/**
 * Autoloader manuel — remplace composer install (bloqué par proxy).
 * Ordre critique : Patchwork → Hamcrest → Mockery → WP_Mock → tests.
 */

require_once __DIR__ . '/antecedent/patchwork/Patchwork.php';
require_once __DIR__ . '/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';
require_once __DIR__ . '/mockery/mockery/library/Mockery.php';
require_once __DIR__ . '/mockery/mockery/library/helpers.php';
require_once __DIR__ . '/10up/wp_mock/php/WP_Mock.php';

spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'WP_Mock\\' ) === 0 ) {
        $rel  = str_replace( 'WP_Mock\\', '', $class );
        $file = __DIR__ . '/10up/wp_mock/php/WP_Mock/' . str_replace( '\\', '/', $rel ) . '.php';
        if ( file_exists( $file ) ) require_once $file;
        return;
    }
    if ( strpos( $class, 'Mockery\\' ) === 0 ) {
        $rel  = str_replace( 'Mockery\\', '', $class );
        $file = __DIR__ . '/mockery/mockery/library/Mockery/' . str_replace( '\\', '/', $rel ) . '.php';
        if ( file_exists( $file ) ) require_once $file;
        return;
    }
    if ( strpos( $class, 'Hamcrest_' ) === 0 ) {
        $file = __DIR__ . '/hamcrest/hamcrest-php/hamcrest/' . str_replace( '_', '/', $class ) . '.php';
        if ( file_exists( $file ) ) require_once $file;
        return;
    }
    if ( strpos( $class, 'Hamcrest\\' ) === 0 ) {
        $rel  = str_replace( 'Hamcrest\\', '', $class );
        $file = __DIR__ . '/hamcrest/hamcrest-php/hamcrest/Hamcrest/' . str_replace( '\\', '/', $rel ) . '.php';
        if ( file_exists( $file ) ) require_once $file;
        return;
    }
} );

spl_autoload_register( function ( $class ) {
    $prefix = 'WS_Crawl_Tracker\\Tests\\Unit\\';
    if ( strpos( $class, $prefix ) !== 0 ) return;
    $rel  = str_replace( $prefix, '', $class );
    $file = dirname( __DIR__ ) . '/tests/unit/' . str_replace( '\\', '/', $rel ) . '.php';
    if ( file_exists( $file ) ) require_once $file;
} );
