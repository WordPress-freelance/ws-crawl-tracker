<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_Activator;

class ActivatorTest extends WebStrategyTestCase {

    public function test_default_bots_has_expected_keys() {
        $bots = WS_Crawl_Tracker_Activator::default_bots();

        foreach ( [ 'googlebot', 'bingbot', 'gptbot', 'claudebot', 'perplexitybot' ] as $key ) {
            $this->assertArrayHasKey( $key, $bots, "Bot manquant : {$key}" );
        }
    }

    public function test_each_bot_has_required_shape() {
        $bots = WS_Crawl_Tracker_Activator::default_bots();
        foreach ( $bots as $key => $bot ) {
            $this->assertArrayHasKey( 'name', $bot, "name manquant pour {$key}" );
            $this->assertArrayHasKey( 'enabled', $bot, "enabled manquant pour {$key}" );
            $this->assertArrayHasKey( 'ua', $bot, "ua manquant pour {$key}" );
            $this->assertArrayHasKey( 'hosts', $bot, "hosts manquant pour {$key}" );
            $this->assertIsArray( $bot['ua'] );
            $this->assertNotEmpty( $bot['ua'] );
            $this->assertIsArray( $bot['hosts'] );
        }
    }

    public function test_search_and_ai_bots_enabled_by_default() {
        $bots = WS_Crawl_Tracker_Activator::default_bots();
        $this->assertSame( 1, $bots['googlebot']['enabled'] );
        $this->assertSame( 1, $bots['gptbot']['enabled'] );
        $this->assertSame( 1, $bots['claudebot']['enabled'] );
        $this->assertSame( 1, $bots['perplexitybot']['enabled'] );
    }

    public function test_secondary_bots_disabled_by_default() {
        $bots = WS_Crawl_Tracker_Activator::default_bots();
        $this->assertSame( 0, $bots['applebot']['enabled'] );
        $this->assertSame( 0, $bots['yandexbot']['enabled'] );
        $this->assertSame( 0, $bots['duckduckbot']['enabled'] );
    }

    public function test_create_table_runs_dbdelta_with_table_name() {
        global $wpdb;
        $wpdb = new \wpdb();

        $captured = null;
        \WP_Mock::userFunction( 'dbDelta', [
            'times' => 1,
            'return' => function ( $sql ) use ( &$captured ) { $GLOBALS['__wsct_sql'] = $sql; return []; },
        ] );

        // ABSPATH/wp-admin/includes/upgrade.php est requis dans la méthode :
        // on crée un fichier vide pour satisfaire le require_once.
        $dir = ABSPATH . 'wp-admin/includes';
        if ( ! is_dir( $dir ) ) { mkdir( $dir, 0777, true ); }
        if ( ! file_exists( $dir . '/upgrade.php' ) ) { file_put_contents( $dir . '/upgrade.php', '<?php' ); }

        WS_Crawl_Tracker_Activator::create_table();

        $this->assertStringContainsString( 'wp_wsct_hits', $GLOBALS['__wsct_sql'] );
        $this->assertStringContainsString( 'session_id', $GLOBALS['__wsct_sql'] );
        $this->assertStringContainsString( 'hit_time', $GLOBALS['__wsct_sql'] );
    }
}
