<?php

add_filter( 'trp_register_advanced_settings', 'trp_show_regular_tab_in_string_translation', 525 );
function trp_show_regular_tab_in_string_translation( $settings_array ){
	$settings_array[] = array(
		'name'          => 'show_regular_tab_in_string_translation',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Show regular strings tab in String Translation', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'Adds an additional tab on the String Translation interface that allows editing translations of user-inputted strings.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

add_filter( 'trp_show_regular_strings_string_translation', 'trp_show_regular_strings_tab_string_translation' );
function trp_show_regular_strings_tab_string_translation( $enable ){
	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['show_regular_tab_in_string_translation'] ) && $option['show_regular_tab_in_string_translation'] === 'yes' ){
		return true;
	}
	return $enable;
}
