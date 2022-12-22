<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_troubleshoot_separator', 5 );
function etm_register_troubleshoot_separator( $settings_array ){
    $settings_array[] = array(
        'name'          => 'troubleshoot_options',
        'type'          => 'separator',
        'label'         => esc_html__( 'Troubleshooting', 'etranslation-multilingual' ),
        'no-border'     => true
    );
    return $settings_array;
}

add_filter( 'etm_register_advanced_settings', 'etm_register_exclude_separator', 95 );
function etm_register_exclude_separator( $settings_array ){
    $settings_array[] = array(
        'name'          => 'exclude_strings',
        'type'          => 'separator',
        'label'         => esc_html__( 'Exclude strings', 'etranslation-multilingual' )
    );
    return $settings_array;
}

add_filter( 'etm_register_advanced_settings', 'etm_register_debug_separator', 500 );
function etm_register_debug_separator( $settings_array ){
	$settings_array[] = array(
	    'name'          => 'debug_options',
		'type'          => 'separator',
		'label'         => esc_html__( 'Debug', 'etranslation-multilingual' )
	);
	return $settings_array;
}

add_filter( 'etm_register_advanced_settings', 'etm_register_miscellaneous_separator', 1000 );
function etm_register_miscellaneous_separator( $settings_array ){
    $settings_array[] = array(
        'name'          => 'miscellaneous_options',
        'type'          => 'separator',
        'label'         => esc_html__( 'Miscellaneous options', 'etranslation-multilingual' )
    );
    return $settings_array;
}

add_filter( 'etm_register_advanced_settings', 'etm_register_custom_language_separator', 2000 );
function etm_register_custom_language_separator( $settings_array ){
	$settings_array[] = array(
		'name'          => 'custom_language',
		'type'          => 'separator',
		'label'         => esc_html__( 'Custom language', 'etranslation-multilingual' )
	);
	return $settings_array;
}