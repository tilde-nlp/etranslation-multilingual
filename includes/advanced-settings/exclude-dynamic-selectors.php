<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_skip_dynamic_selectors', 110 );
function etm_register_skip_dynamic_selectors( $settings_array ){
	$settings_array[] = array(
		'name'          => 'skip_dynamic_selectors',
		'type'          => 'list',
		'columns'       => array(
			'selector' => __('Selector', 'etranslation-multilingual' ),
		),
		'label'         => esc_html__( 'Exclude from dynamic translation', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'Do not dynamically translate strings that are found in html nodes matching these selectors.<br>Excludes all the children of HTML nodes matching these selectors from being translated using JavaScript.<br/>These strings will still be translated on the server side if possible.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}


 add_filter( 'etm_skip_selectors_from_dynamic_translation', 'etm_skip_dynamic_translation_for_selectors' );
function etm_skip_dynamic_translation_for_selectors( $skip_selectors ){
	$option = get_option( 'etm_advanced_settings', true );
	$add_skip_selectors = array( );
	if ( isset( $option['skip_dynamic_selectors'] ) && is_array( $option['skip_dynamic_selectors']['selector'] ) ) {
		$add_skip_selectors = $option['skip_dynamic_selectors']['selector'];
	}
	return array_merge( $skip_selectors, $add_skip_selectors );
}
