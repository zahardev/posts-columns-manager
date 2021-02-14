<?php

namespace PCM\Helpers;

class Settings_Helper {

    public static function is_supported( $action, $type, $field_type = null ) {
        $supported = [
            'tax'    => [
                'show_in_column' => true,
                'filter'         => true,
                'is_numeric'     => false,
                'sort'           => false,
            ],
            'fields' => [
                'show_in_column' => true,
                'filter'         => [
                    'number' => true,
                    'text'   => true,
                ],
                'is_numeric'     => true,
                'sort'           => [
                    'number' => true,
                    'text'   => true,
                ],
            ],
        ];

        if ( empty( $supported[ $type ][ $action ] ) ) {
            return false;
        }

        $is_supported = $supported[ $type ][ $action ];

        if ( is_bool( $is_supported ) ) {
            return $is_supported;
        }

        return ! empty( $is_supported[ $field_type ] );
    }
}
