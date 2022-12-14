<?php

//add_filter( 'trp_register_advanced_settings', 'trp_register_enable_auto_translate_slug', 1070 );
function trp_register_enable_auto_translate_slug( $settings_array ){
	$settings_array[] = array(
		'name'          => 'enable_auto_translate_slug',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Automatically translate slugs', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'Generate automatic translations of slugs for posts, pages and Custom Post Types.<br/>Requires SEO Pack Add-on to be installed and activated.<br>The slugs will be automatically translated starting with the second refresh of each page.', 'etranslation-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ),
	);
	return $settings_array;
}

add_filter('trp_machine_translate_slug', 'trp_enable_auto_translate_slug');
function trp_enable_auto_translate_slug($allow) {

	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['enable_auto_translate_slug'] ) && $option['enable_auto_translate_slug'] === 'yes' ) {
		return true;
	}
	return $allow;
}
