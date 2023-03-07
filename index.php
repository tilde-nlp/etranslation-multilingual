<?php
/*
Plugin Name: eTranslation Multilingual
Description: Make your site multilingual in few steps with eTranslation Multilingual Wordpress plugin. 
Version: 1.0.0
Author: Tilde
Author URI: https://tilde.com/
Text Domain: etranslation-multilingual
Domain Path: /languages
License: GPL2
WC requires at least: 2.5.0
WC tested up to: 7.2.1

== Copyright ==
Copyright (C) 2022 European Union, 2017 Cozmoslabs (www.cozmoslabs.com)

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

==

eTranslation Multilingual is a fork of TranslatePress by Cozmoslabs (www.cozmoslabs.com). 
Original plugin (TranslatePress) was developed by: Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban.

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
    $response = new WP_REST_Response(etranslation_query_action('translation_error_callback', $request));
    $response->set_status(200);
    return $response;
}

function translation_document_destination(WP_REST_Request $request): WP_REST_Response {
    $response = new WP_REST_Response(etranslation_query_action('translation_document_destination', $request));
    $response->set_status(200);
    return $response;
}

function etranslation_query_action($action, $arg) {
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $settings = $etm->get_component('settings')->get_settings();
    $response = "";
    if ($settings['etm_machine_translation_settings']['translation-engine'] === 'etranslation') {
        $mt_engine = $etm->get_component('machine_translator');
        $response = $mt_engine->etranslation_query->$action($arg);
    }
    return $response;
}

defined('DEFAULT_ETRANSLATION_TIMEOUT') or define('DEFAULT_ETRANSLATION_TIMEOUT', 7);
defined('ETM_HTTP_REQUEST_TIMEOUT') or define('ETM_HTTP_REQUEST_TIMEOUT', 30);

function etm_enable_etranslation_multilingual(){
	$enable_etranslation_multilingual = true;
	$current_php_version = apply_filters( 'etm_php_version', phpversion() );

    if (!function_exists( 'is_plugin_active')) {
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }

	// 5.6.20 is the minimum version supported by WordPress
	if ( $current_php_version !== false && version_compare( $current_php_version, '5.6.20', '<' ) ){
		$enable_etranslation_multilingual = false;
		add_action( 'admin_menu', 'etm_etranslation_multilingual_disabled_notice' );
	}
    if (is_plugin_active('translatepress-multilingual/index.php') && !(isset($_REQUEST['action']) && $_REQUEST['action'] == 'deactivate')) {
		add_action( 'admin_init', 'etm_tp_detected_notice' );
    }

	return apply_filters( 'etm_enable_etranslation_multilingual', $enable_etranslation_multilingual );
}

if ( etm_enable_etranslation_multilingual() ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-etranslation-multilingual.php';

	/* make sure we execute our plugin before other plugins so the changes we make apply across the board */
	add_action( 'plugins_loaded', 'etm_run_etranslation_multilingual_hooks', 1 );
}

function etm_run_etranslation_multilingual_hooks(){
	$etm = ETM_eTranslation_Multilingual::get_etm_instance();
	$etm->run();
}

function etm_etranslation_multilingual_disabled_notice(){
	echo '<div class="notice notice-error"><p>' . wp_kses( sprintf( __( '<strong>eTranslation Multilingual</strong> requires at least PHP version 5.6.20+ to run. It is the <a href="%s">minimum requirement of the latest WordPress version</a>. Please contact your server administrator to update your PHP version.','etranslation-multilingual' ), 'https://wordpress.org/about/requirements/' ), array( 'a' => array( 'href' => array() ), 'strong' => array() ) ) . '</p></div>';
}

function etm_tp_detected_notice() {
    echo '<div class="notice notice-warning"><p>' . wp_kses(__( '<strong>eTranslation Multilingual</strong> may not work correctly with TranslatePress enabled.','etranslation-multilingual' ), array('strong' => array())) . '</p></div>';
}

/**
 * Redirect users to the settings page on plugin activation
 */
add_action( 'activated_plugin', 'etm_plugin_activation_redirect' );
function etm_plugin_activation_redirect( $plugin ){

	if( !wp_doing_ajax() && $plugin == plugin_basename( __FILE__ ) ) {
		wp_safe_redirect( admin_url( 'options-general.php?page=etranslation-multilingual' ) );
		exit();
	}

}
//This is for the DEV version
if( file_exists(plugin_dir_path( __FILE__ ) . '/index-dev.php') )
    include_once( plugin_dir_path( __FILE__ ) . '/index-dev.php');
