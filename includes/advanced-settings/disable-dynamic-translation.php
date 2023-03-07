<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_disable_dynamic_translation', 30 );
function etm_register_disable_dynamic_translation( $settings_array ){
	$settings_array[] = array(
		'name'          => 'disable_dynamic_translation',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Disable dynamic translation', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'It disables detection of strings displayed dynamically using JavaScript. <br/>Strings loaded via a server side AJAX call will still be translated.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

add_filter( 'etm_enable_dynamic_translation', 'etm_adst_disable_dynamic' );
function etm_adst_disable_dynamic( $enable ){
	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['disable_dynamic_translation'] ) && $option['disable_dynamic_translation'] === 'yes' ){
		return false;
	}
	return $enable;
}

add_filter( 'etm_editor_missing_scripts_and_styles', 'etm_adst_disable_dynamic2' );
function etm_adst_disable_dynamic2( $scripts ){
	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['disable_dynamic_translation'] ) && $option['disable_dynamic_translation'] === 'yes' ){
		unset($scripts['etm-translate-dom-changes.js']);
	}
	return $scripts;
}