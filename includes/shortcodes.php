<?php
// add conditional language shortcode
add_shortcode( 'etm_language', 'etm_language_content');

/* ---------------------------------------------------------------------------
 * Shortcode [etm_language language="en_EN"] [/etm_language]
 * --------------------------------------------------------------------------- */


function etm_language_content( $attr, $content = null ){

    global $ETM_LANGUAGE_SHORTCODE;
    if (!isset($ETM_LANGUAGE_SHORTCODE)){
        $ETM_LANGUAGE_SHORTCODE = array();
    }

    $ETM_LANGUAGE_SHORTCODE[] = $content;

    extract(shortcode_atts(array(
        'language' => '',
    ), $attr));

    $current_language = get_locale();

    if( $current_language == $language ){
        $output = do_shortcode($content);
    }else{
        $output = "";
    }

    return $output;
}

add_filter('etm_exclude_words_from_automatic_translation', 'etm_add_shortcode_content_to_excluded_words_from_auto_translation');

function etm_add_shortcode_content_to_excluded_words_from_auto_translation($excluded_words){

    global $ETM_LANGUAGE_SHORTCODE;
    if (!isset($ETM_LANGUAGE_SHORTCODE)){
        $ETM_LANGUAGE_SHORTCODE = array();
    }

    $excluded_words = array_merge($excluded_words, $ETM_LANGUAGE_SHORTCODE);

    return $excluded_words;

}