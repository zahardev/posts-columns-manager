<?php

namespace PCM;

use PCM\Controllers\Assets_Controller;
use PCM\Controllers\Settings_Controller;
use PCM\Interfaces\Singleton as Singleton_Interface;
use PCM\Managers\Columns_Manager;
use PCM\Managers\Filters_Manager;


class App implements Singleton_Interface {

    use Traits\Singleton;

    public function init() {
        if ( ! is_admin() ) {
            return;
        }

        Assets_Controller::instance()->init();
        $settings = Settings_Controller::instance()->init();
        ( new Columns_Manager() )->init( $settings );
        ( new Filters_Manager() )->init( $settings );
    }
}
