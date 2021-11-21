<?php

namespace PCM\Helpers;

class Settings_Helper {

	/**
	 * @deprecated
	 * Todo: remove?
	 * */
    public static function is_supported( $action, $source, $field_type = null ) {
		return true;

        $supported = [
            'tax'    => [
                'show_in_column' => true,
                'filter'         => true,
                'is_numeric'     => false,
                'sort'           => false,
            ],
            'acf_fields' => [
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

        return ! empty( $supported[ $source ][ $action ] );
    }

	public static function get_meta_field_name( $meta_field ) {
		return ucfirst( trim( str_replace( '_', ' ', $meta_field ) ) );
	}
}
