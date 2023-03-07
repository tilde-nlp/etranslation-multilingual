<?php

add_filter('etm_register_advanced_settings', 'etm_register_force_slash_in_home_url', 1071);
function etm_register_force_slash_in_home_url($settings_array)
{
    $settings_array[] = array(
        'name' => 'force_slash_at_end_of_links',
        'type' => 'checkbox',
        'label' => esc_html__('Force slash at end of home url:', 'etranslation-multilingual'),
        'description' => wp_kses(__('Ads a slash at the end of the home_url() function', 'etranslation-multilingual'), array('br' => array())),
    );
    return $settings_array;
}
