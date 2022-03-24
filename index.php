<?php
/*
Plugin Name: eTranslation Multilingual
Plugin URI: https://ec.europa.eu/info/index_en
Description: Experience a better way of translating your WordPress site using a visual front-end translation editor, with full support for WooCommerce and site builders.
Version: 0.0.1
Author: EC
Author URI: https://ec.europa.eu
Text Domain: translatepress-multilingual
Domain Path: /languages
License: GPL2
WC requires at least: 2.5.0
WC tested up to: 6.2

== Copyright ==

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Register callback methods for eTranslation
add_action('rest_api_init', 'register_callback');

function register_callback() {
    register_rest_route('etranslation/v1', 'error_callback/(?P<id>[a-zA-Z0-9._-]+)', array(
        'methods' => array(
            'GET',
            'POST'
        ) ,
        'callback' => 'translation_error_callback',
        'args' => array() ,
        'permission_callback' => function () {
            return true;
        }
    ));
    register_rest_route('etranslation/v1', 'document/destination/(?P<id>[a-zA-Z0-9._-]+)', array(
        'methods' => array(
            'GET',
            'POST'
        ) ,
        'callback' => 'translation_document_destination',
        'args' => array() ,
        'permission_callback' => function () {
            return true;
        }
    ));
}

function translation_error_callback(WP_REST_Request $request): WP_REST_Response {
    $response = new WP_REST_Response(etranslation_query_action('translation_document_destination', $request));
    $response->set_status(200);
    return $response;
}

function translation_document_destination(WP_REST_Request $request): WP_REST_Response {
    $response = new WP_REST_Response(etranslation_query_action('translation_document_destination', $request));
    $response->set_status(200);
    return $response;
}

function etranslation_query_action($action, $arg) {
    $trp = TRP_Translate_Press::get_trp_instance();
    $settings = $trp->get_component('settings')->get_settings();
    $response = "";
    if ($settings['trp_machine_translation_settings']['translation-engine'] === 'etranslation') {
        $mt_engine = $trp->get_component('machine_translator');
        $response = $mt_engine->etranslation_query->$action($arg);
    }
    return $response;
}

function trp_enable_translatepress(){
	$enable_translatepress = true;
	$current_php_version = apply_filters( 'trp_php_version', phpversion() );

	// 5.6.20 is the minimum version supported by WordPress
	if ( $current_php_version !== false && version_compare( $current_php_version, '5.6.20', '<' ) ){
		$enable_translatepress = false;
		add_action( 'admin_menu', 'trp_translatepress_disabled_notice' );
	}

	return apply_filters( 'trp_enable_translatepress', $enable_translatepress );
}

if ( trp_enable_translatepress() ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-translate-press.php';

	/** License classes includes here
	 * Since version 1.4.6
	 * It need to be outside of a hook so it load before the classes that are in the addons, that we are trying to phase out
	 */
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-edd-sl-plugin-updater.php';

	/* make sure we execute our plugin before other plugins so the changes we make apply across the board */
	add_action( 'plugins_loaded', 'trp_run_translatepress_hooks', 1 );
}

function trp_run_translatepress_hooks(){
	$trp = TRP_Translate_Press::get_trp_instance();
	$trp->run();
}

function trp_translatepress_disabled_notice(){
	echo '<div class="notice notice-error"><p>' . wp_kses( sprintf( __( '<strong>eTranslation Multilingual</strong> requires at least PHP version 5.6.20+ to run. It is the <a href="%s">minimum requirement of the latest WordPress version</a>. Please contact your server administrator to update your PHP version.','translatepress-multilingual' ), 'https://wordpress.org/about/requirements/' ), array( 'a' => array( 'href' => array() ), 'strong' => array() ) ) . '</p></div>';
}


//This is for the DEV version
if( file_exists(plugin_dir_path( __FILE__ ) . '/index-dev.php') )
    include_once( plugin_dir_path( __FILE__ ) . '/index-dev.php');