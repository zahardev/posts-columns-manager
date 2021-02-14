<?php

namespace PCM\Managers;

use PCM\Entities\Column;
use PCM\Helpers\ACF_Helper;

abstract class Abstract_Manager {
    /**
     * This function tries to get field from ACF, if cannot - getting it directly from post meta
     *
     * @param string $type
     * @param string $name
     *
     * @return Column
     */
    protected function get_column( $type, $name ) {
        if ( 'tax' === $type ) {
            $taxonomy = get_taxonomy( $name );

            return new Column( $taxonomy->name, $taxonomy->label );
        }

        if ( $acf_field = ACF_Helper::acf_get_field( $name ) ) {
            $column = new Column( $name, $acf_field['label'] );
        } else {
            $column = new Column( $name, $name );
        }

        return $column;
    }
}