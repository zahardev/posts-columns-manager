<?php

namespace PCM\Controllers;


use PCM\Entities\Column;
use PCM\Entities\Settings_Tab;
use PCM\Helpers\ACF_Helper;
use PCM\Helpers\Renderer;
use PCM\Helpers\Settings_Helper;
use PCM\Traits\Singleton;

/**
 * Class Settings_Controller
 * @package PCM
 */
class Settings_Controller {

	use Singleton;

	const MANAGER_SETTINGS_URL = 'pcm_settings';

	const OLD_SETTINGS_NAME = 'pcm_manager_settings';

	const DEFAULT_TAB = 'add_columns';

	const SOURCE_ACF_FIELDS = 'acf_fields';

	const SOURCE_META_FIELDS = 'meta_fields';

	const SOURCE_TAX = 'tax';

    // This is for the native fields that are stored in the wp_posts table
    const SOURCE_POST_PROPERTIES = 'post_properties';

	/**
	 * @var array $settings
	 */
	private $settings;


	public function init() {
		add_filter( 'plugin_action_links_' . PCM_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_pages' ) );
		add_action( 'admin_init', array( $this, 'init_setting_tabs' ) );
		add_filter( 'pcm_column', array( $this, 'change_column_title' ) );
		add_action( 'pcm_tab_settings', array( $this, 'provide_settings_tab' ) );

		return $this;
	}

	/**
	 * @param Settings_Tab $tab
	 */
	public function provide_settings_tab( $tab ) {
		$args = array(
			'id'    => 'pcm_settings_tab',
			'value' => $tab->id,
		);

		$this->render_hidden( $args );
	}

	/**
	 * @param Column $column
	 *
	 * @return Column
	 */
	public function change_column_title( $column ) {
		$current_screen = get_current_screen();
		$post_type      = $current_screen->post_type;
		$settings       = $this->get_settings( 'edit_titles' );

		if ( ! empty( $settings[ $post_type ][ $column->source ][ $column->name ]['title'] ) ) {
			$column->title = $settings[ $post_type ][ $column->source ][ $column->name ]['title'];
		}

		return $column;
	}

	/**
	 * @param array $links
	 *
	 * @return array
	 */
	public function add_plugin_links( $links ) {
		$links[] = Renderer::fetch( 'link', [
			'href'  => admin_url( 'options-general.php?page=' . self::MANAGER_SETTINGS_URL ),
			'label' => 'Settings',
		] );

		return $links;
	}

	public function add_settings_pages() {
		$pages = array(
			array(
				'title'     => __( 'PCM Settings', 'posts-columns-manager' ),
				'menu_slug' => self::MANAGER_SETTINGS_URL,
				'page_slug' => self::MANAGER_SETTINGS_URL,
			),
		);

		foreach ( $pages as $page ) {
			add_options_page(
				$page['title'],
				$page['title'],
				'manage_options',
				$page['menu_slug'],
				function () use ( $page ) {
					$this->render_settings_page( $page['page_slug'] );
				}
			);
		}
	}

	public function init_setting_tabs() {

		if ( ! $this->is_pcm_settings_page() ) {
			return;
		}

		$current_tab = $this->get_current_tab();

		$tabs = $this->get_tabs();

		register_setting( self::MANAGER_SETTINGS_URL, $this->get_option_name() );

		if ( isset( $tabs[ $current_tab ]->callback ) && is_callable( $tabs[ $current_tab ]->callback ) ) {
			call_user_func( $tabs[ $current_tab ]->callback );
		}
	}

	protected function is_pcm_settings_page() {
		if ( self::MANAGER_SETTINGS_URL === filter_input( INPUT_GET, 'page' ) ||
		     self::MANAGER_SETTINGS_URL === filter_input( INPUT_POST, 'option_page' )
		) {
			return true;
		}

		return false;
	}


	/**
	 * Inits first settings tab.
	 */
	public function init_add_columns_tab() {
		foreach ( get_post_types() as $post_type ) {
			if ( ! $this->is_post_type_supported( $post_type ) ) {
				continue;
			}

			//Init ACF Fields settings
			if ( $fields = ACF_Helper::get_acf_fields( $post_type ) ) {
				$this->init_acf_fields_settings( $post_type, $fields );
			}

            //Init settings for the post properties ( stored in the wp_posts table )
            $this->init_post_properties_settings( $post_type );

			//Init settings for other meta fields
			$this->init_meta_fields_settings( $post_type );

			//Init taxonomy settings
			if ( $taxonomies = $this->get_taxonomies( $post_type ) ) {
				$this->init_tax_settings( $post_type, $taxonomies );
			}
		}
	}

	/**
	 * Inits second settings tab.
	 */
	protected function init_column_titles_tab() {
		$tab = $this->get_current_tab();

		foreach ( get_post_types() as $post_type ) {
			if ( ! $this->is_post_type_supported( $post_type ) ) {
				continue;
			}

			//Init ACF Fields settings
			if ( $fields = ACF_Helper::get_acf_fields( $post_type ) ) {
				$this->init_acf_fields_settings( $post_type, $fields, $tab, 'title' );
			}

            //Init title settings for the post properties
            $this->init_post_properties_settings( $post_type, $tab, 'title' );

            //Init settings for other meta fields
			$this->init_meta_fields_settings( $post_type, $tab, 'title' );

			//Init taxonomy settings
			if ( $taxonomies = $this->get_taxonomies( $post_type ) ) {
				$this->init_tax_settings( $post_type, $taxonomies, $tab, 'title' );
			}
		}
	}

	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	protected function is_post_type_supported( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );

		return ! empty( $post_type_object->show_in_menu );
	}

	/**
	 * @param string $post_type
	 * @param int $posts_to_check
	 *
	 * @return array
	 */
	public static function get_post_type_meta_keys( $post_type, $posts_to_check = 20 ) {
		$meta_keys = array();
		$posts     = get_posts( array( 'post_type' => $post_type, 'limit' => $posts_to_check ) );

		foreach ( $posts as $post ) {
			$post_meta_keys = get_post_custom_keys( $post->ID );
			if ( ! empty( $post_meta_keys ) ) {
				$meta_keys = array_merge( $meta_keys, $post_meta_keys );
			}
		}

		return array_values( array_unique( $meta_keys ) );
	}

	/**
	 * @param string $post_type
	 *
	 * @return array
	 */
	protected function get_taxonomies( $post_type ) {
		$taxonomies = get_taxonomies( [ 'object_type' => [ $post_type ] ], 'objects' );

		if ( empty( $taxonomies ) ) {
			return [];
		}

		foreach ( $taxonomies as $k => $taxonomy ) {
			if ( empty( get_terms( $taxonomy->name ) ) ) {
				unset( $taxonomies[ $k ] );
			}
		}

		return $taxonomies;
	}

    /**
     * @param string $post_type
     * @param array $fields
     * @param string $tab
     * @param string $option_name
     */
	protected function init_acf_fields_settings( $post_type, $fields, $tab = 'add_columns', $option_name = 'show_in_column' ) {
		$source       = self::SOURCE_ACF_FIELDS;
		$has_settings = false;
		$options_map  = $this->options_map();
		$settings     = $this->get_settings( 'add_columns' );

		$label_tmpl = isset( $options_map[ $option_name ] ) ? $options_map[ $option_name ] : Settings_Helper::get_human_readable_field_name( $option_name );

		foreach ( $fields as $field ) {
			$option_label = sprintf( $label_tmpl, $field['label'] );

			if ( 'add_columns' === $tab ) {
				$has_settings = true;
				$this->add_dynamic_settings_field( 'checkbox', $post_type, $source, $field['name'], $option_name, $option_label );
			}

			if ( 'edit_titles' === $tab ) {
				if ( ! empty( $settings[ $post_type ][ $source ][ $field['name'] ]['show_in_column'] ) ) {
					$has_settings = true;
					$this->add_dynamic_settings_field( 'text', $post_type, $source, $field['name'], $option_name, $option_label );
				}
			}
		}

		if ( $has_settings ) {
			$this->add_post_type_settings_section( $post_type, $source );
		}
	}

    /**
     * @param string $post_type
     */
    protected function init_post_properties_settings( $post_type, $tab = 'add_columns', $option_name = 'show_in_column' ) {
        $source      = self::SOURCE_POST_PROPERTIES;
        $properties = array(
            'ID',
            'post_author',
            'post_date',
            'post_date_gmt',
            'post_title',
            'post_status',
            'post_modified',
            'post_modified_gmt',
            'menu_order',
            'comment_count',
        );

        $settings = $this->get_settings( 'add_columns' );

        $has_settings = false;

        $options_map = $this->options_map();

        $label = isset( $options_map[ $option_name ] ) ? $options_map[ $option_name ] : Settings_Helper::get_human_readable_field_name( $option_name );

        foreach ( $properties as $property ) {

            $field_name   = sprintf( $label, Settings_Helper::get_human_readable_field_name( $property ) );
            $option_label = sprintf( '%s (%s)', $field_name, $property );

            if ( 'add_columns' === $tab ) {
                $has_settings = true;
                $this->add_dynamic_settings_field( 'checkbox', $post_type, $source, $property, $option_name, $option_label );
            }

            if ( 'edit_titles' === $tab ) {
                if ( ! empty( $settings[ $post_type ][ $source ][ $property ]['show_in_column'] ) ) {
                    $has_settings = true;
                    $this->add_dynamic_settings_field( 'text', $post_type, $source, $property, $option_name, $option_label );
                }
            }
        }

        if ( $has_settings ) {
            $this->add_post_type_settings_section( $post_type, $source );
        }
    }


	/**
	 * @param string $post_type
	 */
	protected function init_meta_fields_settings( $post_type, $tab = 'add_columns', $option_name = 'show_in_column' ) {
		$source      = self::SOURCE_META_FIELDS;
		$meta_fields = self::get_post_type_meta_keys( $post_type );
		if ( empty( $meta_fields ) ) {
			return;
		}

		$settings = $this->get_settings( 'add_columns' );

		$has_settings = false;

		$options_map = $this->options_map();

		$label = isset( $options_map[ $option_name ] ) ? $options_map[ $option_name ] : Settings_Helper::get_human_readable_field_name( $option_name );

		foreach ( $meta_fields as $meta_field ) {

			$field_name   = sprintf( $label, Settings_Helper::get_human_readable_field_name( $meta_field ) );
			$option_label = sprintf( '%s (%s)', $field_name, $meta_field );

			if ( 'add_columns' === $tab ) {
				$has_settings = true;
				$this->add_dynamic_settings_field( 'checkbox', $post_type, $source, $meta_field, $option_name, $option_label );
			}

			if ( 'edit_titles' === $tab ) {
				if ( ! empty( $settings[ $post_type ][ $source ][ $meta_field ]['show_in_column'] ) ) {
					$has_settings = true;
					$this->add_dynamic_settings_field( 'text', $post_type, $source, $meta_field, $option_name, $option_label );
				}
			}
		}

		if ( $has_settings ) {
			$this->add_post_type_settings_section( $post_type, $source );
		}
	}

	/**
	 * @param string $post_type
	 * @param array $taxonomies
	 */
	protected function init_tax_settings( $post_type, $taxonomies, $tab = 'add_columns', $option_name = 'show_in_column' ) {
		$source = self::SOURCE_TAX;

		$options_map = $this->options_map();

		$settings     = $this->get_settings( 'add_columns' );
		$has_settings = false;

		$label = isset( $options_map[ $option_name ] ) ? $options_map[ $option_name ] : Settings_Helper::get_human_readable_field_name( $option_name );

		foreach ( $taxonomies as $tax ) {
			$option_label = sprintf( $label, $tax->label );

			if ( 'add_columns' === $tab ) {
				$has_settings = true;
				$this->add_dynamic_settings_field( 'checkbox', $post_type, self::SOURCE_TAX, $tax->name, $option_name, $option_label );
			}

			if ( 'edit_titles' === $tab ) {
				if ( ! empty( $settings[ $post_type ][ $source ][ $tax->name ]['show_in_column'] ) ) {
					$has_settings = true;
					$this->add_dynamic_settings_field( 'text', $post_type, self::SOURCE_TAX, $tax->name, $option_name, $option_label );
				}
			}
		}

		if ( $has_settings ) {
			$this->add_post_type_settings_section( $post_type, $source );
		}
	}

	/**
	 * @param string $post_type
	 * @param string $source Example: self::SOURCE_ACF_FIELDS
	 */
	protected function add_post_type_settings_section( $post_type, $source ) {
		$post_type_object = get_post_type_object( $post_type );

		$id    = $this->get_dynamic_settings_section_id( $post_type, $source );
		$title = $this->get_settings_section_title( $post_type_object->label, $source );

		$this->add_settings_section( $id, $title );
	}


	/**
	 * @param string $id
	 * @param string $title
	 * @param callable|null $callback
	 */
	protected function add_settings_section( $id, $title, $callback = null ) {
		add_settings_section( $id, $title, $callback, self::MANAGER_SETTINGS_URL );
	}


	/**
	 * @param string $post_label
	 * @param string $source
	 *
	 * @return string
	 */
    protected function get_settings_section_title( $post_label, $source ) {
        $name_map = [
            self::SOURCE_TAX             => __( '%s taxonomies', 'posts-columns-manager' ),
            self::SOURCE_ACF_FIELDS      => __( '%s ACF fields', 'posts-columns-manager' ),
            self::SOURCE_META_FIELDS     => __( '%s meta fields', 'posts-columns-manager' ),
            self::SOURCE_POST_PROPERTIES => __( '%s properties', 'posts-columns-manager' ),
        ];

        return sprintf( $name_map[ $source ], $post_label );
    }


	/**
	 * @return array
	 */
	public function options_map() {
		return [
			'show_in_column' => '%s',
			'title'          => '%s',
		];
	}

	/**
	 * @param string $post_type
	 * @param string $source
	 * @param string $field_name
	 * @param string $action_name
	 *
	 * @return mixed|string
	 */
	protected function get_dynamic_setting_value( $post_type, $source, $field_name, $action_name  ){
		$settings     = $this->get_settings();
		if ( isset( $settings[ $post_type ][ $source ][ $field_name ][ $action_name ] ) ) {
			$val = $settings[ $post_type ][ $source ][ $field_name ][ $action_name ];
		} else {
			$val = '';
		}

		return $val;
	}

	/**
	 * @param $field_type
	 * @param $post_type
	 * @param $source
	 * @param $field_name
	 * @param $action_name
	 * @param $title
	 */
	protected function add_dynamic_settings_field( $field_type, $post_type, $source, $field_name, $action_name, $title ) {
		$args = array(
			'id'    => $this->get_dynamic_field_id( $post_type, $source, $field_name, $action_name ),
			'title' => $title,
			'type'  => $field_type,
		);

		if ( 'text' === $field_type ) {
			$args['placeholder'] = $title;
		}

		$section = $this->get_dynamic_settings_section_id( $post_type, $source );
		$value   = $this->get_dynamic_setting_value( $post_type, $source, $field_name, $action_name );

		$this->add_settings_field( $args, $section, $value );
	}

	protected function add_settings_field( $args, $section, $value ) {
		add_settings_field(
			$args['id'],
			isset( $args['title'] ) ? $args['title'] : '',
			array( $this, 'render_' . $args['type'] ),
			self::MANAGER_SETTINGS_URL,
			$section,
			array_merge( $args, array( 'value' => $value ) )
		);
	}

	protected function get_dynamic_field_id( $post_type, $source, $field_name, $action_name ){
		return sprintf( '%s[%s][%s][%s][%s]', self::get_option_name(), $post_type, $source, $field_name, $action_name );
	}

	protected function get_current_tab() {
		$post_tab = filter_input( INPUT_POST, 'pcm_settings_tab' );

		$tab = $post_tab ?: filter_input( INPUT_GET, 'tab' );

		return $tab ?: self::DEFAULT_TAB;
	}

	/**
	 * @param string $post_type
	 * @param string $source
	 *
	 * @return string
	 */
	protected function get_dynamic_settings_section_id( $post_type, $source ) {
		return sprintf( 'pcm_post_type_%s_%s', $post_type, $source );
	}

	public function render_hidden( $args ) {
		Renderer::render( 'settings/hidden', $args );
	}

	/**
	 * @param array $args
	 */
	public function render_checkbox( $args ) {
		Renderer::render( 'settings/checkbox', $args );
	}

	/**
	 * @param array $args
	 */
	public function render_text( $args ) {
		$defaults = array(
			'id'          => '',
			'title'       => '',
			'placeholder' => '',
		);

		Renderer::render( 'settings/text', wp_parse_args( $args, $defaults ) );
	}

	/**
	 * @return array|mixed
	 */
	public function get_settings( $tab = '' ) {
		if ( isset( $this->settings[ $tab ] ) ) {
			return $this->settings[ $tab ];
		}

		$tab          = $tab ?: $this->get_current_tab();
		$tab_settings = get_option( $this->get_option_name( $tab ) );

		// Check if we need to migrate from the previous settings structure.
		if ( self::DEFAULT_TAB === $tab && empty( $tab_settings ) ) {
			$old_settings = get_option( self::OLD_SETTINGS_NAME );

			if ( $old_settings ) {
				$tab_settings = $this->migrate_settings( $old_settings );
			}
		}
		$this->settings[ $tab ] = $tab_settings;

		return $this->settings[ $tab ];
	}

	/**
	 *
	 *
	 * @param $settings
	 *
	 * @return mixed
	 *
	 * Todo: remove in future. Needed to change the current default option name.
	 */
	protected function migrate_settings( $settings ) {
		// Rename fields to acf_fields.
		foreach ( $settings as $post_type => $post_type_sources ) {
			foreach ( $post_type_sources as $source => $source_settings ) {
				if ( 'fields' === $source ) {
					$settings[ $post_type ]['acf_fields'] = $source_settings;
					unset( $settings[ $post_type ]['fields'] );
				}
			}
		}

		update_option( $this->get_option_name( self::DEFAULT_TAB ), $settings, false );
		delete_option( self::OLD_SETTINGS_NAME );

		return $settings;
	}

	public function get_option_name( $tab = '' ) {
		$tab = $tab ?: $this->get_current_tab();

		return self::MANAGER_SETTINGS_URL . '_' . $tab;
	}

	/**
	 * @param null $post_type
	 *
	 * @return array
	 */
	public function get_post_settings( $post_type = null ) {
		$settings = $this->get_settings();

		if ( empty( $post_type ) ) {
			$current_screen = get_current_screen();
			$post_type      = $current_screen->post_type;
		}

		if ( empty( $settings[ $post_type ] ) ) {
			return [];
		}

		return $settings[ $post_type ];
	}


	/**
	 * @return Settings_Tab[]
	 */
	protected function get_tabs() {
		$tabs        = $this->get_tab_settings();
		$tab_objects = array();

		foreach ( $tabs as $tab ) {
			$tab_objects[ $tab['id'] ] = new Settings_Tab( $tab );
		}

		return $tab_objects;
	}


	protected function get_tab_settings() {
		return array(
			array(
				'id'          => 'add_columns',
				'title'       => 'Add columns',
				'callback'    => array( $this, 'init_add_columns_tab' ),
				'description' => 'Please choose which columns you want to add.',
			),
			array(
				'id'          => 'edit_titles',
				'title'       => 'Edit column titles',
				'callback'    => array( $this, 'init_column_titles_tab' ),
				'description' => 'Here you can edit the column titles.',
			),
		);
	}


	public function render_settings_page( $page_slug ) {
		$tabs        = $this->get_tabs();
		$current_tab = $this->get_current_tab();

		$current_tab = $tabs[ $current_tab ];

		Renderer::render( 'settings', compact( 'page_slug', 'tabs', 'current_tab' ) );
	}
}
