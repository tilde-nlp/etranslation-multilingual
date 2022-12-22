<?php

/** Post title */
add_filter( 'etm_register_advanced_settings', 'etm_register_disable_post_container_tags_for_post_title', 510 );
function etm_register_disable_post_container_tags_for_post_title( $settings_array ){
	$settings_array[] = array(
		'name'          => 'disable_post_container_tags_for_post_title',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Disable post container tags for post title', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'It disables search indexing the post title in translated languages.<br/>Useful when the title of the post doesn\'t allow HTML thus breaking the page.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

add_filter( 'etm_before_running_hooks', 'etm_remove_hooks_to_disable_post_title_search_wraps' );
function etm_remove_hooks_to_disable_post_title_search_wraps( $etm_loader ){
    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['disable_post_container_tags_for_post_title'] ) && $option['disable_post_container_tags_for_post_title'] === 'yes' ) {
        $etm                = ETM_eTranslation_Multilingual::get_etm_instance();
        $translation_render = $etm->get_component( 'translation_render' );
        $etm_loader->remove_hook( 'the_title', 'wrap_with_post_id', $translation_render );
    }
}


/** Post content */
add_filter( 'etm_register_advanced_settings', 'etm_register_disable_post_container_tags_for_post_content', 520 );
function etm_register_disable_post_container_tags_for_post_content( $settings_array ){
    $settings_array[] = array(
        'name'          => 'disable_post_container_tags_for_post_content',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Disable post container tags for post content', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'It disables search indexing the post content in translated languages.<br/>Useful when the content of the post doesn\'t allow HTML thus breaking the page.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
    );
    return $settings_array;
}

add_filter( 'etm_before_running_hooks', 'etm_remove_hooks_to_disable_post_content_search_wraps' );
function etm_remove_hooks_to_disable_post_content_search_wraps( $etm_loader ){
    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['disable_post_container_tags_for_post_content'] ) && $option['disable_post_container_tags_for_post_content'] === 'yes' ) {
        $etm                = ETM_eTranslation_Multilingual::get_etm_instance();
        $translation_render = $etm->get_component( 'translation_render' );
        $etm_loader->remove_hook( 'the_content', 'wrap_with_post_id', $translation_render );
	    remove_action( 'do_shortcode_tag', 'tp_oxygen_search_compatibility', 10, 4 );
    }
}


