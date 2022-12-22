<?php
add_filter( 'etm_register_advanced_settings', 'etm_register_enable_hreflang_xdefault', 1100 );
function etm_register_enable_hreflang_xdefault( $settings_array ){
    $settings_array[] = array(
        'name'          => 'enable_hreflang_xdefault',
        'type'          => 'select',
        'default'       => 'disabled',
        'label'         => esc_html__( 'Enable the hreflang x-default tag for language:', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Enables the hreflang="x-default" for an entire language. See documentation for more details.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
        'options'       => etm_get_lang_for_xdefault(),
    );
    return $settings_array;
}

function etm_get_lang_for_xdefault(){
    $published_lang_labels = etm_get_languages();
    return array_merge(['disabled' => 'Disabled'], $published_lang_labels);
}
