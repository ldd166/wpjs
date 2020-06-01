<?php
/**
 * Description
 *
 * @package JsDelivrCdn
 * Plugin Name: jsDelivr CDN
 * Plugin URI: https://jsdelivr.com/wp-plugin
 * Description: The official plugin of jsDelivr.com, a free public CDN. An easy way to integrate the service and speed up your website using our super fast CDN.
 * Version: 1.0
 * Author: ProspectOne
 * Author URI: https://prospectone.io/
 * License: GPLv2 or later
 * Text Domain: jsdelivrcdn
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Automattic, Inc.
*/

/*Make sure we don't expose any info if called directly*/
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'JSDELIVRCDN_MINIMUM_WP_VERSION', '4.0' );
define( 'JSDELIVRCDN_PLUGIN_FILE', __FILE__ );
define( 'JSDELIVRCDN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'JSDELIVRCDN_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'JSDELIVRCDN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Add cron interval
 */
add_filter(
	'cron_schedules',
	function( $schedules ) {
		if ( ! isset( $schedules['fifteen_minutes'] ) ) {
			$schedules['fifteen_minutes'] = array(
				'interval' => 15 * 60,
				'display'  => esc_html__( 'Every Fifteen Minutes' ),
			);
		}
		return $schedules;
	}
);

/**
 * Include JsdelivrCdn class
 */
require_once JSDELIVRCDN_PLUGIN_PATH . 'classes/class-jsdelivrcdn.php';

/** Activation */
register_activation_hook( __FILE__, array( 'JsDelivrCdn', 'activate' ) );
/** Deactivation */
register_deactivation_hook( __FILE__, array( 'JsDelivrCdn', 'deactivate' ) );

add_action( 'init', array( 'JsDelivrCdn', 'init' ) );
