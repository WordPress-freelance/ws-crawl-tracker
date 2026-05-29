<?php

namespace WS_Crawl_Tracker\Tests\Unit;

use WP_Mock;
use Mockery;

abstract class WebStrategyTestCase extends \WP_Mock\Tools\TestCase {

    public function setUp(): void {
        WP_Mock::setUp();
        $_GET    = [];
        $_POST   = [];
        $_SERVER = [];
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        Mockery::close();
    }

    protected function invoke_static( $class, $method, array $args = [] ) {
        $ref = new \ReflectionMethod( $class, $method );
        $ref->setAccessible( true );
        return $ref->invoke( null, ...$args );
    }

    protected function invoke_method( $instance, $method, array $args = [] ) {
        $ref = new \ReflectionMethod( $instance, $method );
        $ref->setAccessible( true );
        return $ref->invoke( $instance, ...$args );
    }

    protected function get_property( $instance, $name ) {
        $ref = new \ReflectionProperty( get_class( $instance ), $name );
        $ref->setAccessible( true );
        return $ref->getValue( $instance );
    }

    protected function set_property( $instance, $name, $value ) {
        $ref = new \ReflectionProperty( get_class( $instance ), $name );
        $ref->setAccessible( true );
        $ref->setValue( $instance, $value );
    }
}
