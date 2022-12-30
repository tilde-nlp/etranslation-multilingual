<?php

/**
 * Class ETM_MT_Notice
 *
 * Generates MT notice informing user about machine translated content.
 */
class ETM_MT_Notice {

    protected $settings;
    protected $url_converter;

    public function __construct( $settings , $url_converter) {
        $this->settings = $settings;
        $this->url_converter = $url_converter;
    }

    public function enqueue_mt_notice_scripts() {
        $show = $this->settings['etm_machine_translation_settings']['show-mt-notice'];
        $mt_enabled = $this->settings['etm_machine_translation_settings']['machine-translation'];
        if ($show == 'yes' && $mt_enabled == 'yes') {
            global $ETM_LANGUAGE;
            $default = $this->settings['default-language'];
            if ($ETM_LANGUAGE != $default) {                
                $etm = ETM_eTranslation_Multilingual::get_etm_instance();
                $machine_translator = $etm->get_component('machine_translator');
                $mt_available = $machine_translator->check_languages_availability(array($default, $ETM_LANGUAGE));
                if ($mt_available) {
                    $original_url = $this->url_converter->get_url_for_language($default);
                    $img_url = ETM_PLUGIN_URL . "assets/images/x.svg";

                    wp_enqueue_style( 'mt-notice-style', ETM_PLUGIN_URL . 'assets/css/mt-notice.css', array(), ETM_PLUGIN_VERSION );
                    wp_enqueue_script( 'mt-notice-script', ETM_PLUGIN_URL . 'assets/js/mt-notice.js', array('jquery'), ETM_PLUGIN_VERSION );
                    wp_localize_script( 'mt-notice-script', 'mt_notice_params', array('original_url' => $original_url, 'img_url' => $img_url) );
                }
            }
        }
    }
}