<?php

namespace PCM\Entities;

use PCM\Controllers\Settings_Controller;

/**
 * Class Settings_Tab
 * @package PCM\Entities
 */
class Settings_Tab {

	/**
	 * string @var
	 */
	public $id;

	/**
	 * string @var
	 */
	public $title;

	/**
	 * string @var
	 */
	public $callback;

	/**
	 * string @var
	 */
	public $description;

	/**
	 * Field constructor.
	 */
	public function __construct( $args ) {
		$this->id          = $args['id'];
		$this->title       = $args['title'];
		$this->callback    = $args['callback'];
		$this->description = $args['description'];
	}

	/**
	 * @return string|void
	 */
	public function get_url() {
		$url = admin_url( sprintf( 'options-general.php?page=%s', Settings_Controller::MANAGER_SETTINGS_URL ) );
		if ( Settings_Controller::DEFAULT_TAB === $this->id ) {
			return $url;
		}

		return add_query_arg( 'tab', $this->id, $url );
	}
}
