<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_do_not_translate_certain_paths', 120 );
function etm_register_do_not_translate_certain_paths( $settings_array ){

    $settings_array[] = array(
        'type'        => 'custom',
        'name'        => 'translateable_content',
        'rows'        => array( 'option' => 'radio', 'paths' => 'textarea' ),
        'label'       => esc_html__( 'Do not translate certain paths', 'etranslation-multilingual' ),
        'description' => wp_kses(  __( 'Choose what paths can be translated. Supports wildcard at the end of the path.<br>For example, to exclude https://example.com/some/path you can either use the rule /some/path/ or /some/*.<br>Enter each rule on it\'s own line. To exclude the home page use {{home}}.', 'etranslation-multilingual' ), array( 'br' => array() )),
    );

	return $settings_array;

}

add_filter( 'etm_advanced_setting_custom_translateable_content', 'etm_output_do_not_translate_certain_paths' );
function etm_output_do_not_translate_certain_paths( $setting ){

    $etm_settings = ( new ETM_Settings() )->get_settings();

    ?>
    <tr id="etm-adv-translate-certain-paths">
        <th scope="row"><?php echo esc_html( $setting['label'] ); ?></th>
        <td>
            <div class="etm-adv-holder">
                <label>
                    <input type='radio' id='$setting_name' name="etm_advanced_settings[<?php echo esc_attr( $setting['name'] ); ?>][option]" value="exclude" <?php echo isset( $etm_settings['etm_advanced_settings'][$setting['name']]['option'] ) && $etm_settings['etm_advanced_settings'][$setting['name']]['option'] == 'exclude' ? 'checked' : ''; ?>>
                    <?php esc_html_e( 'Exclude Paths From Translation', 'etranslation-multilingual' ); ?>
                </label>

                <label>
                    <input type='radio' id='$setting_name' name="etm_advanced_settings[<?php echo esc_attr( $setting['name'] ); ?>][option]" value="include" <?php echo isset( $etm_settings['etm_advanced_settings'][$setting['name']]['option'] ) && $etm_settings['etm_advanced_settings'][$setting['name']]['option'] == 'include' ? 'checked' : ''; ?> >
                    <?php esc_html_e( 'Translate Only Certain Paths', 'etranslation-multilingual' ); ?>
                </label>
            </div>

            <textarea class="etm-adv-big-textarea" name="etm_advanced_settings[<?php echo esc_attr( $setting['name'] ); ?>][paths]"><?php echo isset( $etm_settings['etm_advanced_settings'][$setting['name']]['paths'] ) ? esc_textarea( $etm_settings['etm_advanced_settings'][$setting['name']]['paths'] ) : ''; ?></textarea>

            <p class="description"><?php echo wp_kses_post( $setting['description'] ); ?></p>
        </td>
    </tr>


    <?php
    return;
}

function etm_test_current_slug( &$current_slug, &$array_slugs ) {
    $current_slug = trim($current_slug, "/");

    // Explode get params
    $current_slug = explode( '?', $current_slug );

    // If get params then store in $current_slug the part thats important to us
    if( isset( $current_slug[1] ) ){
        $current_get  = $current_slug[1];
        $current_slug = $current_slug[0];
    } else {
        $current_slug = $current_slug[0];
    }

    // Test if current slug should be home. If not then split the slug on "/" and save the individual strings in $array_slugs
    if( empty( $current_slug ) || $current_slug == '/' || $current_slug == '' ){
        $array_slugs[0] = "{{home}}";
        $current_slug = "{{home}}";
    }
    else {
        $array_slugs = explode( "/", $current_slug );
    }
}

function etm_return_exclude_include_url($paths, $current_slug, $array_slugs) {
    // $paths contains all the paths set in the advance tab
    foreach( $paths as $path ) {

        if ( !empty( $path ) ) {
            $path = trim( $path, "/" );

            // If $current_path is exactly $path and $path doesn't contain "/*"
            if ( ( untrailingslashit( $current_slug ) == untrailingslashit( $path ) || strcmp( $current_slug, $path ) == 0 ) && strpos( $path, '*' ) == false )
                return true;
            // Elseif $current path contains "/*"
            elseif ( strpos( $path, '*' ) !== false ) {
                $path = str_replace( '/*', '', $path );
                // $array_paths contains each part of $path split on "/"
                $array_paths = explode( "/", $path );
                // If $current_slug has more values than $path
                if ( count( $array_slugs ) > count( $array_paths ) ) {
                    $compare_slugs = true;
                    // Comparing each value from $array_paths and $array_slugs in the same order
                    foreach ( $array_paths as $key => $array_path ) {
                        // Testing if the values are different
                        if ( strcmp( $array_slugs[ $key ], $array_path ) !== 0 )
                            $compare_slugs = false;
                    }
                    // If all the values are identical
                    if ( $compare_slugs === true )
                        return true;
                }
            }
        }
    }
}

// Prevent eTranslation Multilingual from loading on excluded pages
add_action( 'etm_allow_etm_to_run', 'etm_exclude_include_paths_to_run_on', 2 );
function etm_exclude_include_paths_to_run_on(){

    if( is_admin() )
        return true;

    if( isset( $_GET['etm-edit-translation'] ) && ( $_GET['etm-edit-translation'] == 'true' || $_GET['etm-edit-translation'] == 'preview' ) )
        return true;

    if( isset( $_GET['etm-string-translation'] ) && $_GET['etm-string-translation'] == 'true' )
        return true;

    $settings          = get_option( 'etm_settings', false );
    $advanced_settings = get_option( 'etm_advanced_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return true;

    $etm           = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');
    $current_lang  = $url_converter->get_lang_from_url_string( $url_converter->cur_page_url() );

    if( empty( $current_lang ) )
        $current_lang = $settings['default-language'];

    if ( $url_converter->is_sitemap_path() )
        return true;

    // Skip checks if this is not the default language
    if( !empty( $current_lang ) && $settings['default-language'] != $current_lang )
        return true;

    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );
    $current_slug = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';

    $replace = '/';

    if( isset( $settings['add-subdirectory-to-default-language'] ) && $settings['add-subdirectory-to-default-language'] == 'yes' ) {
	    $replace .= $settings['url-slugs'][ $current_lang ];
	    $current_slug = str_replace( $replace, '', $current_slug );
    }


    // $array_slugs contains each part of $curent_slug split on "/"
    $array_slugs = array();
    etm_test_current_slug($current_slug, $array_slugs );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return false;

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return true;

        return false;

    }

	return true;

}

add_filter( 'etm_allow_language_redirect', 'etm_exclude_include_do_not_redirect_on_excluded_pages', 20, 3 );
function etm_exclude_include_do_not_redirect_on_excluded_pages( $redirect, $language, $url ){

    if( isset( $_GET['etm-edit-translation'] ) && ( $_GET['etm-edit-translation'] == 'true' || $_GET['etm-edit-translation'] == 'preview' ) )
        return $redirect;

    if( isset( $_GET['etm-string-translation'] ) && $_GET['etm-string-translation'] == 'true' )
        return $redirect;

    $settings          = get_option( 'etm_settings', false );
    $advanced_settings = get_option( 'etm_advanced_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return $redirect;

    if( empty( $language ) || $language != $settings['default-language'] )
        return $redirect;

    $replace = trailingslashit( home_url() );

    $current_slug = str_replace( $replace, '', trailingslashit( $url ) );

    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // $array_slugs contains each part of $curent_slug split on "/"
    $array_slugs = array();
    etm_test_current_slug($current_slug, $array_slugs );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return false;

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return $redirect;

        return false;

    }

    return $redirect;

}

/**
 *  The function verifies if we are on an excluded path and automatically redirects to the default language in that case.
 * The function '$url_converter->get_url_for_language( $settings['default-language'], null, '' )' is needed in the case we are on a page with a different
 * language code then the default and the path is the one excluded, so we need to get the correct url in the default language.
 *
 * Redirects to the excluded page in the default language.
 */
add_action( 'template_redirect', 'etm_exclude_include_redirect_to_default_language', 1 );
function etm_exclude_include_redirect_to_default_language(){

    if( isset( $_GET['etm-edit-translation'] ) && ( $_GET['etm-edit-translation'] == 'true' || $_GET['etm-edit-translation'] == 'preview' ) )
        return;

    if( isset( $_GET['etm-string-translation'] ) && $_GET['etm-string-translation'] == 'true' )
        return;

    if( is_admin() )
        return;

    $settings          = get_option( 'etm_settings', false );
    $advanced_settings = get_option( 'etm_advanced_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return;

    global $ETM_LANGUAGE;
    $etm           = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');

    $current_original_url = $url_converter->get_url_for_language( $settings['default-language'], null, '' );

    // Attempt to redirect on default language only if the current URL contains the language
    if( !isset( $ETM_LANGUAGE ) || $settings['default-language'] == $ETM_LANGUAGE ){

        if( $url_converter->get_lang_from_url_string( $current_original_url ) === null )
            return;

    }

    $absolute_home = $url_converter->get_abs_home();

    // Take into account the subdirectory for default language option
    if ( isset( $settings['add-subdirectory-to-default-language'] ) && $settings['add-subdirectory-to-default-language'] == 'yes' )
        $absolute_home = trailingslashit( $absolute_home ) . $settings['url-slugs'][$settings['default-language']];

    $current_slug = str_replace( $absolute_home, '', untrailingslashit( $current_original_url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // Remove language from this URL if present
    $current_original_url = str_replace( '/' . $settings['url-slugs'][$settings['default-language']], '', $current_original_url );

    // $array_slugs contains each part of $curent_slug split on "/"
    $array_slugs = array();
    etm_test_current_slug($current_slug, $array_slugs );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            if( $url_converter->cur_page_url() != $current_original_url ){
                $status = apply_filters( 'etm_redirect_status', 301, 'redirect_to_default_language_because_link_is_excluded_from_translation' );
                wp_redirect( $current_original_url, $status );
                exit;
            }


    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return;

        if( $url_converter->cur_page_url() != $current_original_url ){
            $status = apply_filters( 'etm_redirect_status', 301, 'redirect_to_default_language_because_link_is_excluded_from_translation' );
            wp_redirect( $current_original_url, $status );
            exit;
        }

    }

}

// only force custom links in paths that are translatable
add_filter( 'etm_force_custom_links', 'etm_exclude_include_filter_custom_links', 10, 4);
function etm_exclude_include_filter_custom_links( $new_url, $url, $ETM_LANGUAGE, $a_href ){

    if( isset( $_GET['etm-edit-translation'] ) && ( $_GET['etm-edit-translation'] == 'true' || $_GET['etm-edit-translation'] == 'preview' ) )
        return $new_url;

    if( isset( $_GET['etm-string-translation'] ) && $_GET['etm-string-translation'] == 'true' )
        return $new_url;

    $advanced_settings = get_option( 'etm_advanced_settings', false );
    $settings          = get_option( 'etm_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return $new_url;

    global $ETM_LANGUAGE;
    $etm           = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');

    if( !isset( $ETM_LANGUAGE ) || $settings['default-language'] == $ETM_LANGUAGE )
        return;

    $current_original_url = $url_converter->get_url_for_language( $settings['default-language'], $new_url, '' );

    // Remove language from this URL if present
    $current_original_url = str_replace( '/' . $settings['url-slugs'][$settings['default-language']], '', $current_original_url );

    $absolute_home        = $url_converter->get_abs_home();

    $current_slug = str_replace( $absolute_home, '', untrailingslashit( $current_original_url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // $array_slugs contains each part of $curent_slug split on "/"
    $array_slugs = array();
    etm_test_current_slug($current_slug, $array_slugs );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return $current_original_url;

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return $new_url;

        return $current_original_url;

    }

    return $new_url;

}

add_action( 'init', 'etm_exclude_include_add_sitemap_filter' );
function etm_exclude_include_add_sitemap_filter(){
    if (class_exists('ETM_IN_Seo_Pack'))
        add_filter( 'etm_xml_sitemap_output_for_url', 'etm_exclude_include_filter_sitemap_links', 10, 6 );
}

function etm_exclude_include_filter_sitemap_links( $new_output, $output, $settings, $alternate, $all_lang_urls, $url ){

    $advanced_settings = get_option( 'etm_advanced_settings', false );
    $settings          = get_option( 'etm_settings', false );

    if( empty( $advanced_settings ) || !isset( $advanced_settings['translateable_content'] ) || !isset( $advanced_settings['translateable_content']['option'] ) || empty( $advanced_settings['translateable_content']['paths'] ) )
        return $new_output;

    global $ETM_LANGUAGE;
    $etm           = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');

    if( empty( $url['loc'] ) )
        return $new_output;

    $current_original_url = $url_converter->get_url_for_language( $settings['default-language'], $url['loc'], '' );
    $absolute_home        = $url_converter->get_abs_home();

    $current_slug = str_replace( $absolute_home, '', untrailingslashit( $current_original_url ) );
    $paths        = explode("\n", str_replace("\r", "", $advanced_settings['translateable_content']['paths'] ) );

    // $array_slugs contains each part of $curent_slug split on "/"
    $array_slugs = array();
    etm_test_current_slug($current_slug, $array_slugs );

    if( $advanced_settings['translateable_content']['option'] == 'exclude' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return $output;

    } else if( $advanced_settings['translateable_content']['option'] == 'include' ){

        if ( etm_return_exclude_include_url($paths, $current_slug, $array_slugs) )
            return $new_output;

        return $output;

    }

    return $new_output;

}