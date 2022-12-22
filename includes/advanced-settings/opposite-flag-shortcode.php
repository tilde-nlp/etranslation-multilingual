<?php

add_filter( 'etm_register_advanced_settings', 'etm_show_opposite_flag_language_switcher_shortcode', 1250 );
function etm_show_opposite_flag_language_switcher_shortcode( $settings_array ){
    $settings_array[] = array(
        'name'          => 'show_opposite_flag_language_switcher_shortcode',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Show opposite language in the language switcher', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Transforms the language switcher into a button showing the other available language, not the current one.<br> Only works when there are exactly two languages, the default one and a translation one.<br>This will affect the shortcode language switcher and floating language switcher as well.<br> To achieve this in menu language switcher go to Appearance->Menus->Language Switcher and select Opposite Language.', 'etranslation-multilingual' ), array( 'br' => array()) ),
    );
    return $settings_array;
}

function etm_opposite_ls_current_language( $current_language, $published_languages, $ETM_LANGUAGE, $settings ){
    if ( count ( $published_languages ) == 2 ) {
        foreach ($published_languages as $code => $name) {
            if ($code != $ETM_LANGUAGE) {
                $current_language['code'] = $code;
                $current_language['name'] = $name;
                break;
            }
        }
    }
    return $current_language;
}

function etm_opposite_ls_other_language( $other_language, $published_languages, $ETM_LANGUAGE, $settings ){
    if ( count ( $published_languages ) == 2 ) {
        $other_language = array();
        foreach ($published_languages as $code => $name) {
            if ($code != $ETM_LANGUAGE) {
                $other_language[$code] = $name;
                break;
            }
        }
    }
    return $other_language;
}

function etm_opposite_ls_hide_disabled_language($return, $current_language, $current_language_preference, $settings){
    if ( count( $settings['publish-languages'] ) == 2 ){
        return false;
    }
    return $return;
}

function etm_enqueue_language_switcher_shortcode_scripts(){
    $etm                 = ETM_eTranslation_Multilingual::get_etm_instance();
    $etm_languages       = $etm->get_component( 'languages' );
    $etm_settings        = $etm->get_component( 'settings' );
    $published_languages = $etm_languages->get_language_names( $etm_settings->get_settings()['publish-languages'] );
    if(count ( $published_languages ) == 2 ) {
        wp_add_inline_style( 'etm-language-switcher-style', '.etm-language-switcher > div {
    padding: 3px 5px 3px 5px;
    background-image: none;
    text-align: center;}' );
    }
}

function etm_opposite_ls_floating_current_language($current_language, $published_languages, $ETM_LANGUAGE, $settings){
    if ( count ( $published_languages ) == 2 ) {
        foreach ($published_languages as $code => $name) {
            if ($code != $ETM_LANGUAGE) {
                $current_language['code'] = $code;
                $current_language['name'] = $name;
                break;
            }
        }
    }
    return $current_language;
}

function etm_opposite_ls_floating_other_language( $other_language, $published_languages, $ETM_LANGUAGE, $settings ){
    if ( count ( $published_languages ) == 2 ) {
        $other_language = array();
        foreach ($published_languages as $code => $name) {
            if ($code != $ETM_LANGUAGE) {
                $other_language[$code] = $name;
                break;
            }
        }
    }
    return $other_language;
}

function etm_opposite_ls_floating_hide_disabled_language($return, $current_language, $settings){
    if ( count( $settings['publish-languages'] ) == 2 ){
        return false;
    }
    return $return;
}

function etm_show_opposite_flag_settings(){
    $option = get_option( 'etm_advanced_settings', true );

     if(isset($option['show_opposite_flag_language_switcher_shortcode']) && $option['show_opposite_flag_language_switcher_shortcode'] !== 'no'){
         add_filter( 'etm_ls_shortcode_current_language', 'etm_opposite_ls_current_language', 10, 4 );
         add_filter( 'etm_ls_shortcode_other_languages', 'etm_opposite_ls_other_language', 10, 4 );
         add_filter( 'etm_ls_shortcode_show_disabled_language', 'etm_opposite_ls_hide_disabled_language', 10, 4 );
         add_action( 'wp_enqueue_scripts', 'etm_enqueue_language_switcher_shortcode_scripts', 20 );
         add_action('etm_ls_floating_current_language', 'etm_opposite_ls_floating_current_language', 10, 4);
         add_action('etm_ls_floating_other_languages', 'etm_opposite_ls_floating_other_language', 10, 4);
         add_action('etm_ls_floater_show_disabled_language', 'etm_opposite_ls_floating_hide_disabled_language', 10, 3 );
     }
 }

etm_show_opposite_flag_settings();