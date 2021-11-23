<?php

namespace PCM\Managers;

use PCM\Helpers\ACF_Helper;
use PCM\Controllers\Settings_Controller;

class Columns_Manager extends Abstract_Manager {

    /**
     * @var Settings_Controller
     */
    protected $settings;


    public function init( Settings_Controller $settings ) {
        $this->settings = $settings;
        add_action( 'current_screen', [ $this, 'init_manager' ], 20 );
    }

    public function init_manager() {
        $screen     = get_current_screen();
        $post_types = get_post_types();

        foreach ( $post_types as $post_type ) {
            $post_type_object = get_post_type_object( $post_type );
            if ( empty( $post_type_object->show_in_menu ) ) {
                continue;
            }
            $settings = $this->get_columns_settings( $post_type );
            if ( $settings ) {
                add_filter( "manage_{$post_type}_posts_columns", [ $this, 'manage_posts_columns' ], 5 );
            }
        }

        add_action( 'manage_posts_custom_column', array( $this, 'echo_column_value' ), 10, 2 );
        add_action( 'manage_pages_custom_column', array( $this, 'echo_column_value' ), 10, 2 );
        add_filter( 'manage_' . $screen->id . '_sortable_columns', array( $this, 'sortable_columns' ), 10, 2 );
        add_action( 'pre_get_posts', array( $this, 'maybe_sort_column' ) );
    }

    public function sortable_columns( $columns ) {
        if ( ! $columns_settings = $this->get_columns_settings() ) {
            return $columns;
        }

        foreach ( $columns_settings as $source => $fields ) {
            // Since column can have multiple values lets just not allow sorting here.
            if ( Settings_Controller::SOURCE_TAX === $source ) {
                continue;
            }
            foreach ( $fields as $field_name => $actions ) {
                $columns[ $this->get_column_name( $source, $field_name ) ] = $field_name;
            }
        }

        return $columns;
    }

    /**
     * @param \WP_Query $wp_query
     */
    public function maybe_sort_column( $wp_query ) {
        if ( ! is_admin() || ! $wp_query->is_main_query() ) {
            return;
        }

        if ( ! $columns_settings = $this->get_columns_settings() ) {
            return;
        }

        $order_by = $wp_query->get( 'orderby' );

        foreach ( $columns_settings as $source => $fields ) {
            foreach ( $fields as $field_name => $actions ) {
                switch ( $source ) {
                    case Settings_Controller::SOURCE_META_FIELDS:
                    case Settings_Controller::SOURCE_ACF_FIELDS:
                        if ( $field_name === $order_by ) {
                            $wp_query->set( 'meta_key', $order_by );
                            $order_field = is_numeric( $order_by ) ? 'meta_value_num' : 'meta_value';
                            $wp_query->set( 'orderby', $order_field );
                        }
                }
            }
        }
    }

    /**
     * Change columns array here.
     *
     * @param $defaults
     *
     * @return mixed
     */
    public function manage_posts_columns( $defaults ) {
        if ( ! $columns_settings = $this->get_columns_settings() ) {
            return $defaults;
        }

        foreach ( $columns_settings as $source => $fields ) {
            foreach ( $fields as $field_name => $actions ) {
                if ( empty( $actions['show_in_column'] ) ) {
                    continue;
                }
                $column                    = $this->get_column( $source, $field_name );
                $defaults[ $this->get_column_name( $source, $column->name ) ] = $column->title;
            }
        }


        return $defaults;
    }

    protected function get_column_name( $source, $column_name ) {
        return $source . '__pcm__' . $column_name;
    }


    public function echo_column_value( $column_name ) {

        // Unfortunately, it's not possible to provide data array directly, so have to use some workarounds.
        $column_data = explode( '__pcm__', $column_name );

        if ( ! is_array( $column_data ) || 2 !== count( $column_data ) ) {
            return;
        }

        if ( ! $columns_settings = $this->get_columns_settings() ) {
            return;
        }

        $source      = $column_data[0];
        $field_name = $column_data[1];

        if ( ! empty( $columns_settings[ $source ][ $field_name ] ) ) {
            echo $this->get_column_value( $source, $field_name );
        }
    }


    /**
     * @param $type
     * @param $key
     *
     * @return string|null
     */
    protected function get_column_value( $type, $key ) {
        switch ( $type ) {
            case 'tax':
                global $post;
                $terms = wp_get_post_terms( $post->ID, $key );

                return $this->convert_terms_to_links( $terms );

            case 'acf_fields':
                return $this->get_column_val_by_acf( $key );

            case 'meta_fields':
                return $this->get_column_val_by_meta( $key );
        }

        return null;
    }

    protected function get_column_val_by_acf( $key ) {
        $fields = ACF_Helper::get_fields();

        if ( empty( $fields ) ) {
            return '';
        }

        foreach ( $fields as $field => $val ) {
            if ( $key === $field ) {
                if ( is_scalar( $val ) ) {
                    return $val;
                } elseif ( is_array( $val ) && 'image' == $val['type'] ) {
                    return sprintf( '<img src="%s" style="width: 50px">', $val['url'] );
                }
                break;
            }
        }

        return '';
    }

    protected function get_column_val_by_meta( $key ) {
        global $post;
        $post_meta = get_post_meta( $post->ID, $key, true );

        return is_scalar( $post_meta ) ? $post_meta : '';
    }

    protected function convert_terms_to_links( $terms ) {
        if ( ! is_array( $terms ) ) {
            return '';
        }
        $links = array_map( function ( $term ) {
            $href = get_admin_url( null, sprintf( 'edit.php?%s=%s', $term->taxonomy, $term->slug ) );

            return sprintf( '<a href=%s>%s</a>', $href, $term->name );
        }, $terms );

        return implode( ', ', $links );
    }

    protected function get_columns_settings( $post_type = null ) {
        return $this->settings->get_post_settings( $post_type );
    }

}
