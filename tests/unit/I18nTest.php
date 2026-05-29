<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_i18n;
use WP_Mock;

class I18nTest extends WebStrategyTestCase {

    public function test_load_plugin_textdomain_called_with_slug() {
        $i18n = new WS_Crawl_Tracker_i18n();

        WP_Mock::userFunction( 'load_plugin_textdomain', [
            'times' => 1,
            'args'  => [ 'ws-crawl-tracker', false, \WP_Mock\Functions::type( 'string' ) ],
            'return' => true,
        ] );

        $i18n->load_plugin_textdomain();
        $this->assertConditionsMet();
    }
}
