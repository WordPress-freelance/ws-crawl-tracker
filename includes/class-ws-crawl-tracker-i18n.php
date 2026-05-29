<?php

defined( 'ABSPATH' ) || exit;

class WS_Crawl_Tracker_i18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ws-crawl-tracker',
            false,
            dirname( plugin_basename( WS_CRAWL_TRACKER_FILE ) ) . '/languages/'
        );
    }
}
