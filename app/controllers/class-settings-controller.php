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

	/**
	 * @var array $settings
	 */
	private $settings;


	public function init() {
		add_filter( 'plugin_action_links_' . PCM_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_pages' ) );
		add_action( 'admin_init', array( $this, 'init_setting_tabs' ) );
		add_filter( 'pcm_column', array( $this, 'change_column_label' ) );

		return $this;
	}

	/**
	 * @param Column $column
	 *
	 * @return mixed
	 */
	public function change_column_label( $column ) {
		$current_screen = get_current_screen();
		$post           = $current_screen->post_type;
		$setting        = $this->get_settings();

		return $column;

		return $column->label = 'Changed label';
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
				'title'     => __( 'PCM Settings', PCM_TEXT_DOMAIN ),
				'menu_slug' => self::MANAGER_SETTINGS_URL,
				'page_slug' => self::MANAGER_SETTINGS_URL,
			),
		);

		foreach ( $pages as $page ) {
			add_submenu_page(
				'options-general.php',
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
		$tabs = $this->get_tabs();

		foreach ( $this->get_tabs() as $tab ) {
			register_setting( self::MANAGER_SETTINGS_URL, $this->get_option_name( $tab->id ) );
		}

		$current_tab = $this->get_current_tab();

		if ( isset( $tabs[ $current_tab ]->callback ) && is_callable( $tabs[ $current_tab ]->callback ) ) {
			call_user_func( $tabs[ $current_tab ]->callback );
		}
	}

	protected function init_labels_tab() {
		$tab = $this->get_current_tab();

		//Next, add settings for taxonomies
		foreach ( get_post_types() as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( empty( $post_type_object->show_in_menu ) ) {
				continue;
			}

			//Init ACF Fields settings
			if ( $fields = ACF_Helper::get_acf_fields( $post_type ) ) {
				$this->init_acf_fields_settings( $post_type, $fields, $tab, 'label' );
			}

			//Init settings for other meta fields
			$this->init_meta_fields_settings( $post_type, $tab, 'label' );

			//Init taxonomy settings
			if ( $taxonomies = $this->get_taxonomies( $post_type ) ) {
				$this->init_tax_settings( $post_type, $taxonomies, $tab, 'label' );
			}
		}
	}


	public function init_add_columns_tab() {


		//Next, add settings for taxonomies
		foreach ( get_post_types() as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( empty( $post_type_object->show_in_menu ) ) {
				continue;
			}

			//Init ACF Fields settings
			if ( $fields = ACF_Helper::get_acf_fields( $post_type ) ) {
				$this->init_acf_fields_settings( $post_type, $fields );
			}

			//Init settings for other meta fields
			$this->init_meta_fields_settings( $post_type );

			//Init taxonomy settings
			if ( $taxonomies = $this->get_taxonomies( $post_type ) ) {
				$this->init_tax_settings( $post_type, $taxonomies );
			}
		}
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
	 */
	protected function init_acf_fields_settings( $post_type, $fields, $tab = 'add_columns', $option_name = 'show_in_column' ) {
		$source = self::SOURCE_ACF_FIELDS;
		$has_settings = false;
		$options_map = $this->options_map();
		$settings = $this->get_settings( 'add_columns' );

		$label = isset( $options_map[ $option_name ] ) ? $options_map[ $option_name ] : Settings_Helper::get_human_readable_field_name( $option_name );

		foreach ( $fields as $field ) {
			$option_label = sprintf( $label, $field['label'] );
			$is_supported = Settings_Helper::is_supported( $option_name, $source, $field['type'] );

			if ( 'add_columns' === $tab ) {
				$has_settings = true;
				$this->add_settings_checkbox( $post_type, $source, $field['name'], $option_name, $option_label, $is_supported );
			}

			if ( 'edit_labels' === $tab ) {
				if ( !empty( $settings[ $post_type ][ $source ][ $field['name'] ]['show_in_column'] ) ) {
					$has_settings = true;
					$this->add_settings_text( $post_type, $source, $field['name'], $option_name, $option_label );
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
	protected function init_meta_fields_settings( $post_type, $tab = 'add_columns', $option_name = 'show_in_column'  ) {
		$source = self::SOURCE_META_FIELDS;
		$meta_fields  = self::get_post_type_meta_keys( $post_type );
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
				$this->add_settings_checkbox( $post_type, $source, $meta_field, $option_name, $option_label );
			}

			if ( 'edit_labels' === $tab ) {
				if ( ! empty( $settings[ $post_type ][ $source ][ $meta_field ]['show_in_column'] ) ) {
					$has_settings = true;
					$this->add_settings_text( $post_type, $source, $meta_field, $option_name, $option_label );
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
			$is_supported = Settings_Helper::is_supported( $option_name, $source );

			if ( 'add_columns' === $tab ) {
				$has_settings = true;
				$this->add_settings_checkbox( $post_type, self::SOURCE_TAX, $tax->name, $option_name, $option_label, $is_supported );
			}

			if ( 'edit_labels' === $tab ) {
				if ( ! empty( $settings[ $post_type ][ $source ][ $tax->name ]['show_in_column'] ) ) {
					$has_settings = true;
					$this->add_settings_text( $post_type, self::SOURCE_TAX, $tax->name, $option_name, $option_label, $is_supported );
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

		$id    = $this->get_settings_section_id( $post_type, $source );
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
			self::SOURCE_TAX         => 'taxonomies',
			self::SOURCE_ACF_FIELDS  => 'ACF fields',
			self::SOURCE_META_FIELDS => 'meta fields',
		];

		return __( sprintf( '%s %s', $post_label, $name_map[ $source ] ), 'wordpress' );
	}


	/**
	 * @return array
	 */
	public function options_map() {
		return [
			'show_in_column' => '%s',
			'label' => '%s',
		];
	}

	/**
	 * @param string $post_type
	 * @param string $source
	 * @param string $field_name
	 * @param string $option_name
	 * @param string $option_label
	 * @param bool $is_supported
	 */
	public function add_settings_checkbox( $post_type, $source, $field_name, $option_name, $option_label, $is_supported = true ) {
		$settings      = $this->get_settings();
		$tab           = $this->get_current_tab();
		add_settings_field(
			sprintf( '%s_%s_%s', $option_name, $source, $field_name ),
			$option_label,
			array( $this, 'render_checkbox' ),
			self::MANAGER_SETTINGS_URL,
			$this->get_settings_section_id( $post_type, $source ),
			compact( 'settings', 'tab', 'post_type', 'field_name', 'is_supported', 'option_name', 'source' )
		);
	}

	/**
	 * @param string $post_type
	 * @param string $source
	 * @param string $field_name
	 * @param string $option_name
	 * @param string $option_label
	 */
	public function add_settings_text( $post_type, $source, $field_name, $option_name, $option_label ) {
		$settings      = $this->get_settings();
		$tab           = $this->get_current_tab();
		add_settings_field(
			sprintf( '%s_%s_%s', $option_name, $source, $field_name ),
			$option_label,
			array( $this, 'render_text' ),
			self::MANAGER_SETTINGS_URL,
			$this->get_settings_section_id( $post_type, $source ),
			compact( 'settings', 'tab', 'post_type', 'field_name', 'option_name', 'source' )
		);
	}

	protected function get_current_tab() {
		$tab = filter_input( INPUT_GET, 'tab' );
		return $tab ?: self::DEFAULT_TAB;
	}

	/**
	 * @param string $post_type
	 * @param string $section
	 *
	 * @return string
	 */
	protected function get_settings_section_id( $post_type, $section ) {
		return sprintf( 'pcm_post_type_%s_%s', $post_type, $section );
	}

	/**
	 * @param array $args
	 */
	public function render_checkbox( $args ) {
		$settings     = $args['settings'];
		$tab          = $args['tab'];
		$post_type    = $args['post_type'];
		$field_name   = $args['field_name'];
		$is_supported = $args['is_supported'];
		$option_name  = $args['option_name'];
		$source       = $args['source'];

		$setting_name = sprintf( '%s[%s][%s][%s][%s]', self::get_option_name(), $post_type, $source, $field_name, $option_name );

		if ( isset( $settings[ $post_type ][ $source ][ $field_name ][ $option_name ] ) ) {
			$setting_value = $settings[ $post_type ][ $source ][ $field_name ][ $option_name ];
		} else {
			$setting_value = 0;
		}

		Renderer::render( 'settings/checkbox', [
			'setting_name' => $setting_name,
			'is_checked'   => $setting_value,
			'is_supported' => $is_supported,
		] );
	}

	/**
	 * @param array $args
	 */
	public function render_text( $args ) {
		$settings     = $args['settings'];
		$tab          = $args['tab'];
		$post_type    = $args['post_type'];
		$field_name   = $args['field_name'];
		$option_name  = $args['option_name'];
		$source       = $args['source'];

		$setting_name = sprintf( '%s[%s][%s][%s][%s]', self::get_option_name(), $post_type, $source, $field_name, $option_name );
		if ( isset( $settings[ $post_type ][ $source ][ $field_name ][ $option_name ] ) ) {
			$setting_value = $settings[ $post_type ][ $source ][ $field_name ][ $option_name ];
		} else {
			$setting_value = '';
		}

		Renderer::render( 'settings/text', [
			'setting_name' => $setting_name,
			'value'   => $setting_value,
		] );
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
		$tabs = $this->get_tab_settings();
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
				'id'          => 'edit_labels',
				'title'       => 'Edit column labels',
				'callback'    => array( $this, 'init_labels_tab' ),
				'description' => 'Here you can edit the column labels.',
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
