<?php

add_filter( 'trp_machine_translation_engines', 'trp_etranslation_add_engine', 5 );
function trp_etranslation_add_engine( $engines ){
    $engines[] = array( 'value' => 'etranslation', 'label' => __( 'eTranslation', 'translatepress-multilingual' ) );

    return $engines;
}

add_action( 'trp_machine_translation_extra_settings_middle', 'trp_etranslation_add_settings' );
function trp_etranslation_add_settings( $mt_settings ){
    $trp                = TRP_Translate_Press::get_trp_instance();
    $machine_translator = $trp->get_component( 'machine_translator' );

    $translation_engine = isset( $mt_settings['translation-engine'] ) ? $mt_settings['translation-engine'] : '';

    // Check for API errors only if $translation_engine is eTranslation.
    if ( 'etranslation' === $translation_engine ) {
        $api_check = $machine_translator->check_api_key_validity();
    }

    // Check for errors.
    $error_message = '';
    $show_errors   = false;
    if ( isset( $api_check ) && true === $api_check['error'] ) {
        $error_message = $api_check['message'];
        $show_errors    = true;
    }

    $text_input_classes = array(
        'trp-text-input',
    );
    if ( $show_errors && 'etranslation' === $translation_engine ) {
        $text_input_classes[] = 'trp-text-input-error';
    }
    ?>
    <tr>
        <th scope="row"><?php esc_html_e( 'eTranslation Application Name', 'translatepress-multilingual' ); ?> </th>
        <td class="et-credentials">
            <?php
            // Display an error message above the input.
            if ( $show_errors && 'etranslation' === $translation_engine ) {
                ?>
                <p class="trp-error-inline">
                    <?php echo wp_kses_post( $error_message ); ?>
                </p>
                <?php
            }
            ?>
            <input type="text" class="<?php echo esc_html( implode( ' ', $text_input_classes ) ); ?>" name="etm_machine_translation_settings[etranslation-app-name]" value="<?php if( !empty( $mt_settings['etranslation-app-name'] ) ) echo esc_attr( $mt_settings['etranslation-app-name']);?>"/>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( 'eTranslation Password', 'translatepress-multilingual' ); ?> </th>
        <td class="et-credentials">
            <input type="password" class="<?php echo esc_html( implode( ' ', $text_input_classes ) ); ?>" name="etm_machine_translation_settings[etranslation-pwd]" value="<?php if( !empty( $mt_settings['etranslation-pwd'] ) ) echo esc_attr( $mt_settings['etranslation-pwd']);?>"/>
            <?php
            // Only show errors if eTranslation is active.
            if ( $machine_translator->is_available() && 'etranslation' === $translation_engine && function_exists( 'trp_output_svg' ) ) {
                $machine_translator->automatic_translation_svg_output( $show_errors );
            }
            ?>
            <p class="description">
                To create a new eTranslation account, please write to eTranslation Helpdesk: <a href="mailto:help@cefat-tools-services.eu">help@cefat-tools-services.eu</a>
            </p>
        </td>
    </tr>

    <?php
}

add_filter( 'trp_machine_translation_sanitize_settings', 'trp_etranslation_sanitize_settings' );
function trp_etranslation_sanitize_settings( $mt_settings ){
    if( !empty( $mt_settings['etranslation-app-name'] ) )
        $mt_settings['etranslation-app-name'] = sanitize_text_field( $mt_settings['etranslation-app-name']  );

    return $mt_settings;
}

function trp_etranslation_response_codes( $code ) {
    $is_error       = false;
    $code           = intval( $code );
    $return_message = '';

    if ( preg_match( '/4\d\d/', $code ) ) {
        $is_error = true;
        $return_message = esc_html__( 'There was an error with your eTranslation credentials.', 'translatepress-multilingual' );
    } elseif ( preg_match( '/5\d\d/', $code ) ) {
        $is_error = true;
        $return_message = esc_html__( 'There was an error on the server processing your eTranslation credentials.', 'translatepress-multilingual' );
    }

    return array(
        'message' => $return_message,
        'error'   => $is_error,
    );
}

add_filter( 'pre_update_option_etm_machine_translation_settings', function( $new_value, $old_value ) {
    $key = 'etranslation-pwd';
    if ($new_value && $new_value[$key] && (!$old_value || $old_value[$key] != $new_value[$key])) {        
        $new_value[$key] = TRP_eTranslation_Utils::encrypt_password($new_value[$key]);
    }
    return $new_value; 
 }, 10, 2);