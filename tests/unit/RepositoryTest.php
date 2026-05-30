<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_Repository;

/**
 * wpdb espion : capture les requêtes et permet de scénariser les retours.
 */
class SpyWpdb extends \wpdb {
    public $queries  = [];
    public $rows     = [];
    public $row      = [];
    public $var      = 0;
    public $deleted  = 0;

    public function get_results( $sql, $output = ARRAY_A ) { $this->queries[] = $sql; return $this->rows; }
    public function get_row( $sql, $output = ARRAY_A ) { $this->queries[] = $sql; return $this->row; }
    public function get_var( $sql ) { $this->queries[] = $sql; return $this->var; }
    public function query( $sql ) { $this->queries[] = $sql; return $this->deleted; }
    public function last() { return end( $this->queries ); }
}

class RepositoryTest extends WebStrategyTestCase {

    private function repo() {
        global $wpdb;
        $wpdb = new SpyWpdb();
        return [ new WS_Crawl_Tracker_Repository(), $wpdb ];
    }

    public function test_table_uses_prefix() {
        global $wpdb;
        $wpdb = new SpyWpdb();
        $this->assertSame( 'wp_wsct_hits', WS_Crawl_Tracker_Repository::table() );
    }

    public function test_insert_truncates_and_hashes_url() {
        global $wpdb;
        $wpdb = new SpyWpdb();
        \WP_Mock::userFunction( 'current_time', [ 'return' => '2026-05-01 12:00:00' ] );

        $repo = new WS_Crawl_Tracker_Repository();
        $long_url = 'https://x.fr/' . str_repeat( 'a', 3000 );
        $id = $repo->insert( [
            'bot_key'  => 'googlebot',
            'bot_name' => 'Googlebot',
            'url'      => $long_url,
            'method'   => 'GET',
            'status_code' => 200,
        ] );

        $this->assertSame( 123, $id );
        $this->assertLessThanOrEqual( 2048, strlen( $wpdb->inserted['url'] ) );
        $this->assertSame( md5( $long_url ), $wpdb->inserted['url_hash'] );
        $this->assertSame( 0, $wpdb->inserted['is_verified'] );
    }

    public function test_insert_sets_verified_flag() {
        global $wpdb;
        $wpdb = new SpyWpdb();
        \WP_Mock::userFunction( 'current_time', [ 'return' => '2026-05-01 12:00:00' ] );

        $repo = new WS_Crawl_Tracker_Repository();
        $repo->insert( [ 'url' => 'https://x.fr/', 'is_verified' => true ] );
        $this->assertSame( 1, $wpdb->inserted['is_verified'] );
    }

    public function test_get_stats_builds_aggregate_query() {
        [ $repo, $wpdb ] = $this->repo();
        $wpdb->row = [ 'total_hits' => 10, 'unique_urls' => 4, 'distinct_bots' => 2, 'error_hits' => 1, 'verified_hits' => 8, 'first_hit' => null, 'last_hit' => null ];

        $stats = $repo->get_stats( 30 );

        $this->assertSame( 10, $stats['total_hits'] );
        $this->assertSame( 4, $stats['unique_urls'] );
        $sql = $wpdb->last();
        $this->assertStringContainsString( 'COUNT(*)', $sql );
        $this->assertStringContainsString( 'wp_wsct_hits', $sql );
    }

    public function test_get_stats_filters_by_bot_when_provided() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_stats( 30, 'googlebot' );
        $this->assertStringContainsString( 'bot_key', $wpdb->last() );
    }

    public function test_get_timeline_groups_by_day() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_timeline_daily( 7 );
        $this->assertStringContainsString( 'DATE(hit_time)', $wpdb->last() );
        $this->assertStringContainsString( 'GROUP BY', $wpdb->last() );
    }

    public function test_get_top_pages_orders_and_limits() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_top_pages( 30, 25 );
        $sql = $wpdb->last();
        $this->assertStringContainsString( 'ORDER BY hits DESC', $sql );
        $this->assertStringContainsString( 'LIMIT', $sql );
    }

    public function test_get_session_path_orders_chronologically() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_session_path( 'abc123' );
        $sql = $wpdb->last();
        $this->assertStringContainsString( 'session_id', $sql );
        $this->assertStringContainsString( 'ORDER BY hit_time ASC', $sql );
    }

    public function test_get_hourly_distribution_uses_hour_function() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_hourly_distribution( 30 );
        $this->assertStringContainsString( 'HOUR(hit_time)', $wpdb->last() );
    }

    public function test_purge_older_than_deletes_with_cutoff() {
        [ $repo, $wpdb ] = $this->repo();
        $wpdb->deleted = 42;
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );

        $deleted = $repo->purge_older_than( 90 );
        $this->assertSame( 42, $deleted );
        $this->assertStringContainsString( 'DELETE FROM', $wpdb->last() );
    }

    public function test_truncate_runs_truncate_table() {
        [ $repo, $wpdb ] = $this->repo();
        \WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );
        $repo->truncate();
        $this->assertStringContainsString( 'TRUNCATE TABLE', $wpdb->last() );
    }

    public function test_count_all_returns_int() {
        [ $repo, $wpdb ] = $this->repo();
        $wpdb->var = 7;
        $this->assertSame( 7, $repo->count_all() );
    }

    public function test_get_recent_hits_grouped_buckets_by_day() {
        [ $repo, $wpdb ] = $this->repo();
        $wpdb->rows = [
            [ 'url' => '/a', 'hit_time' => '2026-05-29 09:00:00', 'bot_name' => 'Googlebot', 'status_code' => 200, 'is_verified' => 1 ],
            [ 'url' => '/b', 'hit_time' => '2026-05-29 08:00:00', 'bot_name' => 'Googlebot', 'status_code' => 200, 'is_verified' => 1 ],
            [ 'url' => '/c', 'hit_time' => '2026-05-28 12:00:00', 'bot_name' => 'Bingbot',   'status_code' => 404, 'is_verified' => 0 ],
        ];
        $grouped = $repo->get_recent_hits_grouped( 200 );

        $this->assertArrayHasKey( '2026-05-29', $grouped );
        $this->assertArrayHasKey( '2026-05-28', $grouped );
        $this->assertCount( 2, $grouped['2026-05-29'] );
        $this->assertCount( 1, $grouped['2026-05-28'] );
    }

    public function test_get_all_for_export_selects_full_columns() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_all_for_export( 30 );
        $sql = $wpdb->last();
        $this->assertStringContainsString( 'referer', $sql );
        $this->assertStringContainsString( 'content_type', $sql );
        $this->assertStringNotContainsString( 'LIMIT', $sql );
    }

    public function test_get_all_for_export_filters_by_bot() {
        [ $repo, $wpdb ] = $this->repo();
        $repo->get_all_for_export( 30, 'googlebot' );
        $this->assertStringContainsString( 'bot_key', $wpdb->last() );
    }
}
