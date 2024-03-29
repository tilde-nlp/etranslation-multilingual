<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_html_lang_attribute', 1001 );
function etm_register_html_lang_attribute( $settings_array ){
    $settings_array[] = array(
        'name'          => 'html_lang_remove_locale',
        'type'          => 'radio',
        'options'       => array( 'default', 'regional' ),
        'default'       => 'default',
        'labels'        => array( esc_html__( 'Default (example: en-US, fr-CA, etc.)', 'etranslation-multilingual' ), esc_html__( 'Regional (example: en, fr, es, etc.)', 'etranslation-multilingual' ) ),
        'label'         => esc_html__( 'HTML Lang Attribute Format', 'etranslation-multilingual' ),
        'description'   => wp_kses(  __( 'Change lang attribute of the html tag to a format that includes country regional or not. <br>In HTML, the lang attribute (<html lang="en-US">)  should be used to  specify the language of text content so that the  browser can correctly display or process  your content (eg. for  hyphenation, styling, spell checking, etc).', 'etranslation-multilingual' ), array( 'br' => array() ) ),
    );
    return $settings_array;
}

add_filter( 'etm_add_default_lang_tags', 'etm_display_default_lang_tag' );
function etm_display_default_lang_tag( $display ){
    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['html_lang_remove_locale'] ) && $option['html_lang_remove_locale'] === 'default' ) {
        return true;
    }
    return false;
}

add_filter( 'etm_add_regional_lang_tags', 'etm_display_regional_lang_tag' );
function etm_display_regional_lang_tag( $display ){

    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['html_lang_remove_locale'] ) && $option['html_lang_remove_locale'] === 'regional' ) {
        return true;
    }
    return false;
}