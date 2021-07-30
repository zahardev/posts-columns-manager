<?php

namespace PCM;

use PCM\Interfaces\Singleton as Singleton_Interface;
use PCM\Managers\Columns_Manager;
use PCM\Managers\Filters_Manager;


class App implements Singleton_Interface {

    use Traits\Singleton;

    public function init() {
        if ( ! is_admin() ) {
            return;
        }

        $settings = Settings::instance()->init();
        ( new Columns_Manager() )->init( $settings );
        ( new Filters_Manager() )->init( $settings );
    }
}
