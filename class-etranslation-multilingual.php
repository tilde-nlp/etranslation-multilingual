<?php

/**
 * Class ETM_eTranslation_Multilingual
 *
 * Singleton. Loads required files, initializes components and hooks methods.
 *
 */
class ETM_eTranslation_Multilingual{
    protected $loader;
    protected $settings;
    protected $translation_render;
    protected $machine_translator;
    protected $machine_translator_logger;
    protected $query;
    protected $language_switcher;
    protected $mt_notice;
    protected $translation_manager;
    protected $editor_api_regular_strings;
    protected $editor_api_gettext_strings;
    protected $url_converter;
    protected $languages;
    protected $slug_manager;
    protected $upgrade;
    protected $advanced_tab;
    protected $translation_memory;
    protected $machine_translation_tab;
    protected $error_manager;
    protected $string_translation;
    protected $string_translation_api_regular;
    protected $notifications;
    protected $search;
    protected $gettext_manager;
    protected $gettext_scan;
    protected $rewrite_rules;
    protected $check_invalid_text;
    protected $woocommerce_emails;
    protected $preferred_user_language;

    public static $etranslation_multilingual = null;

    /**
     * Get singleton object.
     *
     * @return ETM_eTranslation_Multilingual      Singleton object.
     */
    public static function get_etm_instance(){
        if ( self::$etranslation_multilingual == null ){
            self::$etranslation_multilingual = new ETM_eTranslation_Multilingual();
        }

        return self::$etranslation_multilingual;
    }

    /**
     * ETM_eTranslation_Multilingual constructor.
     */
    public function __construct() {
        define( 'ETM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'ETM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'ETM_PLUGIN_BASE', plugin_basename( __DIR__ . '/index.php' ) );
        define( 'ETM_PLUGIN_SLUG', 'etranslation-multilingual' );
        define( 'ETM_PLUGIN_VERSION', '1.0.0' );

	    wp_cache_add_non_persistent_groups(array('etm'));

        $this->load_dependencies();
        $this->initialize_components();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
    }

    /**
     * Returns particular component by name.
     *
     * @param string $component     'loader' | 'settings' | 'translation_render' |
     *                              'machine_translator' | 'query' | 'language_switcher' |
     *                              'translation_manager' | 'url_converter' | 'languages'
     * @return mixed
     */
    public function get_component( $component ){
        return $this->$component;
    }

    /**
     * Includes necessary files.
     */
    protected function load_dependencies() {
        require_once ETM_PLUGIN_DIR . 'includes/class-settings.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-translation-manager.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-editor-api-regular-strings.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-editor-api-gettext-strings.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-translation-manager.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-hooks-loader.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-languages.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-translation-render.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-language-switcher.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-machine-translator.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-machine-translator-logger.php';
        require_once ETM_PLUGIN_DIR . 'includes/queries/class-query.php';
        require_once ETM_PLUGIN_DIR . 'includes/queries/class-gettext-normalization.php';
        require_once ETM_PLUGIN_DIR . 'includes/queries/class-gettext-table-creation.php';
        require_once ETM_PLUGIN_DIR . 'includes/queries/class-gettext-insert-update.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-url-converter.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-uri.php';
	    require_once ETM_PLUGIN_DIR . 'includes/class-upgrade.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-plugin-notices.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-advanced-tab.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-translation-memory.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-error-manager.php';
        require_once ETM_PLUGIN_DIR . 'includes/external-functions.php';
        require_once ETM_PLUGIN_DIR . 'includes/compatibility-functions.php';
        require_once ETM_PLUGIN_DIR . 'includes/functions.php';
        require_once ETM_PLUGIN_DIR . 'includes/custom-language.php';
        require_once ETM_PLUGIN_DIR . 'assets/lib/simplehtmldom/simple_html_dom.php';
        require_once ETM_PLUGIN_DIR . 'includes/shortcodes.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-machine-translation-tab.php';
        require_once ETM_PLUGIN_DIR . 'includes/string-translation/class-string-translation.php';
        require_once ETM_PLUGIN_DIR . 'includes/string-translation/class-string-translation-helper.php';
        require_once ETM_PLUGIN_DIR . 'includes/string-translation/class-gettext-scan.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-search.php';
        require_once ETM_PLUGIN_DIR . 'includes/gettext/class-gettext-manager.php';
        require_once ETM_PLUGIN_DIR . 'includes/gettext/class-process-gettext.php';
        require_once ETM_PLUGIN_DIR . 'includes/gettext/class-plural-forms.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-rewrite-rules.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-check-invalid-text.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-woocommerce-emails.php';
	    require_once ETM_PLUGIN_DIR . 'includes/string-translation/class-string-translation-api-gettext.php';
	    require_once ETM_PLUGIN_DIR . 'includes/string-translation/class-string-translation-api-regular.php';
        require_once ETM_PLUGIN_DIR . 'includes/class-preferred-user-language.php';
        
        if ( did_action( 'elementor/loaded' ) )
            require_once ETM_PLUGIN_DIR . 'includes/class-elementor-language-for-blocks.php';
        if ( defined( 'WPB_VC_VERSION' ) ) {
            require_once ETM_PLUGIN_DIR . 'includes/class-wp-bakery-language-for-blocks.php';
        }
        require_once ETM_PLUGIN_DIR . 'includes/class-mt-notice.php';
    }

    /**
     * Instantiates components.
     */
    protected function initialize_components() {
        $this->loader                     = new ETM_Hooks_Loader();
        $this->languages                  = new ETM_Languages();
        $this->settings                   = new ETM_Settings();

        $this->advanced_tab               = new ETM_Advanced_Tab($this->settings->get_settings());
        $this->advanced_tab->include_custom_codes();

        $this->machine_translation_tab    = new ETM_Machine_Translation_Tab( $this->settings->get_settings() );
        $this->machine_translation_tab->load_engines();

        $this->translation_render         = new ETM_Translation_Render( $this->settings->get_settings() );
        $this->url_converter              = new ETM_Url_Converter( $this->settings->get_settings() );
        $this->language_switcher          = new ETM_Language_Switcher( $this->settings->get_settings(), $this );
        $this->mt_notice                  = new ETM_MT_Notice( $this->settings->get_settings(), $this->url_converter );
        $this->query                      = new ETM_Query( $this->settings->get_settings() );
        $this->machine_translator_logger  = new ETM_Machine_Translator_Logger( $this->settings->get_settings() );
        $this->translation_manager        = new ETM_Translation_Manager( $this->settings->get_settings() );
        $this->editor_api_regular_strings = new ETM_Editor_Api_Regular_Strings( $this->settings->get_settings() );
        $this->editor_api_gettext_strings = new ETM_Editor_Api_Gettext_Strings( $this->settings->get_settings() );
        $this->notifications              = new ETM_Trigger_Plugin_Notifications( $this->settings->get_settings() );
        $this->upgrade                    = new ETM_Upgrade( $this->settings->get_settings() );
        $this->translation_memory         = new ETM_Translation_Memory( $this->settings->get_settings() );
        $this->error_manager              = new ETM_Error_Manager( $this->settings->get_settings() );
        $this->string_translation         = new ETM_String_Translation( $this->settings->get_settings(), $this->loader );
        $this->gettext_scan               = new ETM_Gettext_Scan( $this->settings->get_settings() );
        $this->search                     = new ETM_Search( $this->settings->get_settings() );
        $this->gettext_manager            = new ETM_Gettext_Manager( $this->settings->get_settings() );
        $this->rewrite_rules              = new ETM_Rewrite_Rules( $this->settings->get_settings() );
        $this->check_invalid_text         = new ETM_Check_Invalid_Text( );
        $this->woocommerce_emails         = new ETM_Woocommerce_Emails();
        $this->preferred_user_language    = new ETM_Preferred_User_Language();
    }

    /**
     * Hooks methods used in admin area.
     */
    protected function define_admin_hooks() {
        $this->loader->add_action( 'admin_menu', $this->settings, 'register_menu_page' );
        $this->loader->add_action( 'admin_init', $this->settings, 'register_setting' );
        $this->loader->add_action( 'admin_notices', $this->settings, 'admin_notices' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this->settings, 'enqueue_scripts_and_styles', 10, 1 );
        $this->loader->add_filter( 'plugin_action_links_' . ETM_PLUGIN_BASE , $this->settings, 'plugin_action_links', 10, 1 );
        $this->loader->add_action( 'etm_settings_navigation_tabs', $this->settings, 'add_navigation_tabs' );
        $this->loader->add_action( 'etm_settings_navigation_tabs', $this->settings, 'add_svg_icons' );
        $this->loader->add_action( 'etm_language_selector', $this->settings, 'languages_selector', 10, 1 );

	    $this->loader->add_action( 'etm_settings_tabs', $this->advanced_tab, 'add_advanced_tab_to_settings', 10, 1 );
	    $this->loader->add_action( 'admin_menu', $this->advanced_tab, 'add_submenu_page_advanced' );
	    $this->loader->add_action( 'etm_output_advanced_settings_options', $this->advanced_tab, 'output_advanced_options' );
	    $this->loader->add_action( 'etm_before_output_advanced_settings_options', $this->advanced_tab, 'etm_advanced_settings_content_table' );
	    $this->loader->add_action( 'admin_init', $this->advanced_tab, 'register_setting' );
	    $this->loader->add_action( 'admin_notices', $this->advanced_tab, 'admin_notices' );

        //Machine Translation tab
        $this->loader->add_action( 'etm_settings_tabs', $this->machine_translation_tab, 'add_tab_to_navigation', 10, 1 );
        $this->loader->add_action( 'admin_menu',        $this->machine_translation_tab, 'add_submenu_page' );
        $this->loader->add_action( 'admin_init',        $this->machine_translation_tab, 'register_setting' );
        $this->loader->add_action( 'admin_notices',     $this->machine_translation_tab, 'admin_notices' );
        $this->loader->add_action( 'etm_machine_translation_extra_settings_bottom',     $this->machine_translation_tab, 'display_unsupported_languages' );

        //Machine Translation Logger defaults
        $this->loader->add_action( 'etm_machine_translation_sanitize_settings', $this->machine_translator_logger, 'sanitize_settings', 10, 1 );

        //Error manager hooks
        $this->loader->add_action( 'admin_init', $this->error_manager, 'show_notification_about_errors', 10 );
        $this->loader->add_action( 'admin_menu', $this->error_manager, 'register_submenu_errors_page', 10 );
        $this->loader->add_action( 'etm_dismiss_notification', $this->error_manager, 'clear_notification_from_db', 10, 2 );
        $this->loader->add_filter( 'etm_machine_translation_sanitize_settings', $this->error_manager, 'clear_disable_machine_translation_notification_from_db', 10, 1 );
        $this->loader->add_filter( 'etm_error_manager_page_output', $this->error_manager, 'show_instructions_on_how_to_fix', 7, 1 );
        $this->loader->add_filter( 'etm_error_manager_page_output', $this->error_manager, 'output_db_errors', 10, 1 );
        $this->loader->add_action('load-admin_page_etm_error_manager', $this->error_manager, 'disable_error_after_click_link', 10);

        $this->loader->add_action( 'wp_ajax_nopriv_etm_get_translations_regular', $this->editor_api_regular_strings, 'get_translations' );

	    $this->loader->add_action( 'wp_ajax_etm_get_translations_regular', $this->editor_api_regular_strings, 'get_translations' );
        $this->loader->add_action( 'wp_ajax_etm_save_translations_regular', $this->editor_api_regular_strings, 'save_translations' );
        $this->loader->add_action( 'wp_ajax_etm_split_translation_block', $this->editor_api_regular_strings, 'split_translation_block' );
        $this->loader->add_action( 'wp_ajax_etm_create_translation_block', $this->editor_api_regular_strings, 'create_translation_block' );

	    $this->loader->add_action( 'wp_ajax_etm_get_translations_gettext', $this->editor_api_gettext_strings, 'gettext_get_translations' );
	    $this->loader->add_action( 'wp_ajax_etm_save_translations_gettext', $this->editor_api_gettext_strings, 'gettext_save_translations' );

        $this->loader->add_action( 'wp_ajax_etm_get_similar_string_translation', $this->translation_memory, 'ajax_get_similar_string_translation' );
	    $this->loader->add_action( 'wp_ajax_etm_scan_gettext', $this->gettext_scan, 'scan_gettext' );

	    $this->loader->add_filter( 'etm_get_existing_translations', $this->translation_manager, 'display_possible_db_errors', 20, 3 );
        $this->loader->add_action( 'wp_ajax_etm_save_editor_user_meta', $this->translation_manager, 'save_editor_user_meta', 10 );
        $this->loader->add_action( 'etm_editor_notices', $this->translation_manager, 'display_notice_to_upgrade_gettext_in_editor', 10, 1 );


        $this->loader->add_action( 'wp_ajax_etm_process_js_strings_in_translation_editor', $this->translation_render, 'process_js_strings_in_translation_editor' );
        $this->loader->add_filter( 'etm_skip_selectors_from_dynamic_translation', $this->translation_render, 'skip_base_attributes_from_dynamic_translation', 10, 1 );


	    $this->loader->add_action( 'admin_menu', $this->upgrade, 'register_menu_page' );
        $this->loader->add_action( 'admin_init', $this->upgrade, 'show_admin_error_message' );
	    $this->loader->add_action( 'admin_init', $this->upgrade, 'show_admin_notice' );
        $this->loader->add_action( 'admin_init', $this->upgrade, 'etm_prepare_options_for_database_optimization' );
	    $this->loader->add_action( 'admin_enqueue_scripts', $this->upgrade, 'enqueue_update_script', 10, 1 );
	    $this->loader->add_action( 'wp_ajax_etm_update_database', $this->upgrade, 'etm_update_database' );

        // Filter rewrite rules for .htaccess
        $this->loader->add_filter( 'mod_rewrite_rules', $this->rewrite_rules, 'etm_remove_language_param', 100 );

        // Add hooks for translating WooCommerce emails
        $this->loader->add_action( 'init', $this->woocommerce_emails, 'initialize_hooks' );

        $this->loader->add_action( 'show_user_profile', $this->preferred_user_language, 'always_use_this_language', 99, 1 );
        $this->loader->add_action( 'edit_user_profile', $this->preferred_user_language, 'always_use_this_language', 99, 1 );
        $this->loader->add_action( 'personal_options_update', $this->preferred_user_language, 'update_profile_fields', 99, 1 );
        $this->loader->add_action( 'edit_user_profile_update', $this->preferred_user_language, 'update_profile_fields', 99, 1 );

    }

    /**
     * Hooks methods used in front-end
     */
    protected function define_frontend_hooks(){

        //we do not need the plugin in cron requests ?
        if( isset( $_REQUEST['doing_wp_cron'] ) )
            return;

        $this->loader->add_action( 'init', $this->translation_render, 'start_output_buffer', apply_filters( 'etm_start_output_buffer_priority', 0 ) );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_render, 'enqueue_scripts', 10 );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_render, 'enqueue_dynamic_translation', 1 );
        $this->loader->add_filter( 'wp_redirect', $this->translation_render, 'force_preview_on_url_redirect', 99, 2 );
        $this->loader->add_filter( 'wp_redirect', $this->translation_render, 'force_language_on_form_url_redirect', 99, 2 );
        $this->loader->add_filter( 'etm_before_translate_content', $this->translation_render, 'force_preview_on_url_in_ajax', 10 );
        $this->loader->add_filter( 'etm_before_translate_content', $this->translation_render, 'force_form_language_on_url_in_ajax', 20 );
        /* handle CDATA str replacement from the content as it is messing up the renderer */
        $this->loader->add_filter( "etm_before_translate_content", $this->translation_render, 'handle_cdata', 1000 );
        $this->loader->add_action( "etm_set_translation_for_attribute", $this->translation_render, 'translate_image_srcset_attributes', 10, 3 );
        $this->loader->add_filter( "etm_allow_machine_translation_for_string", $this->translation_render, 'allow_machine_translation_for_string', 10, 4 );
        $this->loader->add_filter( "etm_allow_machine_translation_for_string", $this->translation_render, 'skip_automatic_translation_for_no_auto_translation_selector', 10, 5 );
        $this->loader->add_action( "init", $this->translation_render, 'add_callbacks_for_translating_rest_api', 10, 4 );
        $this->loader->add_filter( "oembed_response_data", $this->translation_render, 'oembed_response_data', 10, 4 );

        /* add custom containers for post content and pots title so we can identify string that are part of them */
        $this->loader->add_filter( "the_content", $this->translation_render, 'wrap_with_post_id', 1000 );
        $this->loader->add_filter( "the_title", $this->translation_render, 'wrap_with_post_id', 1000, 2 );




        $this->loader->add_action( 'wp_enqueue_scripts', $this->language_switcher, 'enqueue_language_switcher_scripts' );
        $this->loader->add_action( 'wp_footer', $this->language_switcher, 'add_floater_language_switcher' );
        $this->loader->add_filter( 'init', $this->language_switcher, 'register_ls_menu_switcher' );
        $this->loader->add_action( 'wp_get_nav_menu_items', $this->language_switcher, 'ls_menu_permalinks', 10, 3 );
        add_shortcode( 'language-switcher', array( $this->language_switcher, 'language_switcher' ) );


        $this->loader->add_action( 'etm_translation_manager_footer', $this->translation_manager, 'enqueue_scripts_and_styles' );
        $this->loader->add_filter( 'template_include', $this->translation_manager, 'translation_editor', 99999 );
        $this->loader->add_filter( 'option_date_format', $this->translation_manager, 'filter_the_date' );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_manager, 'enqueue_preview_scripts_and_styles' );
        $this->loader->add_action( 'admin_bar_menu', $this->translation_manager, 'add_shortcut_to_translation_editor', 90, 1 );
        $this->loader->add_action( 'admin_head', $this->translation_manager, 'add_styling_to_admin_bar_button', 10 );
        $this->loader->add_filter( 'show_admin_bar', $this->translation_manager, 'hide_admin_bar_when_in_editor', 90 );
        $this->loader->add_action( 'enqueue_block_editor_assets', $this->translation_manager, 'etm_add_shortcut_to_etm_editor_gutenberg', 90);

        $this->loader->add_filter( 'template_include', $this->string_translation, 'string_translation_editor', 99999 );
        $this->loader->add_filter( 'etm_string_types', $this->string_translation, 'register_string_types', 10, 1 );
        $this->loader->add_filter( 'etm_editor_nonces', $this->string_translation, 'add_nonces_for_saving_translation', 10, 1 );
        $this->loader->add_action( 'etm_string_translation_editor_footer', $this->string_translation, 'enqueue_scripts_and_styles' );
        $this->loader->add_action( 'init', $this->string_translation, 'register_ajax_hooks' );


        $this->loader->add_filter( 'home_url', $this->url_converter, 'add_language_to_home_url', 1, 4 );
        $this->loader->add_action( 'wp_head', $this->url_converter, 'add_hreflang_to_head' );
        $this->loader->add_filter( 'language_attributes', $this->url_converter, 'change_lang_attr_in_html_tag', 10, 1 );
        $this->loader->add_filter('etm_is_file', $this->url_converter, 'does_url_contains_array', 10, 2);
        $this->loader->add_filter('etm_hreflang', $this->url_converter, 'replace_iso_2_with_iso_3_for_hreflang', 10, 2);
        $this->loader->add_filter('wp_footer', $this->url_converter, 'add_etm_language_lang_attribute', 1);


        $this->loader->add_filter( 'widget_text', null, 'do_shortcode', 11 );
        $this->loader->add_filter( 'widget_text', null, 'shortcode_unautop', 11 );

        /* handle dynamic texts with gettext */
        $this->loader->add_filter( 'locale', $this->languages, 'change_locale', 99999 );
        $this->loader->add_filter( 'locale', $this->languages, 'clear_cache_locale', 99998 );
        $this->loader->add_filter( 'plugin_locale', $this->languages, 'change_locale', 99999 );
        $this->loader->add_filter( 'plugin_locale', $this->languages, 'clear_cache_locale', 99998 );

        $this->loader->add_action( 'init', $this->gettext_manager, 'create_gettext_translated_global' );
        $this->loader->add_action( 'init', $this->gettext_manager, 'initialize_gettext_processing' );
        $this->loader->add_action( 'etm_call_gettext_filters', $this->gettext_manager, 'verify_locale_of_loaded_textdomain' );
        $this->loader->add_action( 'shutdown', $this->gettext_manager, 'machine_translate_gettext', 100 );


        /* we need to treat the date_i18n function differently so we remove the gettext wraps */
        $this->loader->add_filter( 'date_i18n', $this->gettext_manager, 'handle_date_i18n_function_for_gettext', 1, 4 );
	    /* strip esc_url() from gettext wraps */
	    $this->loader->add_filter( 'clean_url', $this->gettext_manager, 'etm_strip_gettext_tags_from_esc_url', 1, 3 );
	    /* strip sanitize_title() from gettext wraps and apply custom etm_remove_accents */
	    $this->loader->add_filter( 'sanitize_title', $this->gettext_manager, 'etm_sanitize_title', 1, 3 );

        /* define an update hook here */
        $this->loader->add_action( 'plugins_loaded', $this->upgrade, 'check_for_necessary_updates', 10 );

        $this->loader->add_filter( 'etm_language_name', $this->languages, 'beautify_language_name', 10, 4 );
        $this->loader->add_filter( 'etm_languages', $this->languages, 'reorder_languages', 10, 2 );

        /* set up wp_mail hooks */
        $this->loader->add_filter( 'wp_mail', $this->translation_render, 'wp_mail_filter', 1 );

        /* hide php ors and notice when we are storing strings in db */
        $this->loader->add_action( 'init', $this->translation_render, 'etm_debug_mode_off', 0 );

        /* fix wptexturize to always replace with the default translated strings */
        $this->loader->add_action( 'gettext_with_context', $this->translation_render, 'fix_wptexturize_characters', 999, 4 );

        /* ?or init ? hook here where you can change the $current_user global */
        $this->loader->add_action( 'init', $this->translation_manager, 'etm_view_as_user' );

        /**
         * we need to modify the permalinks structure for woocommerce when we switch languages
         * when woo registers post_types and taxonomies in the rewrite parameter of the function they change the slugs of the items (they are localized with _x )
         * we can't flush the permalinks on every page load so we filter the rewrite_rules option
         */
        $this->loader->add_filter( "option_rewrite_rules", $this->url_converter, 'woocommerce_filter_permalinks_on_other_languages' );
        $this->loader->add_filter( "option_woocommerce_permalinks", $this->url_converter, 'woocommerce_filter_permalink_option' );
        $this->loader->add_filter( "pre_update_option_woocommerce_permalinks", $this->url_converter, 'prevent_permalink_update_on_other_languages', 10, 2 );
        $this->loader->add_filter( "pre_update_option_rewrite_rules", $this->url_converter, 'prevent_permalink_update_on_other_languages', 10, 2 );
        $this->loader->add_filter( "pre_update_option_rewrite_rules", $this->url_converter, 'delete_woocommerce_transient_permalink' );

        /* add to the body class the current language */
        $this->loader->add_filter( "body_class", $this->translation_manager, 'add_language_to_body_class' );

        /* load textdomain */
        $this->loader->add_action( "init", $this, 'init_translation', 8 );

        // machine translation
        $this->loader->add_action( 'plugins_loaded', $this, 'init_machine_translation', 2 );

        //search
        $this->loader->add_filter( 'pre_get_posts', $this->search, 'etm_search_filter', 99999999 );
        $this->loader->add_filter( 'get_search_query', $this->search, 'etm_search_query', 10 );

        /* prevent indexing edit translation preview pages */
        $this->loader->add_action( 'etm_head', $this->translation_manager, 'output_noindex_tag', 100 );
        $this->loader->add_action( 'wp_head', $this->translation_manager, 'output_noindex_tag', 100 );

        $this->loader->add_action( 'wp_enqueue_scripts', $this->mt_notice, 'enqueue_mt_notice_scripts' );
    }

    /**
     * Register hooks to WP.
     */
    public function run() {
    	/*
    	 * Hook that prevents running the hooks. Caution: some ETM code like constructors of classes still run!
    	 */
    	$run_etm = apply_filters( 'etm_allow_etm_to_run', true );
    	if ( $run_etm ) {
		    $this->loader->run();
	    }
    }

    /**
     * Load plugin textdomain
     */
    public function init_translation(){
        load_plugin_textdomain( 'etranslation-multilingual', false, basename(dirname(__FILE__)) . '/languages/' );
    }

    public function init_machine_translation(){
        $this->machine_translator = $this->machine_translation_tab->get_active_engine();
    }
}
