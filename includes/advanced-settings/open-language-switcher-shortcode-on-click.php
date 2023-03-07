<?php

add_filter( 'etm_register_advanced_settings', 'etm_open_language_switcher_shortcode_on_click', 1350 );
function etm_open_language_switcher_shortcode_on_click( $settings_array ){
    $settings_array[] = array(
        'name'          => 'open_language_switcher_shortcode_on_click',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Open language switcher only on click', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Open the language switcher shortcode by clicking on it instead of hovering.<br> Close it by clicking on it, anywhere else on the screen or by pressing the escape key. This will affect only the shortcode language switcher.', 'etranslation-multilingual' ), array( 'br' => array()) ),
    );
    return $settings_array;
}

function etm_lsclick_enqueue_scriptandstyle() {
    wp_enqueue_script('etm-clickable-ls-js', ETM_PLUGIN_URL . 'assets/js/etm-clickable-ls.js', array('jquery'), ETM_PLUGIN_VERSION, true );

    wp_add_inline_style('etm-language-switcher-style', '.etm_language_switcher_shortcode .etm-language-switcher .etm-ls-shortcode-current-language.etm-ls-clicked{
    visibility: hidden;
}

.etm_language_switcher_shortcode .etm-language-switcher:hover div.etm-ls-shortcode-current-language{
    visibility: visible;
}

.etm_language_switcher_shortcode .etm-language-switcher:hover div.etm-ls-shortcode-language{
    visibility: hidden;
    height: 1px;
}
.etm_language_switcher_shortcode .etm-language-switcher .etm-ls-shortcode-language.etm-ls-clicked,
.etm_language_switcher_shortcode .etm-language-switcher:hover .etm-ls-shortcode-language.etm-ls-clicked{
    visibility:visible;
    height:auto;
    position: absolute;
    left: 0;
    top: 0;
    display: inline-block !important;
}');
}

function etm_open_language_switcher_on_click(){
    $option = get_option( 'etm_advanced_settings', true );

    if(isset($option['open_language_switcher_shortcode_on_click']) && $option['open_language_switcher_shortcode_on_click'] !== 'no'){
        add_action( 'wp_enqueue_scripts', 'etm_lsclick_enqueue_scriptandstyle', 99 );
    }
}

etm_open_language_switcher_on_click();