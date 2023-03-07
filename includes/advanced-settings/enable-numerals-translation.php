<?php

add_filter('etm_register_advanced_settings', 'etm_register_enable_numerals_translation', 1081);
function etm_register_enable_numerals_translation($settings_array)
{
    $settings_array[] = array(
        'name' => 'enable_numerals_translation',
        'type' => 'checkbox',
        'label' => esc_html__('Translate numbers and numerals', 'etranslation-multilingual'),
        'description' => esc_html__('Enable translation of numbers ( e.g. phone numbers)', 'etranslation-multilingual'),
    );
    return $settings_array;
}
