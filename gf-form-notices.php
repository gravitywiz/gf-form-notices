<?php
/**
 * Plugin Name: GF Form Notices
 * Plugin URI: https://gravitywiz.com
 * Description: Display scheduled messages above Gravity Forms based on date ranges. Perfect for office closings, promotions, announcements, and more.
 * Version: 1.0.1
 * Author: Gravity Wiz
 * Author URI: https://gravitywiz.com
 * License: GPL-2.0+
 * Text Domain: gf-form-notices
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GF_FORM_NOTICES_VERSION', '1.0.1' );
define( 'GF_FORM_NOTICES_FILE', __FILE__ );
define( 'GF_FORM_NOTICES_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'gform_loaded', array( 'GF_Form_Notices_Bootstrap', 'load' ), 5 );

class GF_Form_Notices_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( GF_FORM_NOTICES_PATH . 'class-gf-form-notices.php' );

		GFAddOn::register( 'GF_Form_Notices' );
	}

}

function gf_form_notices() {
	return GF_Form_Notices::get_instance();
}
