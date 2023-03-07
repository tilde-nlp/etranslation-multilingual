<?php
add_filter( 'etm_register_advanced_settings', 'etm_register_strip_gettext_post_content', 60 );
function etm_register_strip_gettext_post_content( $settings_array ){
	$settings_array[] = array(
		'name'          => 'strip_gettext_post_content',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Filter Gettext wrapping from post content and title', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'Filters gettext wrapping such as #!etmst#etm-gettext from all updated post content and post title. Does not affect previous post content. <br/><strong>Database backup is recommended before switching on.</strong>', 'etranslation-multilingual' ), array( 'br' => array(), 'strong' => array()) ),
	);
	return $settings_array;
}

/**
 * Strip gettext wrapping from post title and content.
 * They will be regular strings, written in the language they were submitted.
 * Filter called both for wp_insert_post and wp_update_post
 */
add_filter('wp_insert_post_data', 'etm_filter_etmgettext_from_post_content', 10, 2 );
function etm_filter_etmgettext_from_post_content($data, $postarr ){
	$option = get_option( 'etm_advanced_settings', true );
	if ( isset( $option['strip_gettext_post_content'] ) && $option['strip_gettext_post_content'] === 'yes' && class_exists( 'ETM_Translation_Manager' ) ){
		$data['post_content'] = ETM_Translation_Manager::strip_gettext_tags($data['post_content']);
		$data['post_title'] = ETM_Translation_Manager::strip_gettext_tags($data['post_title']);
	}
	return $data;
}