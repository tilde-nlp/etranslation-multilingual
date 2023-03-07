<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_exclude_selectors', 110 );
function etm_register_exclude_selectors( $settings_array ){
    $settings_array[] = array(
        'name'          => 'exclude_translate_selectors',
        'type'          => 'list',
        'columns'       => array(
            'selector' => __('Selector', 'etranslation-multilingual' ),
        ),
        'label'         => esc_html__( 'Exclude selectors from translation', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Do not translate strings that are found in html nodes matching these selectors.<br>Excludes all the children of HTML nodes matching these selectors from being translated.<br>These strings cannot be translated manually nor automatically.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
    );
    return $settings_array;
}


add_filter( 'etm_no_translate_selectors', 'etm_skip_translation_for_selectors' );
function etm_skip_translation_for_selectors( $skip_selectors ){
    $option = get_option( 'etm_advanced_settings', true );
    $add_skip_selectors = array( );
    if ( isset( $option['exclude_translate_selectors'] ) && is_array( $option['exclude_translate_selectors']['selector'] ) ) {
        $add_skip_selectors = $option['exclude_translate_selectors']['selector'];
    }

    return array_merge( $skip_selectors, $add_skip_selectors );
}

