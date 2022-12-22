<?php

/**
 * Class ETM_Settings
 *
 * In charge of settings page and settings option.
 */
class ETM_Settings{

    protected $settings;
    protected $etm_query;
    protected $url_converter;
    protected $etm_languages;
    protected $machine_translator;

    /**
     * Return array of customization options for language switchers.
     *
     * Customization options include whether to add flags, full names or short names.
     * Used for all types of language switchers.
     *
     * @return array            Array with customization options.
     */
    public function get_language_switcher_options(){
        $ls_options = apply_filters( 'etm_language_switcher_output', array(
            'full-names'         => array( 'full_names'  => true, 'short_names'  => false, 'flags' => false, 'no_html' => false, 'label' => __( 'Full Language Names', 'etranslation-multilingual' ) ),
            'short-names'        => array( 'full_names'  => false, 'short_names'  => true, 'flags' => false, 'no_html' => false, 'label' => __( 'Short Language Names', 'etranslation-multilingual' ) ),
            'flags-full-names'   => array( 'full_names'  => true, 'short_names'  => false, 'flags' => true, 'no_html' => false, 'label' => __( 'Flags with Full Language Names', 'etranslation-multilingual' ) ),
            'flags-short-names'  => array( 'full_names'  => false, 'short_names'  => true, 'flags' => true, 'no_html' => false, 'label' => __( 'Flags with Short Language Names', 'etranslation-multilingual' ) ),
            'only-flags'         => array( 'full_names'  => false, 'short_names'  => false, 'flags' => true, 'no_html' => false, 'label' => __( 'Only Flags', 'etranslation-multilingual' ) ),
	        'full-names-no-html' => array( 'full_names'  => false, 'short_names'  => false, 'flags' => false, 'no_html' => true, 'label' => __( 'Full Language Names No HTML', 'etranslation-multilingual' ) )
        ) );
        return $ls_options;
    }

    /**
     * Echo html for selecting language from all available language in settings.
     *
     * @param string $ls_type       shortcode_options | menu_options | floater_options
     * @param string $ls_setting    The selected language switcher customization setting (get_language_switcher_options())
     */
    public function output_language_switcher_select( $ls_type, $ls_setting ){
        $ls_options = $this->get_language_switcher_options();
        // Use the full names no HTML option only for the menu - for extra compatibility with certain themes and menus
	    if ($ls_type !== 'menu-options'){
	    	unset($ls_options['full-names-no-html']);
	    }
        $output = '<select id="' . esc_attr( $ls_type ) . '" name="etm_settings[' . esc_attr( $ls_type ) .']" class="etm-select etm-ls-select-option">';
        foreach( $ls_options as $key => $ls_option ){
            $selected = ( $ls_setting == $key ) ? 'selected' : '';
            $output .= '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . ' >' . esc_html( $ls_option['label'] ). '</option>';

        }
        $output .= '</select>';

        echo $output;/* phpcs:ignore */ /* escaped above */
    }

    /**
     * Echo html for selecting language selector position.
     *
     * @param string $ls_position    The selected language switcher position
     */
    public function output_language_switcher_floater_possition( $ls_position ){
        $ls_options = array(
            'bottom-right'  => array( 'label' => __( 'Bottom Right', 'etranslation-multilingual' ) ),
            'bottom-left'   => array( 'label' => __( 'Bottom Left', 'etranslation-multilingual' ) ),
            'top-right'     => array( 'label' => __( 'Top Right', 'etranslation-multilingual' ) ),
            'top-left'      => array( 'label' => __( 'Top Left', 'etranslation-multilingual' ) ),

        );

        $output = '<select id="floater-position" name="etm_settings[floater-position]" class="etm-select etm-ls-select-option">';
        foreach( $ls_options as $key => $ls_option ){
            $selected = ( $ls_position == $key ) ? 'selected' : '';
            $output .= '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . ' >' . esc_html( $ls_option['label'] ). '</option>';
        }
        $output .= '</select>';

        echo $output; /* phpcs:ignore */ /* escaped above */
    }

	/**
	 * Echo html for selecting language selector color.
	 *
	 * @param string $ls_color    The selected language switcher color.
	 */
	public function output_language_switcher_floater_color( $ls_color ){
		$ls_options = array(
			'default'  => array( 'label' => __( 'Default', 'etranslation-multilingual' ) ),
			'dark'  => array( 'label' => __( 'Dark', 'etranslation-multilingual' ) ),
			'light'   => array( 'label' => __( 'Light', 'etranslation-multilingual' ) ),
		);

		$output = '<select id="floater-color" name="etm_settings[floater-color]" class="etm-select etm-ls-select-option">';
		foreach( $ls_options as $key => $ls_option ){
			$selected = ( $ls_color == $key ) ? 'selected' : '';
			$output .= '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . ' >' . esc_html( $ls_option['label'] ). '</option>';
		}
		$output .= '</select>';

		echo $output; /* phpcs:ignore */ /* escaped above */
	}

    /**
     * Returns settings_option.
     *
     * @return array        Settings option.
     */
    public function get_settings(){
        if ( $this->settings == null ){
            $this->set_options();
        }
        return $this->settings;
    }

    /**
     * Returns the value of an individual setting or the default provided.
     *
     * @param string $name
     * @param default mixed
     *
     * @return mixed Setting Value
     */
    public function get_setting($name, $default = null){
        if( array_key_exists($name, $this->settings ) ){
            return maybe_unserialize($this->settings[$name]);
        } else {
            return $default;
        }
    }

    /**
     * Register Settings subpage for eTranslation Multilingual
     */
    public function register_menu_page(){
        add_options_page( 'eTranslation Multilingual', 'eTranslation Multilingual', apply_filters( 'etm_settings_capability', 'manage_options' ), 'etranslation-multilingual', array( $this, 'settings_page_content' ) );
    }

    /**
     * Settings page content.
     */
    public function settings_page_content(){
	    if ( ! $this->etm_languages ){
            $etm                 = ETM_eTranslation_Multilingual::get_etm_instance();
            $this->etm_languages = $etm->get_component( 'languages' );
        }

        $languages = $this->etm_languages->get_languages( 'english_name' );

        require_once ETM_PLUGIN_DIR . 'partials/main-settings-page.php';
    }

    /**
     * Register settings option.
     */
    public function register_setting(){
        register_setting( 'etm_settings', 'etm_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
     * Sanitizes settings option after save.
     *
     * Updates menu items for languages to be used in Menus.
     *
     * @param array $settings       Raw settings option.
     * @return array                Sanitized option page.
     */
    public function sanitize_settings( $settings ){
        if ( ! $this->etm_query ) {
            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $this->etm_query = $etm->get_component( 'query' );
        }
        if ( ! $this->etm_languages ){
            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $this->etm_languages = $etm->get_component( 'languages' );
        }
        if ( !isset ( $settings['default-language'] ) ) {
            $settings['default-language'] = 'en_GB';
        }
        if ( !isset ( $settings['translation-languages'] ) ){
            $settings['translation-languages'] = array();
        }
        if ( !isset ( $settings['publish-languages'] ) ){
            $settings['publish-languages'] = array();
        }

        $settings['translation-languages'] = array_filter( array_unique( $settings['translation-languages'] ) );
        $settings['publish-languages'] = array_filter( array_unique( $settings['publish-languages'] ) );

        if ( ! in_array( $settings['default-language'], $settings['translation-languages'] ) ){
            array_unshift( $settings['translation-languages'], $settings['default-language'] );
        }
        if ( ! in_array( $settings['default-language'], $settings['publish-languages'] ) ){
            array_unshift( $settings['publish-languages'], $settings['default-language'] );
        }

        // check if submitted language codes are valid. Default language is included here too
        $check_language_codes = array_unique( array_merge($settings['translation-languages'], $settings['publish-languages']) );
        foreach($check_language_codes as $check_language_code ){
            if ( !etm_is_valid_language_code($check_language_code) ){
                add_settings_error( 'etm_advanced_settings', 'settings_error', esc_html__('Invalid language code. Please try again.', 'etranslation-multilingual'), 'error' );
                return get_option( 'etm_settings', 'not_set' );
            }
        }

        if( !empty( $settings['native_or_english_name'] ) )
            $settings['native_or_english_name'] = sanitize_text_field( $settings['native_or_english_name']  );
        else
            $settings['native_or_english_name'] = 'english_name';

        if( !empty( $settings['add-subdirectory-to-default-language'] ) )
            $settings['add-subdirectory-to-default-language'] = sanitize_text_field( $settings['add-subdirectory-to-default-language']  );
        else
            $settings['add-subdirectory-to-default-language'] = 'no';

        if( !empty( $settings['force-language-to-custom-links'] ) )
            $settings['force-language-to-custom-links'] = sanitize_text_field( $settings['force-language-to-custom-links']  );
        else
            $settings['force-language-to-custom-links'] = 'no';


        if ( !empty( $settings['etm-ls-floater'] ) ){
            $settings['etm-ls-floater'] = sanitize_text_field( $settings['etm-ls-floater'] );
        }else{
            $settings['etm-ls-floater'] = 'no';
        }

        $language_switcher_options = $this->get_language_switcher_options();
        if ( ! isset( $language_switcher_options[ $settings['shortcode-options'] ] ) ){
            $settings['shortcode-options'] = 'full-names';
        }
        if ( ! isset( $language_switcher_options[ $settings['menu-options'] ] ) ){
            $settings['menu-options'] = 'full-names';
        }
        if ( ! isset( $language_switcher_options[ $settings['floater-options'] ] ) ){
            $settings['floater-options'] = 'full-names';
        }

        if ( ! isset( $settings['floater-position'] ) ){
            $settings['floater-position'] = 'top-right';
        }

	    if ( ! isset( $settings['floater-color'] ) ){
		    $settings['floater-color'] = 'default';
	    }

	    if ( !empty( $settings['etm-ls-show-poweredby'] ) ){
		    $settings['etm-ls-show-poweredby'] = sanitize_text_field( $settings['etm-ls-show-poweredby'] );
	    }else{
		    $settings['etm-ls-show-poweredby'] = 'no';
	    }

        if ( ! isset( $settings['url-slugs'] ) ){
            $settings['url-slugs'] = $this->etm_languages->get_iso_codes( $settings['translation-languages'] );
        }

        foreach( $settings['translation-languages'] as $language_code ){
            if ( empty ( $settings['url-slugs'][$language_code] ) ){
                $settings['url-slugs'][$language_code] = $language_code;
            }else{
                $settings['url-slugs'][$language_code] = sanitize_title( strtolower( $settings['url-slugs'][$language_code] )) ;
            }
        }

        foreach ($settings['translation-languages'] as $value=>$language){
            if(isset($settings['translation-languages-domain'][$value])) {
                $settings['translation-languages-domain-parameter'][ $language ] = $settings['translation-languages-domain'][ $value ];
            } else {
                $settings['translation-languages-domain-parameter'][ $language ] = 'GEN';
            }
        }

        unset($settings['translation-languages-domain']);

        // check for duplicates in url slugs
        $duplicate_exists = false;
        foreach( $settings['url-slugs'] as $urlslug ) {
            if ( count ( array_keys( $settings['url-slugs'], $urlslug ) ) > 1 ){
                $duplicate_exists = true;
                break;
            }
        }
        if ( $duplicate_exists ){
            foreach( $settings['translation-languages'] as $language_code ) {
                $settings['url-slugs'][$language_code] = $language_code;
            }
        }

        $this->create_menu_entries( $settings['publish-languages'] );

        $gettext_table_creation = $this->etm_query->get_query_component('gettext_table_creation');
        require_once( ABSPATH . 'wp-includes/load.php' );
        foreach ( $settings['translation-languages'] as $language_code ){
            if ( $settings['default-language'] != $language_code ) {
                $this->etm_query->check_table( $settings['default-language'], $language_code );
            }
            wp_download_language_pack( $language_code );
            $gettext_table_creation->check_gettext_table( $language_code );
        }

        //in version 1.6.6 we normalized the original strings and created new tables
        $this->etm_query->check_original_table();
        $this->etm_query->check_original_meta_table();
        $gettext_table_creation->check_gettext_original_table();
        $gettext_table_creation->check_gettext_original_meta_table();

        // regenerate permalinks in case something changed
        flush_rewrite_rules();

        return apply_filters( 'etm_extra_sanitize_settings', $settings );
    }

    /**
     * Output admin notices after saving settings.
     */
    public function admin_notices(){
        settings_errors( 'etm_settings' );
    }

    /**
     * Set options array variable to be used across plugin.
     *
     * Sets a default option if it does not exist.
     */
    protected function set_options(){
        $settings_option = get_option( 'etm_settings', 'not_set' );

        // initialize default settings
        // $default = get_locale();
        // if ( empty( $default ) ){
        //     $default = 'en_GB';
        // }
        $default = 'en_GB';
        $default_settings = array(
            'default-language'                     => $default,
            'translation-languages'                => array( $default ),
            'publish-languages'                    => array( $default ),
            'native_or_english_name'               => 'english_name',
            'add-subdirectory-to-default-language' => 'no',
            'force-language-to-custom-links'       => 'yes',
            'etm-ls-floater'                       => 'yes',
            'shortcode-options'                    => 'full-names',
            'menu-options'                         => 'full-names',
            'floater-options'                      => 'full-names',
            'floater-position'                     => 'top-right',
	        'floater-color'                        => 'default',
	        'etm-ls-show-poweredby'                => 'no',
            'url-slugs'                            => array( 'en_GB' => 'en', '' ),
        );

        if ( 'not_set' == $settings_option ){
            update_option ( 'etm_settings', $default_settings );
            $settings_option = $default_settings;
        }else{
            // Add any missing default option for etm_setting
            foreach ( $default_settings as $key_default_setting => $value_default_setting ){
                if ( !isset ( $settings_option[$key_default_setting] ) ) {
                    $settings_option[$key_default_setting] = $value_default_setting;
                }
            }
        }

        // Might have saved invalid language codes in the past so this code protects against SQL Injections using invalid language codes which are used in queries
        $check_language_codes = array_unique( array_merge($settings_option['translation-languages'], $settings_option['publish-languages']) );
        foreach($check_language_codes as $check_language_code ) {
            if ( !etm_is_valid_language_code( $check_language_code ) ) {
                add_filter('plugins_loaded', array($this, 'show_invalid_language_codes_error_notice'), 999999);
            }
        }


        /**
         * These options (etm_advanced_settings,etm_machine_translation_settings) are not part of the actual etm_settings DB option.
         * But they are included in $settings variable across TP
         */
        $settings_option['etm_advanced_settings'] = get_option('etm_advanced_settings', array() );

        // Add any missing default option for etm_machine_translation_settings
        $default_etm_machine_translation_settings = $this->get_default_etm_machine_translation_settings();
        $settings_option['etm_machine_translation_settings'] = array_merge( $default_etm_machine_translation_settings, get_option( 'etm_machine_translation_settings', $default_etm_machine_translation_settings ) );

        $this->settings = $settings_option;
    }

    public function show_invalid_language_codes_error_notice(){
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $error_manager = $etm->get_component( 'error_manager' );

        $error_manager->record_error(
            array( 'message'         => esc_html__('Language codes can contain only A-Z a-z 0-9 - _ characters. Check your language codes in eTranslation Multilingual General Settings.', 'etranslation-multilingual'),
                   'notification_id' => 'etm_invalid_language_code' ) );
    }

    public function get_default_etm_machine_translation_settings(){
        return apply_filters( 'etm_get_default_etm_machine_translation_settings', array(
            // default settings for etm_machine_translation_settings
            'machine-translation'               => 'no',
            'translation-engine'                => 'etranslation',
            'block-crawlers'                    => 'no',
            'machine_translation_counter_date'  => date ("Y-m-d" ),
            'machine_translation_counter'       => 0,
            'machine_translation_limit'         => 1000000,            
            'show-mt-notice'                       => 'yes'
            /*
             * These settings are merged into the saved DB option.
             * Be sure to set any checkboxes options to 'no' in sanitize_settings.
             * Unchecked checkboxes don't have a POST value when saving settings so they will be overwritten by merging.
             */
        ));
    }

    /**
     * Enqueue scripts and styles for settings page.
     *
     * @param string $hook          Admin page.
     */
    public function enqueue_scripts_and_styles( $hook ) {
        if( in_array( $hook, [ 'settings_page_etranslation-multilingual', 'admin_page_etm_advanced_page', 'admin_page_etm_machine_translation', 'admin_page_etm_test_machine_api' ] ) ){
            wp_enqueue_style(
                'etm-settings-style',
                ETM_PLUGIN_URL . 'assets/css/etm-back-end-style.css',
                array(),
                ETM_PLUGIN_VERSION
            );
        }

        if( in_array( $hook, array( 'settings_page_etranslation-multilingual', 'admin_page_etm_advanced_page', 'admin_page_etm_machine_translation' ) ) ) {
            wp_enqueue_script( 'etm-settings-script', ETM_PLUGIN_URL . 'assets/js/etm-back-end-script.js', array( 'jquery', 'jquery-ui-sortable' ), ETM_PLUGIN_VERSION );

            $etm                 = ETM_eTranslation_Multilingual::get_etm_instance();
            if ( ! $this->etm_languages ){
                $this->etm_languages = $etm->get_component( 'languages' );
            }

            $all_language_codes = $this->etm_languages->get_all_language_codes();
            $iso_codes          = $this->etm_languages->get_iso_codes( $all_language_codes, false );
            $domains            = array();
            $machine_translator = $etm->get_component('machine_translator');
            if ($machine_translator->is_available() && $machine_translator instanceof ETM_eTranslation_Machine_Translator && $machine_translator->credentials_set()) {
                $domains = $machine_translator->get_all_domains();
            }

            wp_localize_script( 'etm-settings-script', 'etm_url_slugs_info', array( 'iso_codes' => $iso_codes, 'error_message_duplicate_slugs' => __( 'Error! Duplicate URL slug values.', 'etranslation-multilingual' ), 'domains' => $domains ) );

            wp_enqueue_script( 'etm-select2-lib-js', ETM_PLUGIN_URL . 'assets/lib/select2-lib/dist/js/select2.min.js', array( 'jquery' ), ETM_PLUGIN_VERSION );
            wp_enqueue_style( 'etm-select2-lib-css', ETM_PLUGIN_URL . 'assets/lib/select2-lib/dist/css/select2.min.css', array(), ETM_PLUGIN_VERSION );

        }
    }

    /**
     * Output HTML for Translation Language option.
     *
     * Hooked to etm_language_selector.
     *
     * @param array $languages          All available languages.
     */
    public function languages_selector( $languages ){
        if ( ! $this->url_converter ) {
            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $this->url_converter = $etm->get_component('url_converter');
        }
        $selected_language_code = '';

        require_once ETM_PLUGIN_DIR . 'partials/main-settings-language-selector.php';
    }

    /**
     * Update language switcher menu items.
     *
     * @param array $languages          Array of language codes to create menu items for.
     */
    public function create_menu_entries( $languages ){
        if ( ! $this->etm_languages ){
            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $this->etm_languages = $etm->get_component( 'languages' );
        }
        $published_languages = $this->etm_languages->get_language_names( $languages, 'english_name' );
        $published_languages['current_language'] = __( 'Current Language', 'etranslation-multilingual' );
        $languages[] = 'current_language';
        $posts = get_posts( array( 'post_type' =>'language_switcher',  'posts_per_page'   => -1  ) );

        if ( count( $published_languages ) == 3 ){
            $languages[] = 'opposite_language';
            $published_languages['opposite_language'] = __( 'Opposite Language', 'etranslation-multilingual' );
        }

        foreach ( $published_languages as $language_code => $language_name ) {
            $existing_ls = null;
            foreach( $posts as $post ){
                if ( $post->post_content == $language_code ){
                    $existing_ls = $post;
                    break;
                }
            }

            $ls = array(
                'post_title' => $language_name,
                'post_content' => $language_code,
                'post_status' => 'publish',
                'post_type' => 'language_switcher'
            );
            if ( $existing_ls ){
                $ls['ID'] = $existing_ls->ID;
                wp_update_post( $ls );
            }else{
                wp_insert_post( $ls );
            }
        }

        foreach ( $posts as $post ){
            if ( ! in_array( $post->post_content, $languages ) ){
                wp_delete_post( $post->ID );
            }
        }
    }

    /**
     * Add navigation tabs in settings.
     *
     */
    public function add_navigation_tabs(){
        $tabs = array(
            array(
                'name'  => __( 'General', 'etranslation-multilingual' ),
                'url'   => admin_url( 'options-general.php?page=etranslation-multilingual' ),
                'page'  => 'etranslation-multilingual'
            ),
            array(
                'name'  => __( 'Translate Site', 'etranslation-multilingual' ),
                'url'   => add_query_arg( 'etm-edit-translation', 'true', home_url() ),
                'page'  => 'etm_translation_editor'
            ),
        );

	    $tabs = apply_filters( 'etm_settings_tabs', $tabs );

        $active_tab = 'etranslation-multilingual';
        if ( isset( $_GET['page'] ) ){
            $active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
        }

        require ETM_PLUGIN_DIR . 'partials/settings-navigation-tabs.php';
    }

    /**
     * Add SVG icon symbols to use throughout the admin.
     */
    public function add_svg_icons() {
        ?>
        <svg width="0" height="0" class="hidden">
			<symbol aria-hidden="true" data-prefix="fas" data-icon="check-circle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" id="check-circle">
                <path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path>
            </symbol>
            <symbol aria-hidden="true" data-prefix="fas" data-icon="times-circle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" id="times-circle">
                <path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm121.6 313.1c4.7 4.7 4.7 12.3 0 17L338 377.6c-4.7 4.7-12.3 4.7-17 0L256 312l-65.1 65.6c-4.7 4.7-12.3 4.7-17 0L134.4 338c-4.7-4.7-4.7-12.3 0-17l65.6-65-65.6-65.1c-4.7-4.7-4.7-12.3 0-17l39.6-39.6c4.7-4.7 12.3-4.7 17 0l65 65.7 65.1-65.6c4.7-4.7 12.3-4.7 17 0l39.6 39.6c4.7 4.7 4.7 12.3 0 17L312 256l65.6 65.1z"></path>
            </symbol>
        </svg>
        <?php
    }

}
