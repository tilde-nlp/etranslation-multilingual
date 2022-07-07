<?php

/**
 * Class TRP_MT_Notice
 *
 * Generates MT notice informing user about machine translated content.
 */
class TRP_MT_Notice {

    protected $settings;
    protected $url_converter;

    public function __construct( $settings , $url_converter) {
        $this->settings = $settings;
        $this->url_converter = $url_converter;
    }

    public function enqueue_mt_notice_scripts() {
        wp_enqueue_script( 'mt-notice-script', TRP_PLUGIN_URL . 'assets/js/mt-notice.js', array(), TRP_PLUGIN_VERSION );
        wp_enqueue_style( 'mt-notice-style', TRP_PLUGIN_URL . 'assets/css/mt-notice.css', array(), TRP_PLUGIN_VERSION );
    }

    public function add_mt_notice() {
        $show = $this->settings['trp_machine_translation_settings']['show-mt-notice'];
        $mt_enabled = $this->settings['trp_machine_translation_settings']['machine-translation'];
        if ($show == 'yes' && $mt_enabled == 'yes') {
            global $TRP_LANGUAGE;
            $default = $this->settings['default-language'];
            if ($TRP_LANGUAGE != $default) {                
                $trp = TRP_Translate_Press::get_trp_instance();
                $machine_translator = $trp->get_component('machine_translator');
                $mt_available = $machine_translator->check_languages_availability(array($default, $TRP_LANGUAGE));
                if ($mt_available) {
                    $original_url = $this->url_converter->get_url_for_language($default);
                    ?>
                    <div class="mt-notice-container">
                        <div class="translation-notice">
                            This page has been machine-translated. <a href="<?php echo $original_url ?>" class="mt-notice-link">Show original</a>
                        </div>
                        <div id="mt-notice-hide" onclick="hideMtNotice()">
                            <?php echo "<img src='" . TRP_PLUGIN_URL . "assets/images/x.svg' />" ?>
                        </div>
                    </div>
                    <div class="mt-notice-space"></div>
                    <?php
                }
            }
        }
    }
}