<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_exclude_words_from_auto_translate', 100 );
function etm_register_exclude_words_from_auto_translate( $settings_array ){
    $settings_array[] = array(
        'name'          => 'exclude_words_from_auto_translate',
        'type'          => 'list',
        'columns'       => array(
            'words' => __('String', 'etranslation-multilingual' ),
        ),
        'label'         => esc_html__( 'Exclude strings from automatic translation', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Do not automatically translate these strings (ex. names, technical words...)<br>Paragraphs containing these strings will still be translated except for the specified part.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
    );
    return $settings_array;
}


add_filter( 'etm_exclude_words_from_automatic_translation', 'etm_exclude_words_from_auto_translate' );
function etm_exclude_words_from_auto_translate( $exclude_words ){
    $option = get_option( 'etm_advanced_settings', true );
    $add_skip_selectors = array( );
    if ( isset( $option['exclude_words_from_auto_translate'] ) && is_array( $option['exclude_words_from_auto_translate']['words'] ) ) {
        $exclude_words = array_merge( $exclude_words, $option['exclude_words_from_auto_translate']['words'] );
    }

    return $exclude_words;
}

