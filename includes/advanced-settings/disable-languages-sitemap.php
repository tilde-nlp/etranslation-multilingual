<?php
//add_filter( 'trp_register_advanced_settings', 'trp_register_disable_languages_sitemap', 1090);
function trp_register_disable_languages_sitemap( $settings_array ){
    $settings_array[] = array(
        'name'          => 'disable_languages_sitemap',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Exclude translated links from sitemap', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Do not include translated links in sitemaps generated by SEO plugins.<br/>Requires SEO Pack Add-on to be installed and activated.', 'etranslation-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ),
    );
    return $settings_array;
}

add_filter('trp_disable_languages_sitemap', 'trp_disable_languages_sitemap_function');
function trp_disable_languages_sitemap_function($allow) {

    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['disable_languages_sitemap'] ) && $option['disable_languages_sitemap'] === 'yes' ) {
        return true;
    }
    return $allow;
}
