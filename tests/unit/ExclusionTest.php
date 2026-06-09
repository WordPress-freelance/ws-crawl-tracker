<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WP_Mock;

require_once WS_CRAWL_TRACKER_PATH . 'public/class-ws-crawl-tracker-public.php';

class ExclusionTest extends WebStrategyTestCase {

    private function make_public( array $settings ) {
        WP_Mock::userFunction( 'get_option', [ 'return' => $settings ] );
        return new \WS_Crawl_Tracker_Public( 'ws-crawl-tracker', '1.2.0' );
    }

    private function default_settings() {
        return [
            'excluded_ua'    => [ 'WS-Claude-Bridge', 'WS-GEO-Audit' ],
            'excluded_paths' => [ '/wp-json/ws-bridge/v1/' ],
        ];
    }

    public function test_excludes_bridge_user_agent() {
        $p = $this->make_public( $this->default_settings() );
        $this->assertTrue( $p->is_excluded( 'WS-Claude-Bridge/1.0', '/une-page/' ) );
    }

    public function test_excludes_geo_audit_user_agent() {
        $p = $this->make_public( $this->default_settings() );
        $this->assertTrue( $p->is_excluded( 'Mozilla/5.0 WS-GEO-Audit', '/' ) );
    }

    public function test_ua_match_is_case_insensitive() {
        $p = $this->make_public( $this->default_settings() );
        $this->assertTrue( $p->is_excluded( 'ws-claude-bridge/2.0', '/' ) );
    }

    public function test_excludes_bridge_rest_path() {
        $p = $this->make_public( $this->default_settings() );
        $this->assertTrue( $p->is_excluded( 'Googlebot', '/wp-json/ws-bridge/v1/geo-score' ) );
    }

    public function test_path_match_ignores_query_string() {
        $p = $this->make_public( $this->default_settings() );
        $this->assertTrue( $p->is_excluded( 'Googlebot', '/wp-json/ws-bridge/v1/gsc?days=28&_=123' ) );
    }

    public function test_legitimate_bot_not_excluded() {
        $p = $this->make_public( $this->default_settings() );
        $this->assertFalse( $p->is_excluded(
            'Mozilla/5.0 (compatible; Googlebot/2.1)',
            '/articles/mon-article/'
        ) );
    }

    public function test_empty_exclusion_lists_never_match() {
        $p = $this->make_public( [ 'excluded_ua' => [], 'excluded_paths' => [] ] );
        $this->assertFalse( $p->is_excluded( 'WS-Claude-Bridge', '/wp-json/ws-bridge/v1/' ) );
    }

    public function test_blank_entries_are_ignored() {
        // Une ligne vide ne doit pas exclure tout le trafic.
        $p = $this->make_public( [ 'excluded_ua' => [ '', '   ' ], 'excluded_paths' => [ '' ] ] );
        $this->assertFalse( $p->is_excluded( 'Googlebot', '/' ) );
    }
}
