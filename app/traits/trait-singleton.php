<?php

namespace PCM\Traits;

trait Singleton {

    /**
     * @var self $instance
     * */
    protected static $instance;

    protected function __construct() {
    }

    public static function instance() {
        if ( empty( static::$instance ) ) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}
