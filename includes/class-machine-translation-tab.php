<?php

class ETM_Machine_Translation_Tab {

    private $settings;

    public function __construct( $settings ) {

        $this->settings = $settings;

    }

    /*
    * Add new tab to ETM settings
    *
    * Hooked to etm_settings_tabs
    */
    public function add_tab_to_navigation( $tabs ){
        $tab = array(
            'name'  => __( 'Automatic Translation', 'etranslation-multilingual' ),
            'url'   => admin_url( 'admin.php?page=etm_machine_translation' ),
            'page'  => 'etm_machine_translation'
        );

        array_splice( $tabs, 2, 0, array( $tab ) );

        return $tabs;
    }

    /*
    * Add submenu for advanced page tab
    *
    * Hooked to admin_menu
    */
    public function add_submenu_page() {
        add_submenu_page( 'ETMHidden', 'eTranslation Multilingual Automatic Translation', 'ETMHidden', apply_filters( 'etm_settings_capability', 'manage_options' ), 'etm_machine_translation', array( $this, 'machine_translation_page_content' ) );
        add_submenu_page( 'ETMHidden', 'eTranslation Multilingual Test Automatic Translation API', 'ETMHidden', apply_filters( 'etm_settings_capability', 'manage_options' ), 'etm_test_machine_api', array( $this, 'test_api_page_content' ) );
    }

    /**
    * Register setting
    *
    * Hooked to admin_init
    */
    public function register_setting(){
        register_setting( 'etm_machine_translation_settings', 'etm_machine_translation_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
    * Output admin notices after saving settings.
    */
    public function admin_notices(){
        if( isset( $_GET['page'] ) && $_GET['page'] == 'etm_machine_translation' )
            settings_errors();
    }

    /*
    * Sanitize settings
    */
    public function sanitize_settings($mt_settings ){
        if( !empty( $mt_settings['machine-translation'] ) )
            $mt_settings['machine-translation'] = sanitize_text_field( $mt_settings['machine-translation']  );
        else
            $mt_settings['machine-translation'] = 'no';

        if( !empty( $mt_settings['translation-engine'] ) )
            $mt_settings['translation-engine'] = sanitize_text_field( $mt_settings['translation-engine']  );
        else
            $mt_settings['translation-engine'] = 'etranslation';

        if( !empty( $mt_settings['block-crawlers'] ) )
            $mt_settings['block-crawlers'] = sanitize_text_field( $mt_settings['block-crawlers']  );
        else
            $mt_settings['block-crawlers'] = 'no';

        if( !empty( $mt_settings['show-mt-notice'] ) )
            $mt_settings['show-mt-notice'] = sanitize_text_field( $mt_settings['show-mt-notice']  );
        else
            $mt_settings['show-mt-notice'] = 'no';

        return apply_filters( 'etm_machine_translation_sanitize_settings', $mt_settings );
    }

    /*
    * Automatic Translation
    */
    public function machine_translation_page_content(){
        $etm                       = ETM_eTranslation_Multilingual::get_etm_instance();

        $machine_translator_logger = $etm->get_component( 'machine_translator_logger' );
        $machine_translator_logger->maybe_reset_counter_date();

        $machine_translator        = $etm->get_component( 'machine_translator' );

        require_once ETM_PLUGIN_DIR . 'partials/machine-translation-settings-page.php';
    }

    /**
    * Test selected API functionality
    */
    public function test_api_page_content(){
        require_once ETM_PLUGIN_DIR . 'partials/test-api-settings-page.php';
    }

    public function load_engines(){
        include_once ETM_PLUGIN_DIR . 'includes/etranslation/etranslation_utils.php';
        include_once ETM_PLUGIN_DIR . 'includes/etranslation/class-etranslation-service.php';
        include_once ETM_PLUGIN_DIR . 'includes/etranslation/class-etranslation-query.php';
        include_once ETM_PLUGIN_DIR . 'includes/etranslation/class-etranslation-machine-translator.php';
        include_once ETM_PLUGIN_DIR . 'includes/etranslation/functions.php';
    }

    public function get_active_engine( ){
        // This $default is just a fail safe. Should never be used. The real default is set in ETM_Settings->set_options function
        $default = 'ETM_eTranslation_Machine_Translator';

        if( empty( $this->settings['etm_machine_translation_settings']['translation-engine'] ) )
            $value = $default;
        else {
            $existing_engines = apply_filters('etm_automatic_translation_engines_classes', array(
                'etranslation' => 'ETM_eTranslation_Machine_Translator'
            ));

            $value = $existing_engines[$this->settings['etm_machine_translation_settings']['translation-engine']];

            if( !class_exists( $value ) ) {
                $value = $default; //something is wrong if it reaches this
            }
        }

        return new $value( $this->settings );
    }

    public function display_unsupported_languages(){
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $machine_translator = $etm->get_component( 'machine_translator' );
        $etm_languages = $etm->get_component( 'languages' );

        $correct_key = $machine_translator->is_correct_api_key();
        $display_recheck_button = false;


        if ( 'yes' === $this->settings['etm_machine_translation_settings']['machine-translation'] &&
            !empty( $machine_translator->get_api_key() ) &&
            !$machine_translator->check_languages_availability($this->settings['translation-languages']) &&
            $correct_key != null
        ){
            $display_recheck_button = true;
            $language_names = $etm_languages->get_language_names( $this->settings['translation-languages'], 'english_name' );

            ?>
            <tr id="etm_unsupported_languages">
                <th scope=row><?php esc_html_e( 'Unsupported languages', 'etranslation-multilingual' ); ?></th>
                <td>
                    <ul class="etm-unsupported-languages">
                        <?php
                        foreach ( $this->settings['translation-languages'] as $language_code ) {
                            if ( !$machine_translator->check_languages_availability( array( $language_code ) ) ) {
                                echo '<li>' . esc_html( $language_names[$language_code] ) . '</li>';
                            }
                        }
                        ?>
                   </ul>
                  <p class="description">
                       <?php echo wp_kses( __( 'The selected automatic translation engine does not provide support for these languages.<br>You can still manually translate pages in these languages using the Translation Editor.', 'etranslation-multilingual' ), array( 'br' => array() ) ); ?>
                   </p>
                </td>
            </tr>

            <?php
        }

        if ( 'yes' === $this->settings['etm_machine_translation_settings']['machine-translation'] && $display_recheck_button ){
            ?>

            <tr id="etm_recheck_supported_languages">
                <th scope=row></th>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=etm_machine_translation&etm_recheck_supported_languages=1&etm_recheck_supported_languages_nonce=' . wp_create_nonce('etm_recheck_supported_languages') ) ); ?>" class="button-secondary"><?php esc_html_e( 'Recheck supported languages', 'etranslation-multilingual' ); ?></a>
                    <p><i><?php echo wp_kses_post( sprintf( __( '(last checked on %s)', 'etranslation-multilingual' ), esc_html( $machine_translator->get_last_checked_supported_languages() ) ) ); ?> </i></p>
                </td>
            </tr>
            <?php
        }
    }
}
