<?php

namespace PCM\Managers;

use PCM\Helpers\ACF_Helper;
use PCM\Controllers\Settings_Controller;

class Columns_Manager extends Abstract_Manager {

    /**
     * @var Settings_Controller
     */
    protected $settings;

    /**
     * @var array $column_values Save current screen column values to understand if it's numeric or not.
     * */
    protected $column_values;


    const COLUMN_VALUES_TRANSIENT = 'pcm_last_column_values';


    public function init( Settings_Controller $settings ) {
        $this->settings = $settings;
        add_action( 'current_screen', array( $this, 'init_manager' ), 20 );
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
                // Used 12 to make sure that we add our columns and no one will override it.
                // For example, WC redefines columns completely on 10th priority.
                // Todo: redefine all the columns on 99 priority
                add_filter( "manage_{$post_type}_posts_columns", array( $this, 'manage_posts_columns' ), 12 );
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
                            $column_values = $this->get_last_column_values( get_query_var( 'post_type' ), $source, $field_name );
                            $is_numeric    = true;

                            // Let's find out if the value is numeric or not.
                            if ( empty( $column_values ) ) {
                                $is_numeric = false;
                            } else {
                                foreach ( $column_values as $column_value ) {
                                    if ( ! is_numeric( $column_value ) ) {
                                        $is_numeric = false;
                                        break;
                                    }
                                }
                            }

                            $wp_query->set( 'meta_key', $order_by );
                            $order_field = $is_numeric ? 'meta_value_num' : 'meta_value';
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
                $column                                                       = $this->get_column( $source, $field_name );
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

        $source     = $column_data[0];
        $field_name = $column_data[1];
        if ( ! empty( $columns_settings[ $source ][ $field_name ] ) ) {
            $value = $this->get_column_value( $source, $field_name );
            $value = apply_filters( 'pcm_column_value', $value, $source, $field_name );
            $this->update_column_values( $source, $field_name, $value );
            echo $value;
        }
    }

    protected function update_column_values( $source, $field_name, $value ) {
        global $posts, $post;
        $this->column_values[ $source ][ $field_name ][] = $value;

        $last_post = end( $posts );

        // If all column values obtained save it to transient for the next query
        if ( $this->column_values && $post->ID === $last_post->ID ) {
            $this->save_last_column_values( $post->post_type, $this->column_values );
        }
    }

    /**
     * @param array $column_values
     */
    public function save_last_column_values( $post_type, $column_values ) {
        set_transient( self::COLUMN_VALUES_TRANSIENT . '_' . $post_type, $column_values );
    }

    /**
     * @param string $post_type
     *
     * @return array
     */
    public function get_last_column_values( $post_type, $source, $field_name ) {
        $val = get_transient( self::COLUMN_VALUES_TRANSIENT . '_' . $post_type );

        return ( isset( $val[ $source ][ $field_name ] ) ) ? $val[ $source ][ $field_name ] : null;
    }

    /**
     * Workaround to get column values before query and understand if it's numeric or not
     * */
    protected function save_page_column_values() {

    }


    /**
     * @param $type
     * @param $key
     *
     * @return string|null
     */
    protected function get_column_value( $type, $key ) {
        switch ( $type ) {
            case Settings_Controller::SOURCE_TAX:
                global $post;
                $terms = wp_get_post_terms( $post->ID, $key );

                return $this->convert_terms_to_links( $terms );

            case Settings_Controller::SOURCE_ACF_FIELDS:
                return $this->get_column_val_by_acf( $key );

            case Settings_Controller::SOURCE_META_FIELDS:
                return $this->get_column_val_by_meta( $key );

            case Settings_Controller::SOURCE_POST_PROPERTIES:
                return $this->get_column_val_by_post_property( $key );
        }

        return null;
    }

    protected function get_column_val_by_acf( $key ) {
        $fields = ACF_Helper::get_fields();

        if ( ! isset( $fields[ $key ] ) ) {
            return '';
        }

        $field = $fields[ $key ];

        $type = $this->get_acf_type_by_field_name( $key );
        switch ( $type ) {
            case 'relationship':
                return ACF_Helper::get_column_value_relationship( $field );
            case 'image':
                return ACF_Helper::get_column_value_image( $field );
            case 'checkbox':
                return ACF_Helper::get_column_value_checkbox( $key );

            default:
                return is_scalar( $field ) ? $field : '';
        }
    }

    protected function get_acf_type_by_field_name( $name ) {
        $field = acf_maybe_get_field( $name );

        return isset( $field['type'] ) ? $field['type'] : null;
    }

    /**
     * @param string $key
     *
     * @return string|int|null
     */
    protected function get_column_val_by_post_property( $key ) {
        global $post;

        if ( 'post_author' == $key && $post->post_author ) {
            return sprintf(
                '<a href="%s">%s</a>',
                get_author_posts_url( $post->post_author ),
                get_the_author_meta( 'display_name', $post->post_author )
            );
        }

        return property_exists( $post, $key ) ? $post->$key : null;
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
