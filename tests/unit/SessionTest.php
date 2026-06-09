<?php
namespace WS_Crawl_Tracker\Tests\Unit;
use WS_Crawl_Tracker_Public;
use WP_Mock;

require_once WS_CRAWL_TRACKER_PATH . 'public/class-ws-crawl-tracker-public.php';

class SessionTest extends WebStrategyTestCase {

    private function session_for( $public, $bot, $ip ) {
        return $this->invoke_method( $public, 'session_id', [ $bot, $ip ] );
    }

    public function test_same_ip_same_session() {
        $store = [];
        WP_Mock::userFunction( 'get_option', [ 'return' => [] ] );
        WP_Mock::userFunction( 'get_transient', [
            'return' => function ( $k ) use ( &$store ) { return $store[$k] ?? false; },
        ] );
        WP_Mock::userFunction( 'set_transient', [
            'return' => function ( $k, $v, $ttl = 0 ) use ( &$store ) { $store[$k] = $v; return true; },
        ] );

        $p = new WS_Crawl_Tracker_Public( 'ws-crawl-tracker', '1.1.0' );
        $s1 = $this->session_for( $p, 'googlebot', '66.249.66.1' );
        $s2 = $this->session_for( $p, 'googlebot', '66.249.66.1' );
        $this->assertSame( $s1, $s2 );
    }

    public function test_different_ip_same_bot_keeps_session() {
        $store = [];
        WP_Mock::userFunction( 'get_option', [ 'return' => [] ] );
        WP_Mock::userFunction( 'get_transient', [
            'return' => function ( $k ) use ( &$store ) { return $store[$k] ?? false; },
        ] );
        WP_Mock::userFunction( 'set_transient', [
            'return' => function ( $k, $v, $ttl = 0 ) use ( &$store ) { $store[$k] = $v; return true; },
        ] );

        $p = new WS_Crawl_Tracker_Public( 'ws-crawl-tracker', '1.1.0' );
        $s1 = $this->session_for( $p, 'googlebot', '66.249.66.1' );
        $s2 = $this->session_for( $p, 'googlebot', '66.249.66.7' );
        $this->assertSame( $s1, $s2, 'Meme bot, IP differentes du pool = MEME session (fix)' );
    }
}
