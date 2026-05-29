<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_Loader;
use WP_Mock;

class LoaderTest extends WebStrategyTestCase {

    public function test_add_action_registers_in_actions_array() {
        $loader = new WS_Crawl_Tracker_Loader();
        $obj    = new \stdClass();
        $loader->add_action( 'init', $obj, 'cb', 20, 2 );

        $actions = $loader->get_actions();
        $this->assertCount( 1, $actions );
        $this->assertSame( 'init', $actions[0]['hook'] );
        $this->assertSame( 20, $actions[0]['priority'] );
        $this->assertSame( 2, $actions[0]['accepted_args'] );
    }

    public function test_add_filter_registers_in_filters_array() {
        $loader = new WS_Crawl_Tracker_Loader();
        $obj    = new \stdClass();
        $loader->add_filter( 'the_content', $obj, 'filter_cb' );

        $filters = $loader->get_filters();
        $this->assertCount( 1, $filters );
        $this->assertSame( 'the_content', $filters[0]['hook'] );
        $this->assertSame( 10, $filters[0]['priority'] );
    }

    public function test_run_wires_hooks_to_wordpress() {
        $loader = new WS_Crawl_Tracker_Loader();
        $obj    = new \stdClass();
        $loader->add_action( 'template_redirect', $obj, 'detect', 1, 1 );
        $loader->add_filter( 'admin_body_class', $obj, 'add_class', 10, 1 );

        WP_Mock::expectActionAdded( 'template_redirect', [ $obj, 'detect' ], 1, 1 );
        WP_Mock::expectFilterAdded( 'admin_body_class', [ $obj, 'add_class' ], 10, 1 );

        $loader->run();
        $this->assertConditionsMet();
    }
}
