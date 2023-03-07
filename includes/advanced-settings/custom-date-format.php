<?php
/**
 * Register advanced configuration option for custom date formatting for every translated language
 * The settings uses the 'input_array' advanced setting
 * Saves as a key-value pair
 *
 */
add_filter( 'etm_register_advanced_settings', 'etm_register_language_date_format', 1205 );
function etm_register_language_date_format( $settings_array ){

	$settings_array[] = array(
		'name'          => 'language_date_format',
		'rows'          => etm_get_languages("nodefault"),
		'default'       => '',
		'type'          => 'input_array',
		'label'         => esc_html__( 'Date format', 'etranslation-multilingual' ),
		'description'   => wp_kses(  __( 'Customize the date formatting per each translated language.<br/>Leave empty for default WP setting or see more information <a href="https://wordpress.org/support/article/formatting-date-and-time/" title="Formatting Date and Time" target="_blank">here</a>', 'etranslation-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) )),
	);

	return $settings_array;
}