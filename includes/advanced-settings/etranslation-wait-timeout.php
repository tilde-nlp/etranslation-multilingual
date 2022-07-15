<?php
/**
 * Register advanced configuration option for eTranslation waiting time in seconds before timeout is reached,
 * after which original strings will be returned instead of translations.
 *
 */
add_filter( 'trp_register_advanced_settings', 'trp_register_etranslation_timeout', 1069 );
function trp_register_etranslation_timeout( $settings_array ){

    $settings_array[] = array(
        'name'          => 'etranslation_wait_timeout',
        'default'       => DEFAULT_ETRANSLATION_TIMEOUT,
        'type'          => 'number',
        'label'         => esc_html__( 'eTranslation timeout', 'etranslation-multilingual' ),
        'description'   => __('Max time to wait on eTranslation service (in seconds) to return translations, after which original strings will be shown. Infinite if zero.', 'etranslation-multilingual'),
    );

    return $settings_array;
}