<?php
/*
 Plugin Name: Posts Columns Manager
 Author: Sergey Zakharchenko
 Author URI:  https://github.com/zahardoc
 Version: 1.0
 Description: Manage WordPress admin post table columns. Supports ACF fields. You can: add columns, remove columns, filter by columns.
 License:     GPL2

Posts Columns Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Posts Columns Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Stripe Payments Custom Fields. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html.

*/

if ( ! function_exists( 'add_action' ) ) {
    exit;
}

define( 'PCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PCM_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'PCM_TEXT_DOMAIN', 'PCM' );
define( 'PCM_PLUGIN_VERSION', '1.0' );

require_once 'wp-autoloader.php';

PCM\App::instance()->init();
