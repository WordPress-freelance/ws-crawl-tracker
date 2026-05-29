<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WS_Crawl_Tracker_Admin;
use WP_Mock;

/**
 * Admin testable : neutralise le exit() de la redirection.
 */
class TestableAdmin extends WS_Crawl_Tracker_Admin {
    protected function terminate() {
        throw new \RuntimeException( '__terminated__' );
    }
}

class AdminTest extends WebStrategyTestCase {

    private function admin() {
        return new TestableAdmin( 'ws-crawl-tracker', '1.0.0' );
    }

    public function test_add_admin_body_class_adds_class_on_plugin_screen() {
        $admin = $this->admin();

        $screen = new \stdClass();
        $screen->id = 'toplevel_page_ws-crawl-tracker';
        WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );

        $out = $admin->add_admin_body_class( 'foo' );
        $this->assertStringContainsString( 'wsct-page', $out );
    }

    public function test_add_admin_body_class_untouched_elsewhere() {
        $admin = $this->admin();

        $screen = new \stdClass();
        $screen->id = 'edit-post';
        WP_Mock::userFunction( 'get_current_screen', [ 'return' => $screen ] );

        $out = $admin->add_admin_body_class( 'foo' );
        $this->assertSame( 'foo', $out );
    }

    public function test_ajax_get_data_denies_without_capability() {
        $admin = $this->admin();

        WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        WP_Mock::userFunction( 'current_user_can', [ 'args' => [ 'manage_options' ], 'return' => false ] );

        $sent = null;
        WP_Mock::userFunction( 'wp_send_json_error', [
            'times'  => 1,
            'return' => function ( $payload ) use ( &$sent ) { $sent = $payload; },
        ] );
        // get_data ne doit jamais atteindre wp_send_json_success.
        WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $admin->ajax_get_data();
        $this->assertArrayHasKey( 'message', $sent );
    }

    public function test_ajax_purge_truncates_when_authorized() {
        global $wpdb;
        $wpdb = new \wpdb();

        $admin = $this->admin();
        WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );

        $ok = null;
        WP_Mock::userFunction( 'wp_send_json_success', [
            'times'  => 1,
            'return' => function ( $payload ) use ( &$ok ) { $ok = $payload; },
        ] );

        $admin->ajax_purge();
        $this->assertArrayHasKey( 'message', $ok );
        $this->assertStringContainsString( 'TRUNCATE', $wpdb->last_query );
    }

    public function test_ajax_get_session_requires_session_id() {
        $admin = $this->admin();
        WP_Mock::userFunction( 'check_ajax_referer', [ 'return' => true ] );
        WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );

        $err = null;
        WP_Mock::userFunction( 'wp_send_json_error', [
            'times'  => 1,
            'return' => function ( $p ) use ( &$err ) { $err = $p; },
        ] );
        WP_Mock::userFunction( 'wp_send_json_success', [ 'times' => 0 ] );

        $_POST['session_id'] = '';
        $admin->ajax_get_session();
        $this->assertArrayHasKey( 'message', $err );
    }

    public function test_handle_save_settings_persists_options() {
        $admin = $this->admin();

        WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        WP_Mock::userFunction( 'check_admin_referer', [ 'return' => true ] );
        WP_Mock::userFunction( 'get_option', [ 'return' => [ 'bots' => \WS_Crawl_Tracker_Activator::default_bots() ] ] );
        WP_Mock::userFunction( 'admin_url', [ 'return' => 'https://x.fr/wp-admin/admin.php' ] );
        WP_Mock::userFunction( 'add_query_arg', [ 'return' => 'https://x.fr/wp-admin/admin.php?page=ws-crawl-tracker-settings&saved=1' ] );

        $saved = null;
        WP_Mock::userFunction( 'update_option', [
            'times'  => 1,
            'return' => function ( $name, $value ) use ( &$saved ) { $saved = $value; return true; },
        ] );
        WP_Mock::userFunction( 'wp_safe_redirect', [ 'return' => true ] );

        $_POST['wsct_enabled']        = '1';
        $_POST['wsct_verify_dns']     = '1';
        $_POST['wsct_retention_days'] = '60';
        $_POST['wsct_bots']           = [ 'googlebot', 'gptbot' ];

        try {
            $admin->handle_save_settings();
        } catch ( \Exception $e ) {
            // wp_safe_redirect suivi de exit — exit non simulable, on ignore.
        }

        $this->assertSame( 1, $saved['enabled'] );
        $this->assertSame( 1, $saved['verify_dns'] );
        $this->assertSame( 60, $saved['retention_days'] );
        $this->assertSame( 1, $saved['bots']['googlebot']['enabled'] );
        $this->assertSame( 0, $saved['bots']['bingbot']['enabled'] );
    }

    public function test_handle_save_settings_defaults_retention_when_zero() {
        $admin = $this->admin();

        WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
        WP_Mock::userFunction( 'check_admin_referer', [ 'return' => true ] );
        WP_Mock::userFunction( 'get_option', [ 'return' => [ 'bots' => \WS_Crawl_Tracker_Activator::default_bots() ] ] );
        WP_Mock::userFunction( 'admin_url', [ 'return' => 'https://x.fr/wp-admin/admin.php' ] );
        WP_Mock::userFunction( 'add_query_arg', [ 'return' => 'https://x.fr/' ] );
        WP_Mock::userFunction( 'wp_safe_redirect', [ 'return' => true ] );

        $saved = null;
        WP_Mock::userFunction( 'update_option', [
            'return' => function ( $name, $value ) use ( &$saved ) { $saved = $value; return true; },
        ] );

        $_POST['wsct_retention_days'] = '0';

        try { $admin->handle_save_settings(); } catch ( \Exception $e ) {}

        $this->assertSame( 90, $saved['retention_days'] );
    }

    public function test_maybe_schedule_purge_schedules_when_absent() {
        $admin = $this->admin();
        WP_Mock::userFunction( 'wp_next_scheduled', [ 'return' => false ] );
        WP_Mock::userFunction( 'wp_schedule_event', [ 'times' => 1, 'return' => true ] );

        $admin->maybe_schedule_purge();
        $this->assertConditionsMet();
    }

    public function test_maybe_schedule_purge_skips_when_present() {
        $admin = $this->admin();
        WP_Mock::userFunction( 'wp_next_scheduled', [ 'return' => time() + 1000 ] );
        WP_Mock::userFunction( 'wp_schedule_event', [ 'times' => 0 ] );

        $admin->maybe_schedule_purge();
        $this->assertConditionsMet();
    }

    public function test_run_purge_uses_retention_setting() {
        global $wpdb;
        $wpdb = new \wpdb();

        $admin = $this->admin();
        WP_Mock::userFunction( 'get_option', [ 'return' => [ 'retention_days' => 30 ] ] );
        WP_Mock::userFunction( 'wp_cache_flush', [ 'return' => true ] );

        $admin->run_purge();
        $this->assertStringContainsString( 'DELETE FROM', $wpdb->last_query );
    }
}
