<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_Detector;

class DetectorTest extends WebStrategyTestCase {

    private function bots() {
        return [
            'googlebot' => [ 'name' => 'Googlebot', 'enabled' => 1, 'ua' => [ 'Googlebot', 'Storebot-Google' ], 'hosts' => [ '.googlebot.com' ] ],
            'gptbot'    => [ 'name' => 'GPTBot',    'enabled' => 1, 'ua' => [ 'GPTBot' ],     'hosts' => [ '.openai.com' ] ],
            'bingbot'   => [ 'name' => 'Bingbot',   'enabled' => 0, 'ua' => [ 'bingbot' ],    'hosts' => [ '.search.msn.com' ] ],
        ];
    }

    public function test_matches_googlebot_ua() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        $ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        $this->assertSame( 'googlebot', $d->match_bot_key( $ua ) );
    }

    public function test_matches_secondary_ua_pattern() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        $this->assertSame( 'googlebot', $d->match_bot_key( 'Storebot-Google/1.0' ) );
    }

    public function test_matches_gptbot_case_insensitive() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        $this->assertSame( 'gptbot', $d->match_bot_key( 'Mozilla/5.0 GPTBOT/1.1' ) );
    }

    public function test_ignores_disabled_bot() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        // bingbot est désactivé → null malgré le match UA.
        $this->assertNull( $d->match_bot_key( 'Mozilla/5.0 (compatible; bingbot/2.0)' ) );
    }

    public function test_returns_null_for_human_ua() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        $this->assertNull( $d->match_bot_key( 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120' ) );
    }

    public function test_returns_null_for_empty_ua() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        $this->assertNull( $d->match_bot_key( '' ) );
        $this->assertNull( $d->match_bot_key( '   ' ) );
    }

    public function test_verify_ip_returns_false_when_dns_disabled() {
        $d = new WS_Crawl_Tracker_Detector( $this->bots(), false );
        $this->assertFalse( $d->verify_ip( '66.249.66.1', 'googlebot' ) );
    }

    public function test_resolve_ip_prefers_cloudflare_header() {
        $server = [
            'HTTP_CF_CONNECTING_IP' => '66.249.66.1',
            'HTTP_X_FORWARDED_FOR'  => '10.0.0.1, 192.168.1.1',
            'REMOTE_ADDR'           => '127.0.0.1',
        ];
        $this->assertSame( '66.249.66.1', WS_Crawl_Tracker_Detector::resolve_ip( $server ) );
    }

    public function test_resolve_ip_uses_first_xff_when_no_cf() {
        $server = [
            'HTTP_X_FORWARDED_FOR' => '66.249.66.5, 10.0.0.1',
            'REMOTE_ADDR'          => '127.0.0.1',
        ];
        $this->assertSame( '66.249.66.5', WS_Crawl_Tracker_Detector::resolve_ip( $server ) );
    }

    public function test_resolve_ip_falls_back_to_remote_addr() {
        $server = [ 'REMOTE_ADDR' => '203.0.113.7' ];
        $this->assertSame( '203.0.113.7', WS_Crawl_Tracker_Detector::resolve_ip( $server ) );
    }

    public function test_resolve_ip_skips_invalid_and_returns_empty() {
        $server = [ 'HTTP_X_FORWARDED_FOR' => 'not-an-ip', 'REMOTE_ADDR' => 'garbage' ];
        $this->assertSame( '', WS_Crawl_Tracker_Detector::resolve_ip( $server ) );
    }
}
