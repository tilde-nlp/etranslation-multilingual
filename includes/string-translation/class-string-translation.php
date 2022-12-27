<?php


class ETM_String_Translation {
    protected $settings;
    /* @var ETM_Translation_Manager */
    protected $translation_manager;

    // flat structure of string_types_config
    protected $string_types = array();

    // actual classes that may get retrieved from elsewhere through get_string_type_API()
    protected $string_type_apis = array();
    /**
     * @var array
     */
    protected $gettext_domains;

    public function __construct( $settings, $loader ) {
        $this->settings = $settings;
        $this->loader   = $loader;


    }

    public function register_ajax_hooks() {
        // Build a flat structure of string types
        $string_types_config = $this->string_types_config();
        foreach ( $string_types_config as $string_type_key => $string_type_value ) {
            if ( $string_type_value['category_based'] ) {
                foreach ( $string_type_value['categories'] as $substring_type_key => $substring_type_value ) {
                    $this->string_types[ $substring_type_key ] = $substring_type_value;
                }
            } else {
                $this->string_types[ $string_type_key ] = $string_type_value;
            }
        }

        // Include all classes and hooks needed for Visual Editor
        foreach ( $this->string_types as $string_type_key => $string_type_value ) {
	        if ( $string_type_key == 'emails' || (isset($string_type_value['type']) && $string_type_value['type'] == 'upsale-slugs' ) ) {
				// it's just gettext. We are using it to create an extra tab with this filter
				continue;
	        }

            require_once $string_type_value['plugin_path'] . 'includes/string-translation/class-string-translation-api-' . $string_type_key . '.php';
            $class_name                                 = 'ETM_String_Translation_API_' . $string_type_value['class_name_suffix'];
            $this->string_type_apis[ $string_type_key ] = new $class_name( $this->settings );

            // Different hook for String Translation compared to Visual Editor
            add_action( 'wp_ajax_etm_string_translation_get_strings_' . $string_type_key, array( $this->string_type_apis[ $string_type_key ], 'get_strings' ) );

			if ( $string_type_key == 'gettext' ) {
				add_action( 'wp_ajax_etm_string_translation_get_missing_gettext_strings', array(
					$this->string_type_apis[ 'gettext' ],
					'get_missing_gettext_strings'
				) );
				add_action( 'wp_ajax_etm_string_translation_get_strings_by_original_ids_gettext', array(
					$this->string_type_apis[ 'gettext' ],
					'get_strings_by_original_ids'
				) );
			}

	        // Same hook as for Visual Editor save translations
            add_action( 'wp_ajax_etm_save_translations_' . $string_type_key, array( $this->string_type_apis[ $string_type_key ], 'save_strings' ) );
        }
    }

    public function get_string_types() {
        return $this->string_types;
    }

    public function get_string_type_API( $string_type ) {
        return $this->string_type_apis[ $string_type ];
    }

    /**
     * Start String Translation Editor.
     *
     * Hooked to template_include.
     *
     * @param string $page_template Current page template.
     * @return string                       Template for translation Editor.
     */
    public function string_translation_editor( $page_template ) {
        if ( !$this->is_string_translation_editor() ) {
            return $page_template;
        }

        return ETM_PLUGIN_DIR . 'includes/string-translation/string-translation-editor.php';
    }

    /**
     * Return true if we are on String translation page.
     *
     * Also wp_die and show 'Cheating' message if we are on translation page but user does not have capabilities to view it
     *
     * @return bool
     */
    public function is_string_translation_editor() {
        if ( isset( $_REQUEST['etm-string-translation'] ) && sanitize_text_field( $_REQUEST['etm-string-translation'] ) === 'true' ) {
            if ( current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) && !is_admin() ) {
                return true;
            } else {
                wp_die(
                    '<h1>' . esc_html__( 'Cheatin&#8217; uh?' ) . '</h1>' . //phpcs:ignore
                    '<p>' . esc_html__( 'Sorry, you are not allowed to access this page.' ) . '</p>', //phpcs:ignore
                    403
                );
            }
        }
        return false;
    }

    /**
     * Enqueue script and styles for String Translation Editor page
     *
     * Hooked to etm_string_translation_editor_footer
     */
    public function enqueue_scripts_and_styles() {
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        if ( !$this->translation_manager ) {
            $this->translation_manager = $etm->get_component( 'translation_manager' );
        }


        wp_enqueue_style( 'etm-editor-style', ETM_PLUGIN_URL . 'assets/css/etm-editor.css', array( 'dashicons', 'buttons' ), ETM_PLUGIN_VERSION );
        wp_enqueue_script( 'etm-string-translation-editor', ETM_PLUGIN_URL . 'assets/js/etm-string-translation-editor.js', array(), ETM_PLUGIN_VERSION );

        wp_localize_script( 'etm-string-translation-editor', 'etm_editor_data', $this->translation_manager->get_etm_editor_data() );
        wp_localize_script( 'etm-string-translation-editor', 'etm_string_translation_data', $this->get_string_translation_data() );


        // Show upload media dialog in default language
        switch_to_locale( $this->settings['default-language'] );
        // Necessary for add media button
        wp_enqueue_media();

        // Necessary for add media button
        wp_print_media_templates();
        restore_current_locale();

        // Necessary for translate-dom-changes to have a nonce as the same user as the Editor.
        // The Preview iframe (which loads translate-dom-changes script) can load as logged out which sets an different nonce

        $scripts_to_print = apply_filters( 'etm-scripts-for-editor', array( 'jquery', 'jquery-ui-core', 'jquery-effects-core', 'jquery-ui-resizable', 'etm-string-translation-editor' ) );
        $styles_to_print  = apply_filters( 'etm-styles-for-editor', array( 'dashicons', 'etm-editor-style', 'media-views', 'imgareaselect', 'common', 'forms', 'list-tables', 'buttons' /*'wp-admin', 'common', 'site-icon', 'buttons'*/ ) );
        wp_print_scripts( $scripts_to_print );
        wp_print_styles( $styles_to_print );

        // Necessary for add media button
        print_footer_scripts();

    }

    public function get_string_translation_data() {
        $string_translation_data = array(
            'string_types_config'        => $this->string_types_config(),
            'st_editor_strings'          => $this->get_st_editor_strings(),
            'translation_status_filters' => $this->get_translation_status_filters(),
            'default_actions'            => $this->get_default_actions(),
            'config'                     => $this->get_configuration_options()
        );
        return apply_filters( 'etm_string_translation_data', $string_translation_data );
    }

    public function get_translation_status_filters() {
        $filters = array(
            'translation_status' => array(
                'human_reviewed'     => esc_html__( 'Manually translated', 'etranslation-multilingual' ),
                'machine_translated' => esc_html__( 'Automatically translated', 'etranslation-multilingual' ),
                'not_translated'     => esc_html__( 'Not translated', 'etranslation-multilingual' )
            )

        );
        return apply_filters( 'etm_st_default_filters', $filters );
    }

    public function get_default_actions() {
        $actions = array(
            'bulk_actions' => array(
                'etm_default' => array( 'name' => esc_html__( 'Bulk Actions', 'etranslation-multilingual' ) ),
                'delete'      => array(
                    'name'  => esc_html__( 'Delete entries', 'etranslation-multilingual' ),
                    'nonce' => wp_create_nonce( 'string_translation_save_strings_delete' )
                ),
            ),
            'actions'      => array(
                'edit'   => esc_html__( 'Edit', 'etranslation-multilingual' )
            )
        );
        return apply_filters( 'etm_st_default_actions', $actions );
    }

    public function get_gettext_domains() {
        if ( !$this->gettext_domains ) {
            $etm          = ETM_eTranslation_Multilingual::get_etm_instance();
            $etm_query    = $etm->get_component( 'query' );
            $etm_settings = $etm->get_component( 'settings' );
            $settings     = $etm_settings->get_settings();

            global $wpdb;
            $query = 'SELECT DISTINCT domain FROM `' . $etm_query->get_table_name_for_gettext_original_strings() . '` ORDER BY domain ASC';

            $this->gettext_domains = $wpdb->get_results( $query, OBJECT_K );
            foreach ( $this->gettext_domains as $domain => $value ) {
                $this->gettext_domains[ $domain ] = $domain;
            }
        }

        return $this->gettext_domains;
    }

    public function get_st_editor_strings() {
        $st_editor_strings = array(
	        'translation_status'     => esc_html__( 'Translation Status', 'etranslation-multilingual' ),
	        'filter'                 => esc_html__( 'Filter', 'etranslation-multilingual' ),
	        'clear_filter'           => esc_html__( 'Clear filters', 'etranslation-multilingual' ),
	        'filter_by_language'     => esc_html__( 'Filter by language', 'etranslation-multilingual' ),
	        'add_new'                => esc_html__( 'Add New', 'etranslation-multilingual' ),
	        'rescan_gettext'         => esc_html__( 'Rescan plugins and theme for strings', 'etranslation-multilingual' ),
	        'scanning_gettext'       => esc_html__( 'Scanning plugins and theme for strings...', 'etranslation-multilingual' ),
	        'gettext_scan_completed' => esc_html__( 'Plugins and theme scan is complete', 'etranslation-multilingual' ),
	        'gettext_scan_error'     => esc_html__( 'Plugins and theme scan did not finish due to an error', 'etranslation-multilingual' ),
	        'importexport'           => esc_html__( 'Import / Export', 'etranslation-multilingual' ),
	        'items'                  => esc_html__( 'items', 'etranslation-multilingual' ),
	        'of'                     => esc_html_x( 'of', 'page 1 of 3', 'etranslation-multilingual' ),
	        'see_more'               => esc_html__( 'See More', 'etranslation-multilingual' ),
	        'see_less'               => esc_html__( 'See Less', 'etranslation-multilingual' ),
	        'apply'                  => esc_html__( 'Apply', 'etranslation-multilingual' ),
	        'no_strings_match_query' => esc_html__( 'No strings match your query.', 'etranslation-multilingual' ),
	        'no_strings_match_rescan'=> esc_html__( 'Try to rescan plugins and theme for strings.', 'etranslation-multilingual' ),
	        'request_error'          => esc_html__( 'An error occurred while loading results. Most likely you were logged out. Reload page?', 'etranslation-multilingual' ),

	        'select_all'               => esc_html__( 'Select All', 'etranslation-multilingual' ),
	        'select_visible'           => esc_html__( 'Select Visible', 'etranslation-multilingual' ),
	        'select_all_warning'       => esc_html__( 'You are about to perform this action on all the strings matching your filter, not just the visibly checked. To perform the action only to the visible strings click "Select Visible" from the table header dropdown.', 'etranslation-multilingual' ),
	        'select_visible_warning'   => esc_html__( 'You are about to perform this action only on the visible strings. To perform the action on all the strings matching the filter click "Select All" from the table header dropdown.', 'etranslation-multilingual' ),
	        'type_a_word_for_security' => esc_html__( 'To continue please type the word:', 'etranslation-multilingual' ),
	        'incorect_word_typed'      => esc_html__( 'The word typed was incorrect. Action was cancelled.', 'etranslation-multilingual' ),

	        'in'                         => esc_html_x( 'in', 'Untranslated in this language', 'etranslation-multilingual' ),

	        // specific bulk actions
	        'delete_warning'             => esc_html__( 'Warning: This action cannot be undone. Deleting a string will remove its current translation. The original string will appear again in this interface after eTranslation Multilingual detects it. This action is NOT equivalent to excluding the string from being translated again.', 'etranslation-multilingual' ),

	        // tooltips
	        'next_page'                  => esc_html__( 'Navigate to next page', 'etranslation-multilingual' ),
	        'previous_page'              => esc_html__( 'Navigate to previous page', 'etranslation-multilingual' ),
	        'first_page'                 => esc_html__( 'Navigate to first page', 'etranslation-multilingual' ),
	        'last_page'                  => esc_html__( 'Navigate to last page', 'etranslation-multilingual' ),
	        'navigate_to_page'           => esc_html__( 'Type a page number to navigate to', 'etranslation-multilingual' ),
	        'wrong_page'                 => esc_html__( 'Incorrect page number. Type a page number between 1 and total number of pages', 'etranslation-multilingual' ),
	        'search_tooltip'             => esc_html__( 'Search original strings containing typed keywords while also matching selected filters', 'etranslation-multilingual' ),
	        'filter_tooltip'             => esc_html__( 'Filter strings according to selected translation status, filters and keywords and selected filters', 'etranslation-multilingual' ),
	        'clear_filter_tooltip'       => esc_html__( 'Removes selected filters', 'etranslation-multilingual' ),
	        'select_all_tooltip'         => esc_html__( 'See options for selecting all strings', 'etranslation-multilingual' ),
	        'sort_by_column'             => esc_html__( 'Click to sort strings by this column', 'etranslation-multilingual' ),
	        'filter_by_language_tooltip' => esc_html__( 'Language in which the translation status filter applies. Leave unselected for the translation status to apply to ANY language', 'etranslation-multilingual' ),
        );
        return apply_filters( 'etm_st_editor_strings', $st_editor_strings );
    }

    /**
     * @return mixed
     */
    public function string_types_config() {
	    $string_types_config = array(
		    'gettext' =>
			    array(
					'type'                   => 'gettext',
				    'name'                   => esc_html__( 'Plugins and Theme String Translation', 'etranslation-multilingual' ),
				    'tab_name'               => esc_html__( 'Gettext', 'etranslation-multilingual' ),
				    'search_name'            => esc_html__( 'Search Gettext Strings', 'etranslation-multilingual' ),
				    'class_name_suffix'      => 'Gettext',
//				    'add_new'                => true,
                    'scan_gettext'           => true,
				    'plugin_path'            => ETM_PLUGIN_DIR,
				    'nonces'                 => $this->get_nonces_for_type( 'gettext' ),
				    'table_columns'          => array(
					    'id'         => esc_html__( 'ID', 'etranslation-multilingual' ),
					    'original'   => esc_html__( 'Original String', 'etranslation-multilingual' ),
					    'translated' => esc_html__( 'Translation', 'etranslation-multilingual' ),
					    'domain'     => esc_html__( 'Domain', 'etranslation-multilingual' ),
				    ),
				    'show_original_language' => true,
				    'category_based'         => false,
				    'filters'                => array(
					    'domain' => array_merge(
						    array( 'etm_default' => esc_html__( 'Filter by domain', 'etranslation-multilingual' ) ),
						    $this->get_gettext_domains()
					    ),
					    'type' => array(
							    'etm_default' => esc_html__( 'Filter by type', 'etranslation-multilingual' ),
							    'email'       => esc_html__( 'Email text', 'etranslation-multilingual' )
					    ),
				    )
			    ),
		    'emails' =>
			    array(
				    'type'                   => 'gettext',
				    'name'                   => esc_html__( 'Emails String Translation', 'etranslation-multilingual' ),
				    'tab_name'               => esc_html__( 'Emails', 'etranslation-multilingual' ),
				    'search_name'            => esc_html__( 'Search Email Strings', 'etranslation-multilingual' ),
				    'class_name_suffix'      => 'Gettext',
				    //				    'add_new'                => true,
				    'scan_gettext'           => true,
				    'plugin_path'            => ETM_PLUGIN_DIR,
				    'nonces'                 => $this->get_nonces_for_type( 'gettext' ),
				    'table_columns'          => array(
					    'id'         => esc_html__( 'ID', 'etranslation-multilingual' ),
					    'original'   => esc_html__( 'Original String', 'etranslation-multilingual' ),
					    'translated' => esc_html__( 'Translation', 'etranslation-multilingual' ),
					    'domain'     => esc_html__( 'Domain', 'etranslation-multilingual' ),
				    ),
				    'show_original_language' => true,
				    'category_based'         => false,
				    'filters'                => array(
					    'domain' => array_merge(
						    array( 'etm_default' => esc_html__( 'Filter by domain', 'etranslation-multilingual' ) ),
						    $this->get_gettext_domains()
					    ),
				    )
			    ),
		    'regular' =>
			    array(
				    'type'                   => 'regular',
				    'name'                   => esc_html__( 'User Inputted String Translation', 'etranslation-multilingual' ),
				    'tab_name'               => esc_html__( 'Regular', 'etranslation-multilingual' ),
				    'search_name'            => esc_html__( 'Search Regular Strings', 'etranslation-multilingual' ),
				    'class_name_suffix'      => 'Regular',
				    //				    'add_new'                => true,
				    'plugin_path'            => ETM_PLUGIN_DIR,
				    'nonces'                 => $this->get_nonces_for_type( 'regular' ),
				    'table_columns'          => array(
					    'id'         => esc_html__( 'ID', 'etranslation-multilingual' ),
					    'original'   => esc_html__( 'Original String', 'etranslation-multilingual' ),
					    'translated' => esc_html__( 'Translation', 'etranslation-multilingual' )
				    ),
				    'show_original_language' => false,
				    'category_based'         => false,
				    'filters'                => array(
					    'translation-block-type' => array(
						    'etm_default'       => esc_html__( 'Filter by Translation Block', 'etranslation-multilingual' ),
						    'individual_string' => 'Individual string',
						    'translation_block' => 'Translation Block'
					    )
				    )
			    )
	    );


	    if ( !apply_filters('etm_show_regular_strings_string_translation', false ) ){
	    	unset($string_types_config['regular']);
	    }

        return apply_filters( 'etm_st_string_types_config', $string_types_config, $this );
    }

    public function get_nonces_for_type( $type ) {
        $nonces = array(
            'get_strings'  => wp_create_nonce( 'string_translation_get_strings_' . $type ),
            'get_missing_strings'  => wp_create_nonce( 'string_translation_get_missing_strings_' . $type ),
            'get_strings_by_original_id'  => wp_create_nonce( 'string_translation_get_strings_by_original_ids_' . $type ),
            'save_strings' => wp_create_nonce( 'string_translation_save_strings_' . $type )
        );
        return apply_filters( 'etm_string_translation_nonces', $nonces, $type );
    }

    public function get_configuration_options() {
        $config = array(
            'items_per_page'      => 20,
            'see_more_max_length' => 150
        );
        return apply_filters( 'etm_string_translation_config', $config );
    }

    public function register_string_types( $registered_string_types ) {
        foreach ( $this->string_types as $string_type => $value ) {
            if ( !in_array( $string_type, $registered_string_types ) ) {
                $registered_string_types[] = $string_type;
            }
        }

        return $registered_string_types;
    }

    /*
     * hooked to etm_editor_nonces
     */
    public function add_nonces_for_saving_translation( $nonces ) {
        foreach ( $this->string_types as $string_type => $string_config ) {
            if ( !isset( $nonces[ 'savetranslationsnonce' . $string_type ] ) ) {
                $nonces[ 'savetranslationsnonce' . $string_type ] = $string_config['nonces']['save_strings'];
            }
        }
        return $nonces;
    }

}