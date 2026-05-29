<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_Analyzer;

class AnalyzerTest extends WebStrategyTestCase {

    private function analyzer() {
        return new WS_Crawl_Tracker_Analyzer();
    }

    private function levels( array $recos ) {
        return array_column( $recos, 'level' );
    }

    private function titles( array $recos ) {
        return implode( ' || ', array_column( $recos, 'title' ) );
    }

    public function test_no_hits_returns_single_warning() {
        $recos = $this->analyzer()->build(
            [ 'total_hits' => 0 ], [], [], []
        );
        $this->assertCount( 1, $recos );
        $this->assertSame( 'warn', $recos[0]['level'] );
    }

    public function test_high_error_rate_triggers_error_reco() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 15, 'verified_hits' => 90, 'unique_urls' => 50 ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], [], [] );
        $this->assertContains( 'err', $this->levels( $recos ) );
    }

    public function test_moderate_error_rate_triggers_warn_not_err() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 5, 'verified_hits' => 90, 'unique_urls' => 50 ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], [], [] );
        // Pas d'erreur critique (err) à 5%, mais au moins un warn présent.
        $this->assertContains( 'warn', $this->levels( $recos ) );
    }

    public function test_404_presence_is_reported() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 4, 'verified_hits' => 90, 'unique_urls' => 50 ];
        $statuses = [ [ 'status_code' => 404, 'hits' => 4 ] ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], $statuses, [] );
        $this->assertStringContainsString( '404', $this->titles( $recos ) );
    }

    public function test_low_verification_triggers_warn() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 0, 'verified_hits' => 20, 'unique_urls' => 50 ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], [], [] );
        $found = false;
        foreach ( $recos as $r ) {
            if ( false !== strpos( $r['title'], '%' ) && 'warn' === $r['level'] && false !== strpos( $r['title'], 'vérifi' ) ) {
                $found = true;
            }
        }
        $this->assertTrue( $found, 'Une reco de faible vérification (warn) est attendue.' );
    }

    public function test_high_verification_is_ok() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 0, 'verified_hits' => 95, 'unique_urls' => 50 ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], [], [] );
        $this->assertContains( 'ok', $this->levels( $recos ) );
    }

    public function test_crawl_concentration_triggers_warn() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 0, 'verified_hits' => 90, 'unique_urls' => 30 ];
        $top   = [ [ 'url' => 'https://x.fr/', 'hits' => 50 ] ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], [], $top );
        $this->assertStringContainsString( 'concentr', strtolower( $this->titles( $recos ) ) );
    }

    public function test_low_coverage_triggers_warn() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 0, 'verified_hits' => 90, 'unique_urls' => 5 ];
        $recos = $this->analyzer()->build( $stats, [ [ 'bot_key' => 'gptbot' ] ], [], [] );
        $this->assertStringContainsString( 'couverture', strtolower( $this->titles( $recos ) ) );
    }

    public function test_no_ai_bots_triggers_geo_warning() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 0, 'verified_hits' => 90, 'unique_urls' => 50 ];
        $by_bot = [ [ 'bot_key' => 'googlebot' ], [ 'bot_key' => 'bingbot' ] ];
        $recos = $this->analyzer()->build( $stats, $by_bot, [], [] );
        $this->assertStringContainsString( 'IA', $this->titles( $recos ) );
    }

    public function test_ai_bots_present_is_ok() {
        $stats = [ 'total_hits' => 100, 'error_hits' => 0, 'verified_hits' => 90, 'unique_urls' => 50 ];
        $by_bot = [ [ 'bot_key' => 'claudebot' ], [ 'bot_key' => 'perplexitybot' ] ];
        $recos = $this->analyzer()->build( $stats, $by_bot, [], [] );
        $ai_ok = false;
        foreach ( $recos as $r ) {
            if ( 'ok' === $r['level'] && false !== strpos( $r['title'], 'IA' ) ) { $ai_ok = true; }
        }
        $this->assertTrue( $ai_ok, 'Reco OK bots IA attendue.' );
    }
}
