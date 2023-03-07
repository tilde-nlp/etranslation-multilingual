<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_show_dynamic_content_before_translation', 20 );
function etm_register_show_dynamic_content_before_translation( $settings_array ){
	$settings_array[] = array(
		'name'          => 'show_dynamic_content_before_translation',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Fix missing dynamic content', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'May help fix missing content inserted using JavaScript. <br> It shows dynamically inserted content in original language for a moment before the translation request is finished.', 'etranslation-multilingual' ), array( 'br' => array()) ),
	);
	return $settings_array;
}


/**
* Apply "show dynamic content before translation" fix only on front page
*/
add_filter( 'etm_show_dynamic_content_before_translation', 'etm_show_dynamic_content_before_translation' );
function etm_show_dynamic_content_before_translation( $allow ){
	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['show_dynamic_content_before_translation'] ) && $option['show_dynamic_content_before_translation'] === 'yes' ){
		return true;
	}
	return $allow;
}
