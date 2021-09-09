<?php

namespace PCM;


use PCM\Helpers\ACF_Helper;
use PCM\Helpers\Renderer;
use PCM\Helpers\Settings_Helper;
use PCM\Traits\Singleton;

/**
 * Class Settings
 * @package PCM
 */
class Settings {

	use Singleton;

	const SETTINGS_URL = 'pcm_manager_settings';

	/**
	 * @var array $settings
	 */
	private $settings;


	public function init() {
		add_filter( 'plugin_action_links_' . PCM_PLUGIN_BASENAME, [ $this, 'add_plugin_links' ] );
		add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'init_settings' ] );

		return $this;
	}

	/**
	 * @param array $links
	 *
	 * @return array
	 */
	public function add_plugin_links( $links ) {
		$links[] = Renderer::fetch( 'link', [
			'href'  => admin_url( 'options-general.php?page=' . self::SETTINGS_URL ),
			'label' => 'Settings',
		] );

		return $links;
	}


	public function enqueue_assets() {
		$css_path = '/assets/css/pcm.css';
		wp_enqueue_style(
			'pcm-css',
			PCM_PLUGIN_URL . $css_path,
			[],
			PCM_PLUGIN_VERSION
		);

		$js_path = '/assets/js/pcm.js';
		wp_enqueue_script(
			'pcm-js',
			PCM_PLUGIN_URL . $js_path,
			['jquery', 'jquery-ui-accordion'],
			PCM_PLUGIN_VERSION
		);
	}


	public function add_plugin_page() {
		$title = __( 'PCM Settings', PCM_TEXT_DOMAIN );
		add_submenu_page(
			'options-general.php',
			$title,
			$title,
			'manage_options',
			self::SETTINGS_URL,
			[ $this, 'render_plugin_page' ]
		);
	}


	public function init_settings() {
		register_setting( self::SETTINGS_URL, self::SETTINGS_URL );

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
	 * @param array $taxonomies
	 */
	protected function init_tax_settings( $post_type, $taxonomies ) {
		$setting_type = 'tax';
		$this->add_settings_section( $post_type, $setting_type );
		foreach ( $taxonomies as $tax ) {
			foreach ( $this->options_map() as $option_name => $label ) {
				$option_label = sprintf( $label, $tax->label );
				$is_supported = Settings_Helper::is_supported( $option_name, $setting_type );
				$this->add_settings_checkbox( $post_type, 'tax', $tax->name, $option_name, $option_label, $is_supported );
			}
		}
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
	protected function init_acf_fields_settings( $post_type, $fields ) {
		$setting_type = 'fields';
		$this->add_settings_section( $post_type, $setting_type );
		foreach ( $fields as $field ) {
			foreach ( $this->options_map() as $option_name => $label ) {
				$option_label = sprintf( $label, $field['label'] );
				$is_supported = Settings_Helper::is_supported( $option_name, $setting_type, $field['type'] );
				$this->add_settings_checkbox( $post_type, $setting_type, $field['name'], $option_name, $option_label, $is_supported );
			}
		}
	}

	/**
	 * @param string $post_type
	 */
	protected function init_meta_fields_settings( $post_type ) {
		$setting_type = 'meta_fields';
		$meta_fields  = self::get_post_type_meta_keys( $post_type );
		if ( empty( $meta_fields ) ) {
			return;
		}

		$section_has_settings = false;

		foreach ( $meta_fields as $meta_field ) {
			if ( 0 === strpos( $meta_field, '_' ) ) {
				// Let's skip metas which begin with "_"
				continue;
			}
			foreach ( $this->options_map() as $option_name => $label ) {
				$section_has_settings = true;
				$field_name           = sprintf( $label, Settings_Helper::get_meta_field_name( $meta_field ) );
				$option_label         = sprintf( '%s (%s)', $field_name, $meta_field );
				$this->add_settings_checkbox( $post_type, $setting_type, $meta_field, $option_name, $option_label, true );
			}
		}

		if ( $section_has_settings ) {
			$this->add_settings_section( $post_type, $setting_type );
		}
	}

	/**
	 * @param string $post_type
	 * @param string $type
	 */
	protected function add_settings_section( $post_type, $type ) {
		$post_type_object = get_post_type_object( $post_type );

		add_settings_section(
			$this->get_settings_section_id( $post_type, $type ),
			$this->get_settings_section_title( $post_type_object->label, $type ),
			null,
			self::SETTINGS_URL
		);
	}


	/**
	 * @param string $post_label
	 * @param string $type
	 *
	 * @return string
	 */
	protected function get_settings_section_title( $post_label, $type ) {
		$name_map = [
			'tax'         => 'taxonomies',
			'fields'      => 'ACF fields',
			'meta_fields' => 'meta fields',
		];

		return __( sprintf( '%s %s', $post_label, $name_map[ $type ] ), 'wordpress' );
	}


	/**
	 * @return array
	 */
	public function options_map() {
		return [
			'show_in_column' => '%s',
			/* 'filter'         => 'Filter by %s',
			 'is_numeric'     => 'Is numeric',
			 'sort'           => 'Sort by %s',*/
		];
	}

	/**
	 * @param string $post_type
	 * @param string $type
	 * @param string $field_name
	 * @param string $option_name
	 * @param string $option_label
	 * @param bool $is_supported
	 */
	public function add_settings_checkbox( $post_type, $type, $field_name, $option_name, $option_label, $is_supported ) {
		$settings      = $this->get_settings();
		$settings_page = self::SETTINGS_URL;
		add_settings_field(
			sprintf( '%s_%s_%s', $option_name, $type, $field_name ),
			$option_label,
			[ $this, 'settings_field_callback' ],
			$settings_page,
			$this->get_settings_section_id( $post_type, $type ),
			compact( 'settings', 'settings_page', 'post_type', 'field_name', 'is_supported', 'option_name', 'type' )
		);
	}

	/**
	 * @param string $post_type
	 * @param string $type
	 *
	 * @return string
	 */
	protected function get_settings_section_id( $post_type, $type ) {
		return sprintf( 'pcm_post_type_%s_%s', $post_type, $type );
	}

	/**
	 * @param array $args
	 */
	public function settings_field_callback( $args ) {
		$settings      = $args['settings'];
		$settings_page = $args['settings_page'];
		$post_type     = $args['post_type'];
		$field_name    = $args['field_name'];
		$is_supported  = $args['is_supported'];
		$option_name   = $args['option_name'];
		$type          = $args['type'];

		$setting_name = sprintf( '%s[%s][%s][%s][%s]', $settings_page, $post_type, $type, $field_name, $option_name );
		if ( isset( $settings[ $post_type ][ $type ][ $field_name ][ $option_name ] ) ) {
			$setting_value = $settings[ $post_type ][ $type ][ $field_name ][ $option_name ];
		} else {
			$setting_value = 0;
		}

		Renderer::render( 'checkbox', [
			'setting_name' => $setting_name,
			'is_checked'   => $setting_value,
			'is_supported' => $is_supported,
		] );
	}

	/**
	 * @return array|mixed
	 */
	public function get_settings() {
		if ( is_null( $this->settings ) ) {
			$this->settings = get_option( self::SETTINGS_URL );
		}

		return $this->settings;
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


	public function render_plugin_page() {
		Renderer::render( 'settings', [ 'option_group' => self::SETTINGS_URL ] );
	}
}
