<?php

namespace PCM;

use PCM\Interfaces\Singleton;
use PCM\Managers\Columns_Manager;
use PCM\Managers\Filters_Manager;
use PCM\Traits\Singleton as Trait_Singleton;


/**
 * @method $this instance()
 * */
class App implements Singleton {

    use Trait_Singleton;

    protected $field_objects;

    public function init() {
        if ( ! is_admin() ) {
            return;
        }

        $settings = Settings::instance()->init();
        ( new Columns_Manager() )->init( $settings );
        ( new Filters_Manager() )->init( $settings );
    }
}
