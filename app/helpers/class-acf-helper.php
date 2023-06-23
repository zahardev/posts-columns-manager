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


    public static function acf_get_field( $field ) {
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

    /**
     * @param \WP_Post[]|int[] $field_data
     */
    public static function get_column_value_relationship( $field_data ) {
        if ( ! is_array( $field_data ) ) {
            return '';
        }
        $links = array();
        foreach ( $field_data as $post ) {
            if ( ! is_object( $post ) ) {
                $post = get_post( $post );
            }
            $links[] = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $post ), $post->post_title );
        }

        return implode( ', ', $links );
    }

    /**
     * @param array|string $field_data
     */
    public static function get_column_value_image( $field_data ) {
        $arg_type = gettype( $field_data );

        switch ( $arg_type ) {
            case 'array':
                $url = $field_data['url'];
                break;
            case 'integer':
                $url = wp_get_attachment_url( $field_data );
                break;
            default:
                $url = $field_data;
        }

        return sprintf( '<img src="%s" style="width: 90px">', $url );
    }


    /**
     * @param array|string $field_name
     */
    public static function get_column_value_checkbox( $field_name ) {
        $field = self::acf_get_field( $field_name );

        $settings = self::get_field( $field_name );

        if ( empty( $field['choices'] ) ) {
            return '';
        }

        foreach ( $field['choices'] as $k => $choice ) {
            $value = in_array( $k, $settings ) ? __( 'Yes', 'posts-columns-manager' ) : __( 'No', 'posts-columns-manager' );

            $values[] = count( $field['choices'] ) > 1 ? sprintf( '%s: %s', $choice, $value ) : $value;
        }

        return implode( '<br>', $values );
    }

}
