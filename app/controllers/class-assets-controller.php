<?php

namespace PCM\Controllers;


use PCM\Helpers\ACF_Helper;
use PCM\Helpers\Renderer;
use PCM\Helpers\Settings_Helper;
use PCM\Traits\Singleton;

/**
 * Class Assets_Controller
 * @package PCM
 */
class Assets_Controller {

	use Singleton;

	public function init() {
		add_action( 'admin_init', [ $this, 'enqueue_assets' ] );

		return $this;
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
}
