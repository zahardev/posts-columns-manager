<?php
/**
 * Plugin Name: Posts Columns Manager
 * Author: Sergiy Zakharchenko
 * Author URI:  https://github.com/zahardev
 * Version: 1.7.0
 * Description: Manage custom columns in the posts editor. You can: add columns, sort and filter posts in the custom columns.
 * License:     GPL2
 * Text Domain: posts-columns-manager
 */

if ( ! function_exists( 'add_action' ) ) {
    exit;
}

define( 'PCM_PLUGIN_VERSION', '1.7.0' );
define( 'PCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PCM_PLUGIN_URL', plugins_url( '', __FILE__ ) );

require_once 'wp-autoloader.php';

PCM\App::instance()->init();
