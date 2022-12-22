<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_fix_broken_html', 50 );
function etm_register_fix_broken_html( $settings_array ){
	$settings_array[] = array(
		'name'          => 'fix_broken_html',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Fix broken HTML', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'General attempt to fix broken or missing HTML on translated pages.<br/>', 'etranslation-multilingual' ), array( 'br' => array(), 'strong' => array() ) ),
	);
	return $settings_array;
}

add_filter('etm_try_fixing_invalid_html', 'etm_fix_broken_html');
function etm_fix_broken_html($allow) {

	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['fix_broken_html'] ) && $option['fix_broken_html'] === 'yes' ) {
		return true;
	}
	return $allow;
}
