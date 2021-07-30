<?php

namespace PCM\Helpers;

class ACF_Helper {

    protected static $post_type_fields;

    public static function get_acf_fields( $post_type = 'post' ) {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return [];
        }
        if ( ! isset( self::$post_type_fields[ $post_type ] ) ) {
            $field_groups = acf_get_field_groups( [ 'post_type' => $post_type ] );
            $fields       = [];
            if ( empty( $field_groups ) ) {
                return $fields;
            }
            foreach ( $field_groups as $field_group ) {
                $fields = array_merge( $fields, acf_get_fields( $field_group['key'] ) );
            }
            self::$post_type_fields[ $post_type ] = $fields;
        }

        return self::$post_type_fields[ $post_type ];
    }


    public static function acf_get_field( $field ){
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return null;
        }

        return acf_get_field( $field );
    }


    public static function get_field( $field_key, $post_id = false, $format_value = true ) {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return null;
        }

        return get_field( $field_key, $post_id, $format_value );
    }


    public static function get_fields( $post_id = false, $format_value = true ) {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return [];
        }

        return get_fields( $post_id, $format_value );
    }

    public static function get_field_object( $field_key, $post_id = false, $options = [] ) {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return null;
        }

        return get_field_object( $field_key, $post_id, $options );
    }
}
