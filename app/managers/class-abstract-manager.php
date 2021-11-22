<?php

namespace PCM\Managers;

use PCM\Entities\Column;
use PCM\Helpers\ACF_Helper;
use PCM\Helpers\Settings_Helper;

abstract class Abstract_Manager {
    /**
     * This function tries to get field from ACF, if cannot - getting it directly from post meta
     *
     * @param string $source
     * @param string $name
     *
     * @return Column
     */
    protected function get_column( $source, $name ) {

	    switch ( $source ) {
		    case 'tax':
			    $taxonomy = get_taxonomy( $name );

			    return new Column( $taxonomy->name, $taxonomy->label, $source );

		    case 'acf_fields':
			    $acf_field = ACF_Helper::acf_get_field( $name );

			    return new Column( $name, $acf_field['label'], $source );

		    default:
			    return new Column( $name, Settings_Helper::get_human_readable_field_name( $name ), $source );
	    }
    }
}
