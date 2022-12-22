<?php

add_filter( 'etm_register_advanced_settings', 'etm_translation_for_gettext_strings', 523 );
function etm_translation_for_gettext_strings( $settings_array ){
    $settings_array[] = array(
        'name'          => 'disable_translation_for_gettext_strings',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Disable translation for gettext strings', 'etranslation-multilingual' ),
        'description'   => wp_kses( __( 'Gettext Strings are strings outputted by themes and plugins. <br> Translating these types of strings through eTranslation Multilingual can be unnecessary if they are already translated using the .po/.mo translation file system.<br>Enabling this option can improve the page load performance of your site in certain cases. The disadvantage is that you can no longer edit gettext translations using eTranslation Multilingual, nor benefit from automatic translation on these strings.', 'etranslation-multilingual' ), array( 'br' => array()) ),
    );
    return $settings_array;
}

add_action( 'etm_before_running_hooks', 'etm_remove_hooks_to_disable_gettext_translation', 10, 1);
function etm_remove_hooks_to_disable_gettext_translation( $etm_loader ){
    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['disable_translation_for_gettext_strings'] ) && $option['disable_translation_for_gettext_strings'] === 'yes' ) {
        $etm             = ETM_eTranslation_Multilingual::get_etm_instance();
        $gettext_manager = $etm->get_component( 'gettext_manager' );
        $etm_loader->remove_hook( 'init', 'create_gettext_translated_global', $gettext_manager );
        $etm_loader->remove_hook( 'shutdown', 'machine_translate_gettext', $gettext_manager );
    }
}

add_filter( 'etm_skip_gettext_querying', 'etm_skip_gettext_querying', 10, 4 );
function etm_skip_gettext_querying( $skip, $translation, $text, $domain ){
    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['disable_translation_for_gettext_strings'] ) && $option['disable_translation_for_gettext_strings'] === 'yes' ) {
        return true;
    }
    return $skip;
}



add_action( 'etm_editor_notices', 'display_message_for_disable_gettext_in_editor', 10, 1 );
function display_message_for_disable_gettext_in_editor( $etm_editor_notices ) {
    $option = get_option( 'etm_advanced_settings', true );
    if ( isset( $option['disable_translation_for_gettext_strings'] ) && $option['disable_translation_for_gettext_strings'] === 'yes' ) {

        $url = add_query_arg( array(
            'page'                      => 'etm_advanced_page#debug_options',
        ), site_url('wp-admin/admin.php') );

        // maybe change notice color to blue #28B1FF
        $html = "<div class='etm-notice etm-notice-warning'>";
        $html .= '<p><strong>' . esc_html__( 'Gettext Strings translation is disabled', 'etranslation-multilingual' ) . '</strong></p>';

        $html .= '<p>' . esc_html__( 'To enable it go to ', 'etranslation-multilingual' ) . '<a class="etm-link-primary" target="_blank" href="' . esc_url( $url ) . '">' . esc_html__( 'eTranslation Multilingual->Advanced Settings->Debug->Disable translation for gettext strings', 'etranslation-multilingual' ) . '</a>' . esc_html__(' and uncheck the Checkbox.', 'etranslation-multilingual') .'</p>';
        $html .= '</div>';

        $etm_editor_notices = $html;
    }

    return $etm_editor_notices;
}