<?php
/**
 * Add automatic translate exclude selectors.
 */
add_filter( 'etm_register_advanced_settings', 'etm_register_exclude_selectors_automatic_translation', 120 );
function etm_register_exclude_selectors_automatic_translation( $settings_array ){
    $settings_array[] = array(
        'name'          => 'exclude_selectors_from_automatic_translation',
        'type'          => 'list',
        'columns'       => array(
            'selector' => __('Selector', 'etranslation-multilingual' ),
        ),
        'label'         => esc_html__( 'Exclude selectors only from automatic translation', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Do not automatically translate strings that are found in html nodes matching these selectors.<br>Excludes all the children of HTML nodes matching these selectors from being automatically translated.<br>Manual translation of these strings is still possible.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
    );
    return $settings_array;
}


add_filter( 'etm_no_auto_translate_selectors', 'etm_skip_automatic_translation_for_selectors' );
function etm_skip_automatic_translation_for_selectors( $skip_selectors ){
    $option = get_option( 'etm_advanced_settings', true );
    $add_skip_selectors = array( );
    if ( isset( $option['exclude_selectors_from_automatic_translation'] ) && is_array( $option['exclude_selectors_from_automatic_translation']['selector'] ) ) {
        $add_skip_selectors = $option['exclude_selectors_from_automatic_translation']['selector'];
    }

    return array_merge( $skip_selectors, $add_skip_selectors );
}

