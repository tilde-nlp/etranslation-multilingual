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
                $original_url = $this->url_converter->get_url_for_language($default);
                ?>
                <div class="mt-notice-container">
                    <div class="translation-notice">
                        This page has been machine-translated. <a href="<?php echo $original_url ?>" class="mt-notice-link">Show original</a>
                    </div>
                    <div id="mt-notice-hide" onclick="hideMtNotice()">
                        <svg xmlns="http://www.w3.org/2000/svg" height="7pt" viewBox="0 0 329.26933 329" width="7pt"><path d="m194.800781 164.769531 128.210938-128.214843c8.34375-8.339844 8.34375-21.824219 0-30.164063-8.339844-8.339844-21.824219-8.339844-30.164063 0l-128.214844 128.214844-128.210937-128.214844c-8.34375-8.339844-21.824219-8.339844-30.164063 0-8.34375 8.339844-8.34375 21.824219 0 30.164063l128.210938 128.214843-128.210938 128.214844c-8.34375 8.339844-8.34375 21.824219 0 30.164063 4.15625 4.160156 9.621094 6.25 15.082032 6.25 5.460937 0 10.921875-2.089844 15.082031-6.25l128.210937-128.214844 128.214844 128.214844c4.160156 4.160156 9.621094 6.25 15.082032 6.25 5.460937 0 10.921874-2.089844 15.082031-6.25 8.34375-8.339844 8.34375-21.824219 0-30.164063zm0 0"></path></svg>
                    </div>
                </div>
                <div class="mt-notice-space"></div>
                <?php
            }
        }
    }
}