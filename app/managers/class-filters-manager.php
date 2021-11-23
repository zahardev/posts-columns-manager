<?php

namespace PCM\Managers;

use PCM\Helpers\Renderer;
use PCM\Controllers\Settings_Controller;


class Filters_Manager extends Abstract_Manager {

	/**
	 * @var Settings_Controller $settings
	 */
	private $settings;

	public function init( Settings_Controller $settings ) {
		$this->settings = $settings;
		add_action( 'restrict_manage_posts', [ $this, 'show_filters' ] );
		add_filter( 'pre_get_posts', [ $this, 'filter_posts' ] );
	}

	public function filter_posts( \WP_Query $wp_query ) {
		if ( ! is_admin() || ! $wp_query->is_main_query() || ! $columns_settings = $this->settings->get_post_settings() ) {
			return $wp_query;
		}

		$meta_query = $wp_query->get( 'meta_query' );
		$meta_query = is_array( $meta_query ) ? $meta_query : [];

		$tax_query = $wp_query->get( 'tax_query' );
		$tax_query = is_array( $tax_query ) ? $tax_query : [];

		foreach ( $columns_settings as $type => $type_group ) {
			if ( 'tax' === $type ) {
				$wp_query->set( 'tax_query', $this->get_tax_meta_queries( $type_group, $tax_query ) );
			} else {
				$wp_query->set( 'meta_query', $this->get_fields_meta_queries( $type_group, $meta_query ) );
			}
		}

		return $wp_query;
	}

	private function get_tax_meta_queries( $type_group, $tax_query ) {
		foreach ( $type_group as $tax => $column_settings ) {
			if ( empty( $column_settings['filter'] ) ) {
				continue;
			}
			if ( $query = $this->get_tax_meta_query( $tax ) ) {
				$tax_query[] = $query;
			}
		}

		return $tax_query;
	}


	private function get_tax_meta_query( $tax ) {
		$term_id   = $this->get_query_val( $tax );
		$tax_query = [];

		if ( $term_id ) {
			$tax_query = [
				'taxonomy' => $tax,
				'terms'    => [ $term_id ],
				'field'    => 'slug',
				'operator' => 'IN',
			];
		}

		return $tax_query;
	}

	private function get_fields_meta_queries( $type_group, $meta_query ) {
		foreach ( $type_group as $field_name => $column_settings ) {

			$filter = $this->get_query_val( $field_name );

			if ( empty( $filter ) ) {
				continue;
			}

			if ( empty( $column_settings['is_numeric'] ) ) {
				$query = $this->get_text_meta_query( $field_name );
			} else {
				$query = $this->get_number_meta_query( $field_name );
			}

			if ( $query ) {
				$meta_query = array_merge( $meta_query, $query );
			}
		}

		return $meta_query;
	}

	private function get_number_meta_query( $field_name ) {
		$val_from = $this->get_query_val_from( $field_name );
		$val_to   = $this->get_query_val_to( $field_name );

		$meta_query = [];

		if ( $val_from ) {
			$meta_query[] = [
				'key'     => $field_name,
				'value'   => $val_from,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			];
		}

		if ( $val_to ) {
			$meta_query[] = [
				'key'     => $field_name,
				'value'   => $val_to,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			];
		}

		return $meta_query;
	}

	private function get_text_meta_query( $field_name ) {
		$val        = $this->get_query_val( $field_name );
		$meta_query = [];

		if ( $val ) {
			$meta_query[] = [
				'key'     => $field_name,
				'value'   => $val,
				'compare' => 'LIKE',
				'type'    => 'TEXT',
			];
		}

		return $meta_query;
	}

	public function show_filters() {
		if ( ! $columns_settings = $this->settings->get_post_settings() ) {
			return;
		}

		foreach ( $columns_settings as $type => $type_group ) {
			foreach ( $type_group as $name => $column_settings ) {
				if ( empty( $column_settings['filter'] ) ) {
					continue;
				}

				if ( 'tax' === $type ) {
					$tax = get_taxonomy( $name );
					wp_dropdown_categories( [
						'hide_empty'      => true,
						'show_option_all' => 'All from ' . $tax->label,
						'taxonomy'        => $name,
						'hierarchical'    => 1,
						'name'            => $name,
						'selected'        => $this->get_query_val( $name ),
						'value_field'     => 'slug',
					] );
					continue;
				}

				$column = $this->get_column( $type, $name );

				if ( empty( $column_settings['is_numeric'] ) ) {
					Renderer::render( 'text', [
						'label' => $column->title,
						'param' => $column->name,
						'value' => $this->get_query_val( $column->name ),
					] );
				} else {
					Renderer::render( 'range', [
						'label'      => $column->title,
						'param_from' => $this->get_param_from( $column->name ),
						'param_to'   => $this->get_param_to( $column->name ),
						'val_from'   => $this->get_query_val_from( $column->name ),
						'val_to'     => $this->get_query_val_to( $column->name ),
					] );
				}
			}
		}
	}

	private function get_query_val( $query_var, $filter = FILTER_SANITIZE_STRING ) {
		return filter_input( INPUT_GET, $query_var, $filter );
	}

	private function get_query_val_from( $name ) {
		return $this->get_query_val( $this->get_param_from( $name ), FILTER_VALIDATE_INT );
	}

	private function get_query_val_to( $name ) {
		return $this->get_query_val( $this->get_param_to( $name ), FILTER_VALIDATE_INT );
	}


	private function get_param_from( $name ) {
		return $name . '-from';
	}

	private function get_param_to( $name ) {
		return $name . '-to';
	}
}
