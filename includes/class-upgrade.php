<?php

/**
 * Class ETM_Upgrade
 *
 * When changing plugin version, do the necessary checks and database upgrades.
 */
class ETM_Upgrade {

	protected $settings;
	protected $db;
	/* @var ETM_Query */
	protected $etm_query;

	/**
	 * ETM_Upgrade constructor.
	 *
	 * @param $settings
	 */
	public function __construct( $settings ) {
		global $wpdb;
		$this->db       = $wpdb;
		$this->settings = $settings;

	}

	/**
	 * Register Settings subpage for eTranslation Multilingual
	 */
	public function register_menu_page() {
		add_submenu_page( 'ETMHidden', 'eTranslation Multilingual Remove Duplicate Rows', 'ETMHidden', apply_filters( 'etm_settings_capability', 'manage_options' ), 'etm_remove_duplicate_rows', array( $this, 'etm_remove_duplicate_rows' ) );
		add_submenu_page( 'ETMHidden', 'eTranslation Multilingual Update Database', 'ETMHidden', apply_filters( 'etm_settings_capability', 'manage_options' ), 'etm_update_database', array( $this, 'etm_update_database_page' ) );
	}

	/**
	 * When changing plugin version, call certain database upgrade functions.
	 */
	public function check_for_necessary_updates() {
		$etm = ETM_eTranslation_Multilingual::get_etm_instance();
		if ( ! $this->etm_query ) {
			$this->etm_query = $etm->get_component( 'query' );
		}
		$stored_database_version = get_option( 'etm_plugin_version' );
		if ( empty( $stored_database_version ) ) {
			$this->check_if_gettext_tables_exist();
		} else {

			// Updates that require admins to trigger manual update of db because of long duration. Set an option in DB if this is the case.
			$updates = $this->get_updates_details();
			foreach ( $updates as $update ) {
				if ( version_compare( $update['version'], $stored_database_version, '>' ) ) {
					update_option( $update['option_name'], 'no' );
				}
			}

			// Updates that can be done right way. They should take very little time.
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->etm_query->check_for_block_type_column();
				$this->check_if_gettext_tables_exist();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->add_full_text_index_to_tables();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->upgrade_machine_translation_settings();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->etm_query->check_for_original_id_column();
				$this->etm_query->check_original_table();
				$this->etm_query->check_original_meta_table();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->set_force_slash_at_end_of_links();
			}

			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$gettext_normalization = $this->etm_query->get_query_component( 'gettext_normalization' );
				$gettext_normalization->check_for_gettext_original_id_column();

				$gettext_table_creation = $this->etm_query->get_query_component( 'gettext_table_creation' );
				$gettext_table_creation->check_gettext_original_table();
				$gettext_table_creation->check_gettext_original_meta_table();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->add_iso_code_to_language_code();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->create_opposite_ls_option();
			}
			if ( version_compare( $stored_database_version, '1.0.0', '<' ) ) {
				$this->migrate_auto_translate_slug_to_automatic_translation();
			}
			/**
			 * Write an upgrading function above this comment to be executed only once: while updating plugin to a higher version.
			 * Use example condition: version_compare( $stored_database_version, '2.9.9', '<=')
			 * where 2.9.9 is the current version, and 3.0.0 will be the updated version where this code will be launched.
			 */
		}

		// don't update the db version unless they are different. Otherwise the query is run on every page load.
		if ( version_compare( ETM_PLUGIN_VERSION, $stored_database_version, '!=' ) ) {
			update_option( 'etm_plugin_version', ETM_PLUGIN_VERSION );
		}
	}

	public function migrate_auto_translate_slug_to_automatic_translation() {
		$option             = get_option( 'etm_advanced_settings', true );
		$mt_settings_option = get_option( 'etm_machine_translation_settings' );
		if ( ! isset( $mt_settings_option['automatically-translate-slug'] ) ) {
			if ( ! isset( $option['enable_auto_translate_slug'] ) || $option['enable_auto_translate_slug'] == '' || $option['enable_auto_translate_slug'] == 'no' ) {
				$mt_settings_option['automatically-translate-slug'] = 'no';
			} else {
				$mt_settings_option['automatically-translate-slug'] = 'yes';
			}
			update_option( 'etm_machine_translation_settings', $mt_settings_option );
		}
	}

	/**
	 * Iterates over all languages to call gettext table checking
	 */
	public function check_if_gettext_tables_exist() {
		$etm = ETM_eTranslation_Multilingual::get_etm_instance();
		if ( ! $this->etm_query ) {
			$this->etm_query = $etm->get_component( 'query' );
		}
		$gettext_table_creation = $this->etm_query->get_query_component( 'gettext_table_creation' );
		if ( ! empty( $this->settings['translation-languages'] ) ) {
			foreach ( $this->settings['translation-languages'] as $site_language_code ) {
				$gettext_table_creation->check_gettext_table( $site_language_code );
			}
		}
		$gettext_table_creation->check_gettext_original_table();
		$gettext_table_creation->check_gettext_original_meta_table();
	}

	public function get_updates_details() {
		return apply_filters(
			'etm_updates_details',
			array(
				'remove_cdata_original_and_dictionary_rows' => array(
					'version'            => '0',
					'option_name'        => 'etm_remove_cdata_original_and_dictionary_rows',
					'callback'           => array( $this->etm_query, 'remove_cdata_in_original_and_dictionary_tables' ),
					'batch_size'         => 1000,
					'message_initial'    => '',
					'message_processing' => __( 'Removing cdata dictionary strings for language %s...', 'etranslation-multilingual' ),
				),
				'remove_untranslated_links_dictionary_rows' => array(
					'version'            => '0',
					'option_name'        => 'etm_remove_untranslated_links_dictionary_rows',
					'callback'           => array( $this->etm_query, 'remove_untranslated_links_in_dictionary_table' ),
					'batch_size'         => 10000,
					'message_initial'    => '',
					'message_processing' => __( 'Removing untranslated dictionary links for language %s...', 'etranslation-multilingual' ),
				),
				'full_trim_originals_140'          => array(
					'version'     => '0',
					'option_name' => 'etm_updated_database_full_trim_originals_140',
					'callback'    => array( $this, 'etm_updated_database_full_trim_originals_140' ),
					'batch_size'  => 200,
				),
				'gettext_empty_rows_145'           => array(
					'version'     => '0',
					'option_name' => 'etm_updated_database_gettext_empty_rows_145',
					'callback'    => array( $this, 'etm_updated_database_gettext_empty_rows_145' ),
					'batch_size'  => 20000,
				),
				'remove_duplicate_gettext_rows'    => array(
					'version'            => '0',
					'option_name'        => 'etm_remove_duplicate_gettext_rows',
					'callback'           => array( $this->etm_query, 'remove_duplicate_rows_in_gettext_table' ),
					'batch_size'         => 10000,
					'message_initial'    => '',
					'message_processing' => __( 'Removing duplicated gettext strings for language %s...', 'etranslation-multilingual' ),
				),
				'remove_duplicate_untranslated_gettext_rows' => array(
					'version'            => '0',
					'option_name'        => 'etm_remove_duplicate_untranslated_gettext_rows',
					'callback'           => array( $this->etm_query, 'remove_untranslated_strings_if_gettext_translation_available' ),
					'batch_size'         => 10000,
					'message_initial'    => '',
					'message_processing' => __( 'Removing untranslated gettext strings where translation is available for language %s...', 'etranslation-multilingual' ),
				),
				'remove_duplicate_dictionary_rows' => array(
					'version'            => '0',
					'option_name'        => 'etm_remove_duplicate_dictionary_rows',
					'callback'           => array( $this->etm_query, 'remove_duplicate_rows_in_dictionary_table' ),
					'batch_size'         => 1000,
					'message_initial'    => '',
					'message_processing' => __( 'Removing duplicated dictionary strings for language %s...', 'etranslation-multilingual' ),
				),
				'remove_duplicate_untranslated_dictionary_rows' => array(
					'version'            => '0',
					'option_name'        => 'etm_remove_duplicate_untranslated_dictionary_rows',
					'callback'           => array( $this->etm_query, 'remove_untranslated_strings_if_translation_available' ),
					'batch_size'         => 10000,
					'message_initial'    => '',
					'message_processing' => __( 'Removing untranslated dictionary strings where translation is available for language %s...', 'etranslation-multilingual' ),
				),
				'original_id_insert_166'           => array(
					'version'            => '0',
					'option_name'        => 'etm_updated_database_original_id_insert_166',
					'callback'           => array( $this, 'etm_updated_database_original_id_insert_166' ),
					'batch_size'         => 1000,
					'message_processing' => __( 'Inserting original strings for language %s...', 'etranslation-multilingual' ),
				),
				'original_id_cleanup_166'          => array(
					'version'            => '0',
					'option_name'        => 'etm_updated_database_original_id_cleanup_166',
					'callback'           => array( $this, 'etm_updated_database_original_id_cleanup_166' ),
					'progress_message'   => 'clean',
					'batch_size'         => 1000,
					'message_initial'    => '',
					'message_processing' => __( 'Cleaning original strings table for language %s...', 'etranslation-multilingual' ),
				),
				'original_id_update_166'           => array(
					'version'            => '0',
					'option_name'        => 'etm_updated_database_original_id_update_166',
					'callback'           => array( $this, 'etm_updated_database_original_id_update_166' ),
					'batch_size'         => 5000,
					'message_initial'    => '',
					'message_processing' => __( 'Updating original string ids for language %s...', 'etranslation-multilingual' ),
				),
				'regenerate_original_meta'         => array(
					'version'            => '0', // independent of etm version, available only on demand
					'option_name'        => 'etm_regenerate_original_meta_table',
					'callback'           => array( $this, 'etm_regenerate_original_meta_table' ),
					'batch_size'         => 200,
					'message_initial'    => '',
					'message_processing' => __( 'Regenerating original meta table for language %s...', 'etranslation-multilingual' ),
				),
				'clean_original_meta'              => array(
					'version'            => '0', // independent of etm version, available only on demand
					'option_name'        => 'etm_clean_original_meta_table',
					'callback'           => array( $this, 'etm_clean_original_meta_table' ),
					'batch_size'         => 20000,
					'message_initial'    => '',
					'message_processing' => __( 'Cleaning original meta table for language %s...', 'etranslation-multilingual' ),
				),
				'gettext_original_id_insert'       => array(
					'version'            => '0',
					'option_name'        => 'etm_updated_database_gettext_original_id_insert',
					'callback'           => array( $this, 'etm_updated_database_gettext_original_id_insert' ),
					'batch_size'         => 1000,
					'message_processing' => __( 'Inserting gettext original strings for language %s...', 'etranslation-multilingual' ),
				),
				'gettext_original_id_cleanup'      => array(
					'version'            => '0',
					'option_name'        => 'etm_updated_database_gettext_original_id_cleanup',
					'callback'           => array( $this, 'etm_updated_database_gettext_original_id_cleanup' ),
					'progress_message'   => 'clean',
					'batch_size'         => 1000,
					'message_initial'    => '',
					'message_processing' => __( 'Cleaning gettext original strings table for language %s...', 'etranslation-multilingual' ),
				),
				'gettext_original_id_update'       => array(
					'version'            => '0',
					'option_name'        => 'etm_updated_database_gettext_original_id_update',
					'callback'           => array( $this, 'etm_updated_database_gettext_original_id_update' ),
					'batch_size'         => 5000,
					'message_initial'    => '',
					'message_processing' => __( 'Updating gettext original string ids for language %s...', 'etranslation-multilingual' ),
				),
				'show_error_db_message'            => array(
					'version'            => '0', // independent of etm version, available only on demand
					'option_name'        => 'etm_show_error_db_message',
					'callback'           => array( $this, 'etm_successfully_run_database_optimization' ),
					'batch_size'         => 10,
					'message_initial'    => '',
					'message_processing' => __( 'Finishing up...', 'etranslation-multilingual' ),
					'execute_only_once'  => true,
				),
			)
		);
		/**
		 * Write 3.0.0 if 2.9.9 is the current version, and 3.0.0 will be the updated version where this code will be launched.
		 */
	}

	/**
	 * Show admin notice about updating database
	 */
	public function show_admin_notice() {
		$notifications = ETM_Plugin_Notifications::get_instance();
		if ( $notifications->is_plugin_page() || ( isset( $GLOBALS['PHP_SELF'] ) && ( $GLOBALS['PHP_SELF'] === '/wp-admin/index.php' || $GLOBALS['PHP_SELF'] === '/wp-admin/plugins.php' ) ) ) {
			if ( ( isset( $_GET['page'] ) && $_GET['page'] == 'etm_update_database' ) ) {
				return;
			}
			$updates_needed          = $this->get_updates_details();
			$option_db_error_message = get_option( $updates_needed['show_error_db_message']['option_name'] );
			foreach ( $updates_needed as $update ) {
				$option = get_option( $update['option_name'], 'is not set' );
				if ( $option === 'no' && $option_db_error_message !== 'no' ) {
					add_action( 'admin_notices', array( $this, 'admin_notice_update_database' ) );
					break;
				}
			}
		}
	}

	/**
	 * Print admin notice message
	 */
	public function admin_notice_update_database() {

		$url = add_query_arg(
			array(
				'page' => 'etm_update_database',
			),
			site_url( 'wp-admin/admin.php' )
		);

		// maybe change notice color to blue #28B1FF
		$html  = '<div id="message" class="updated">';
		$html .= '<p><strong>' . esc_html__( 'eTranslation Multilingual data update', 'etranslation-multilingual' ) . '</strong> &#8211; ' . esc_html__( 'We need to update your translations database to the latest version.', 'etranslation-multilingual' ) . '</p>';
		$html .= '<p class="submit"><a href="' . esc_url( $url ) . '" onclick="return confirm( \'' . __( 'IMPORTANT: It is strongly recommended to first backup the database!\nAre you sure you want to continue?', 'etranslation-multilingual' ) . '\');" class="button-primary">' . esc_html__( 'Run the updater', 'etranslation-multilingual' ) . '</a></p>';
		$html .= '</div>';
		escape_and_echo_html( $html );
	}

	public function etm_successfully_run_database_optimization( $language_code = null, $inferior_size = null, $batch_size = null ) {
		delete_option( 'etm_show_error_db_message' );

		return true;
	}


	public function show_admin_error_message() {
		if ( ( isset( $_GET['page'] ) && $_GET['page'] == 'etm_update_database' ) ) {
			return;
		}
		$updates_needed          = $this->get_updates_details();
		$option_db_error_message = get_option( $updates_needed['show_error_db_message']['option_name'] );
		if ( $option_db_error_message === 'no' ) {
			add_action( 'admin_notices', array( $this, 'etm_admin_notice_error_database' ) );
		}

	}

	public function etm_admin_notice_error_database() {

		echo '<div class="notice notice-error is-dismissible">
            <p>' . wp_kses( sprintf( __( 'Database optimization did not complete successfully. We recommend restoring the original database or <a href="%s" >trying again.</a>', 'etranslation-multilingual' ), admin_url( 'admin.php?page=etm_update_database' ) ), array( 'a' => array( 'href' => array() ) ) ) . '</p>
        </div>';

	}

	public function etm_update_database_page() {
		require_once ETM_PLUGIN_DIR . 'partials/etm-update-database.php';
	}



	/**
	 * Call all functions to update database
	 *
	 * hooked to wp_ajax_etm_update_database
	 */
	public function etm_update_database() {
		if ( ! current_user_can( apply_filters( 'etm_update_database_capability', 'manage_options' ) ) ) {
			$this->stop_and_print_error( __( 'Update aborted! Your user account doesn\'t have the capability to perform database updates.', 'etranslation-multilingual' ) );
		}

		$nonce = isset( $_REQUEST['etm_updb_nonce'] ) ? wp_verify_nonce( sanitize_text_field( $_REQUEST['etm_updb_nonce'] ), 'etmupdatedatabase' ) : false;
		if ( $nonce === false ) {
			$this->stop_and_print_error( __( 'Update aborted! Invalid nonce.', 'etranslation-multilingual' ) );
		}

		$request                     = array();
		$request['progress_message'] = '';
		$updates_needed              = $this->get_updates_details();
		if ( isset( $_REQUEST['initiate_update'] ) && $_REQUEST['initiate_update'] === 'true' ) {
			update_option( 'etm_show_error_db_message', 'no' );
		}
		if ( empty( $_REQUEST['etm_updb_action'] ) ) {
			foreach ( $updates_needed as $update_action_key => $update ) {
				$option = get_option( $update['option_name'], 'is not set' );
				if ( $option === 'no' ) {
					$_REQUEST['etm_updb_action'] = $update_action_key;
					break;
				}
			}
			if ( empty( $_REQUEST['etm_updb_action'] ) ) {
				$back_to_settings_button = '<p><a href="' . site_url( 'wp-admin/options-general.php?page=etranslation-multilingual' ) . '"> <input type="button" value="' . esc_html__( 'Back to eTranslation Multilingual Settings', 'etranslation-multilingual' ) . '" class="button-primary"></a></p>';
				// finished successfully
				emt_safe_json_send(
					array(
						'etm_update_completed' => 'yes',
						'progress_message'     => '<p><strong>' . __( 'Successfully updated database!', 'etranslation-multilingual' ) . '</strong></p>' . $back_to_settings_button,
					)
				);
			} else {
				$_REQUEST['etm_updb_lang']  = $this->settings['translation-languages'][0];
				$_REQUEST['etm_updb_batch'] = 0;

				$update_message_initial = isset( $updates_needed[ $_REQUEST['etm_updb_action'] ]['message_initial'] ) ?
											$updates_needed[ sanitize_text_field( $_REQUEST['etm_updb_action'] ) ]['message_initial']
											: __( 'Updating database to version %s+', 'etranslation-multilingual' );

				$update_message_processing = isset( $updates_needed[ $_REQUEST['etm_updb_action'] ]['message_processing'] ) ?
												$updates_needed[ sanitize_text_field( $_REQUEST['etm_updb_action'] ) ]['message_processing']
												: __( 'Processing table for language %s...', 'etranslation-multilingual' );

				if ( $updates_needed[ sanitize_text_field( $_REQUEST['etm_updb_action'] ) ]['version'] != 0 ) {
					$request['progress_message'] .= '<p>' . sprintf( $update_message_initial, $updates_needed[ sanitize_text_field( $_REQUEST['etm_updb_action'] ) ]['version'] ) . '</p>';
				}
				$request['progress_message'] .= '<br>' . sprintf( $update_message_processing, sanitize_text_field( wp_unslash( $_REQUEST['etm_updb_lang'] ) ) );
			}
		} else {
			if ( ! isset( $updates_needed[ $_REQUEST['etm_updb_action'] ] ) ) {
				$this->stop_and_print_error( __( 'Update aborted! Incorrect action.', 'etranslation-multilingual' ) );
			}
			if ( !in_array( $_REQUEST['etm_updb_lang'], $this->settings['translation-languages'] ) ) {//phpcs:ignore
				$this->stop_and_print_error( __( 'Update aborted! Incorrect language code.', 'etranslation-multilingual' ) );
			}
		}

		$request['etm_updb_action'] = sanitize_text_field( $_REQUEST['etm_updb_action'] );
		if ( ! empty( $_REQUEST['etm_updb_batch'] ) && (int) $_REQUEST['etm_updb_batch'] > 0 ) {
			$get_batch = (int) $_REQUEST['etm_updb_batch'];
		} else {
			$get_batch = 0;
		}

		$request['etm_updb_batch'] = 0;
		$update_details            = $updates_needed[ sanitize_text_field( $_REQUEST['etm_updb_action'] ) ];
		$batch_size                = apply_filters( 'etm_updb_batch_size', $update_details['batch_size'], sanitize_text_field( $_REQUEST['etm_updb_action'] ), $update_details );
		$language_code             = isset( $_REQUEST['etm_updb_lang'] ) ? sanitize_text_field( $_REQUEST['etm_updb_lang'] ) : '';

		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}

		$start_time = microtime( true );
		$duration   = 0;
		while ( $duration < 2 ) {
			$inferior_limit         = $batch_size * $get_batch;
			$finished_with_language = call_user_func( $update_details['callback'], $language_code, $inferior_limit, $batch_size );

			if ( $finished_with_language ) {
				break;
			} else {
				$get_batch = $get_batch + 1;
			}
			$stop_time = microtime( true );
			$duration  = $stop_time - $start_time;
		}
		if ( ! $finished_with_language ) {
			$request['etm_updb_batch'] = $get_batch;
		}

		if ( $finished_with_language ) {
			// finished with the current language
			$index = array_search( $language_code, $this->settings['translation-languages'] );

			if ( isset( $this->settings['translation-languages'][ $index + 1 ] ) && ( ! isset( $update_details['execute_only_once'] ) || $update_details['execute_only_once'] == false ) ) {
					// next language code in array
					$request['etm_updb_lang']     = $this->settings['translation-languages'][ $index + 1 ];
					$request['progress_message'] .= __( ' done.', 'etranslation-multilingual' ) . '</br>';
					$update_message_processing    = isset( $updates_needed[ $_REQUEST['etm_updb_action'] ]['message_processing'] ) ?
						$updates_needed[ sanitize_text_field( $_REQUEST['etm_updb_action'] ) ]['message_processing']
						: __( 'Processing table for language %s...', 'etranslation-multilingual' );
					$request['progress_message'] .= '</br>' . sprintf( $update_message_processing, $request['etm_updb_lang'] );

			} else {
				// finish action due to completing all the translation languages
				$request['progress_message'] .= __( ' done.', 'etranslation-multilingual' ) . '</br>';
				$request['etm_updb_lang']     = '';
				// this will stop showing the admin notice
				update_option( $update_details['option_name'], 'yes' );
				$request['etm_updb_action'] = '';
			}
		} else {
			$request['etm_updb_lang']    = $language_code;
			$request['progress_message'] = '.';
		}

		if ( $this->db->last_error != '' ) {
			$request['progress_message'] = '<p><strong>SQL Error:</strong> ' . esc_html( $this->db->last_error ) . '</p>' . $request['progress_message'];
		}
		$query_arguments = array(
			'action'               => 'etm_update_database',
			'etm_updb_action'      => $request['etm_updb_action'],
			'etm_updb_lang'        => $request['etm_updb_lang'],
			'etm_updb_batch'       => $request['etm_updb_batch'],
			'etm_updb_nonce'       => wp_create_nonce( 'etmupdatedatabase' ),
			'etm_update_completed' => 'no',
			'progress_message'     => $request['progress_message'],
		);
		echo( json_encode( $query_arguments ) );
		wp_die();
	}

	public function stop_and_print_error( $error_message ) {
		$back_to_settings_button = '<p><a href="' . site_url( 'wp-admin/options-general.php?page=etranslation-multilingual' ) . '"> <input type="button" value="' . __( 'Back to eTranslation Multilingual Settings', 'etranslation-multilingual' ) . '" class="button-primary"></a></p>';
		$query_arguments         = array(
			'etm_update_completed' => 'yes',
			'progress_message'     => '<p><strong>' . $error_message . '</strong></strong></p>' . $back_to_settings_button,
		);
		echo( json_encode( $query_arguments ) );
		wp_die();
	}

	/**
	 * Get all originals from the table, trim them and update originals back into table
	 *
	 * @param string $language_code     Language code of the table
	 * @param int    $inferior_limit       Omit first X rows
	 * @param int    $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function etm_updated_database_full_trim_originals_140( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}
		if ( $language_code == $this->settings['default-language'] ) {
			// default language doesn't have a dictionary table
			return true;
		}
		$strings = $this->etm_query->get_rows_from_location( $language_code, $inferior_limit, $batch_size, array( 'id', 'original' ) );
		if ( count( $strings ) == 0 ) {
			return true;
		}
		foreach ( $strings as $key => $string ) {
			$strings[ $key ]['original'] = etm_full_trim( $strings[ $key ]['original'] );
		}

		// overwrite original only
		$this->etm_query->update_strings( $strings, $language_code, array( 'id', 'original' ) );
		return false;
	}

	/**
	 * Delete all empty gettext rows
	 *
	 * @param string $language_code     Language code of the table
	 * @param int    $inferior_limit       Omit first X rows
	 * @param int    $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function etm_updated_database_gettext_empty_rows_145( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}
		$rows_affected = $this->etm_query->delete_empty_gettext_strings( $language_code, $batch_size );
		if ( $rows_affected > 0 ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Normalize original ids for all dictionary entries
	 *
	 * @param string $language_code     Language code of the table
	 * @param int    $inferior_limit       Omit first X rows
	 * @param int    $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function etm_updated_database_original_id_insert_166( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}

		$rows_inserted = $this->etm_query->original_ids_insert( $language_code, $inferior_limit, $batch_size );

		if ( $rows_inserted > 0 ) {
			return false;
		} else {
			return true;
		}
	}

	public function etm_updated_database_original_id_cleanup_166( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}

		$this->etm_query->original_ids_cleanup();

		return true;
	}

	/**
	 * Normalize original ids for all dictionary entries
	 *
	 * @param string $language_code     Language code of the table
	 * @param int    $inferior_limit       Omit first X rows
	 * @param int    $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function etm_updated_database_original_id_update_166( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}

		$rows_updated = $this->etm_query->original_ids_reindex( $language_code, $inferior_limit, $batch_size );

		if ( $rows_updated > 0 ) {
			return false;
		} else {
			return true;
		}
	}


	public function etm_prepare_options_for_database_optimization() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$redirect = false;

		if ( isset( $_GET['etm_rm_duplicates_gettext'] ) ) {
			update_option( 'etm_remove_duplicate_gettext_rows', 'no' );
			update_option( 'etm_remove_duplicate_untranslated_gettext_rows', 'no' );
			$redirect = true;
		}

		if ( isset( $_GET['etm_rm_duplicates_dictionary'] ) ) {
			update_option( 'etm_remove_duplicate_dictionary_rows', 'no' );
			update_option( 'etm_remove_duplicate_untranslated_dictionary_rows', 'no' );
			$redirect = true;
		}

		if ( isset( $_GET['etm_rm_duplicates_original_strings'] ) ) {
			$this->etm_remove_duplicate_original_strings();
			$redirect = true;
		}

		if ( isset( $_GET['etm_rm_cdata_original_and_dictionary'] ) ) {
			update_option( 'etm_remove_cdata_original_and_dictionary_rows', 'no' );
			$redirect = true;
		}

		if ( isset( $_GET['etm_rm_untranslated_links'] ) ) {
			update_option( 'etm_remove_untranslated_links_dictionary_rows', 'no' );
			$redirect = true;
		}

		if ( $redirect ) {
			$url = add_query_arg( array( 'page' => 'etm_update_database' ), site_url( 'wp-admin/admin.php' ) );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Remove duplicate rows from DB for etm_dictionary tables.
	 * Removes untranslated strings if there is a translated version.
	 *
	 * Iterates over languages. Each language is iterated in batches of 10 000
	 *
	 * Not accessible from anywhere else
	 * http://example.com/wp-admin/admin.php?page=etm_remove_duplicate_rows
	 */
	public function etm_remove_duplicate_rows() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// prepare page structure

		require_once ETM_PLUGIN_DIR . 'partials/etm-remove-duplicate-rows.php';
	}

	public function enqueue_update_script( $hook ) {
		if ( $hook === 'admin_page_etm_update_database' ) {
			wp_enqueue_script(
				'etm-update-database',
				ETM_PLUGIN_URL . 'assets/js/etm-update-database.js',
				array(
					'jquery',
				),
				ETM_PLUGIN_VERSION
			);
		}

		wp_localize_script(
			'etm-update-database',
			'etm_updb_localized ',
			array(
				'admin_ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'etmupdatedatabase' ),
			)
		);
	}

	/**
	 * Add full text index on the dictionary and gettext tables.
	 * Gets executed once after update.
	 */
	private function add_full_text_index_to_tables() {
		$table_names         = $this->etm_query->get_all_table_names( '', array() );
		$gettext_table_names = $this->etm_query->get_all_gettext_table_names();

		foreach ( array_merge( $table_names, $gettext_table_names ) as $table_name ) {
			$possible_index = "SHOW INDEX FROM {$table_name} WHERE Key_name = 'original_fulltext';";
			if ( $this->db->query( $possible_index ) === 1 ) {
				continue;
			};

			$sql_index = 'CREATE FULLTEXT INDEX original_fulltext ON `' . $table_name . '`(original);';
			$this->db->query( $sql_index );
		}
	}

	/**
	 * Moving some settings from etm_settings option to etm_machine_translation_settings
	 */
	private function upgrade_machine_translation_settings() {
		$etm                          = ETM_eTranslation_Multilingual::get_etm_instance();
		$etm_settings                 = $etm->get_component( 'settings' );
		$machine_translation_settings = get_option( 'etm_machine_translation_settings', false );

		$default_machine_translation_settings = $etm_settings->get_default_etm_machine_translation_settings();

		if ( $machine_translation_settings == false ) {
			$machine_translation_settings = $default_machine_translation_settings;
			// move the old API key option
			if ( ! empty( $this->settings['g-translate-key'] ) ) {
				$machine_translation_settings['google-translate-key'] = $this->settings['g-translate-key'];
			}

			// enable machine translation if it was activated before
			if ( ! empty( $this->settings['g-translate'] ) && $this->settings['g-translate'] == 'yes' ) {
				$machine_translation_settings['machine-translation'] = 'yes';
			}
			update_option( 'etm_machine_translation_settings', $machine_translation_settings );
		} else {
			$machine_translation_settings = array_merge( $default_machine_translation_settings, $machine_translation_settings );
			update_option( 'etm_machine_translation_settings', $machine_translation_settings );
		}
	}

	/**
	 *
	 */
	private function set_force_slash_at_end_of_links() {
		$etm          = ETM_eTranslation_Multilingual::get_etm_instance();
		$etm_settings = $etm->get_component( 'settings' );
		$settings     = $etm_settings->get_settings();

		if ( ! empty( $settings['etm_advanced_settings'] ) && ! isset( $settings['etm_advanced_settings']['force_slash_at_end_of_links'] ) ) {
			$advanced_settings                                = $settings['etm_advanced_settings'];
			$advanced_settings['force_slash_at_end_of_links'] = 'yes';
			update_option( 'etm_advanced_settings', $advanced_settings );
		}

	}

	public function add_iso_code_to_language_code() {
		$etm          = ETM_eTranslation_Multilingual::get_etm_instance();
		$etm_settings = $etm->get_component( 'settings' );
		$settings     = $etm_settings->get_settings();

		if ( isset( $settings['etm_advanced_settings'] ) && isset( $settings['etm_advanced_settings']['custom_language'] ) ) {
			$advanced_settings = $settings['etm_advanced_settings'];
			if ( ! isset( $advanced_settings['custom_language']['cuslangcode'] ) ) {
				$advanced_settings['custom_language']['cuslangcode'] = $advanced_settings['custom_language']['cuslangiso'];
			}
			update_option( 'etm_advanced_settings', $advanced_settings );
		}
	}

	public function create_opposite_ls_option() {

		add_filter( 'wp_loaded', array( $this, 'call_create_menu_entries' ) );
	}

	public function call_create_menu_entries() {
		$etm          = ETM_eTranslation_Multilingual::get_etm_instance();
		$etm_settings = $etm->get_component( 'settings' );
		$settings     = $etm_settings->get_settings();

		$etm_settings->create_menu_entries( $settings['publish-languages'] );
	}

	public function etm_remove_duplicate_original_strings() {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}
		$this->etm_query->rename_originals_table();
		$this->etm_query->check_original_table();

		update_option( 'etm_updated_database_original_id_insert_166', 'no' );
		update_option( 'etm_updated_database_original_id_cleanup_166', 'no' );
		update_option( 'etm_updated_database_original_id_update_166', 'no' );

		update_option( 'etm_regenerate_original_meta_table', 'no' );
		update_option( 'etm_clean_original_meta_table', 'no' );

	}

	public function etm_regenerate_original_meta_table( $language_code, $inferior_limit, $batch_size ) {

		if ( $language_code != $this->settings['default-language'] ) {
			// perform regeneration of original meta table only once
			return true;
		}
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}
		$this->etm_query->regenerate_original_meta_table( $inferior_limit, $batch_size );

		$last_id = $this->db->get_var( 'SELECT MAX(meta_id) FROM ' . $this->etm_query->get_table_name_for_original_meta() );
		if ( $last_id < $inferior_limit ) {
			// reached end of table
			return true;
		} else {
			// not done. get another batch
			return false;
		}
	}

	public function etm_clean_original_meta_table( $language_code, $inferior_limit, $batch_size ) {
		if ( $language_code != $this->settings['default-language'] ) {
			// perform regeneration of original meta table only once
			return true;
		}
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}
		$rows_affected = $this->etm_query->clean_original_meta( $batch_size );
		if ( $rows_affected > 0 ) {
			return false;
		} else {
			$old_originals_table = get_option( 'etm_original_strings_table_for_recovery', '' );
			if ( ! empty( $old_originals_table ) && strpos( $old_originals_table, 'etm_original_strings1' ) !== false ) {
				delete_option( 'etm_original_strings_table_for_recovery' );
				$this->etm_query->drop_table( $old_originals_table );
			}
			return true;
		}
	}

	/**
	 * Normalize original ids for all gettext entries
	 *
	 * @param string $language_code     Language code of the table
	 * @param int    $inferior_limit       Omit first X rows
	 * @param int    $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function etm_updated_database_gettext_original_id_insert( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}
		$gettext_normalization = $this->etm_query->get_query_component( 'gettext_normalization' );
		$rows_inserted         = $gettext_normalization->gettext_original_ids_insert( $language_code, $inferior_limit, $batch_size );
		$last_id               = $this->etm_query->get_last_id( $this->etm_query->get_gettext_table_name( $language_code ) );

		if ( $inferior_limit + $batch_size <= $last_id ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Removes possible duplicates from within gettext_original_strings table
	 *
	 * @param $language_code
	 * @param $inferior_limit
	 * @param $batch_size
	 * @return bool
	 */
	public function etm_updated_database_gettext_original_id_cleanup( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}

		$gettext_normalization = $this->etm_query->get_query_component( 'gettext_normalization' );
		$gettext_normalization->gettext_original_ids_cleanup();

		return true;
	}

	/**
	 * Normalize original ids for all gettext entries
	 *
	 * @param string $language_code     Language code of the table
	 * @param int    $inferior_limit       Omit first X rows
	 * @param int    $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function etm_updated_database_gettext_original_id_update( $language_code, $inferior_limit, $batch_size ) {
		if ( ! $this->etm_query ) {
			$etm = ETM_eTranslation_Multilingual::get_etm_instance();
			/* @var ETM_Query */
			$this->etm_query = $etm->get_component( 'query' );
		}

		$gettext_normalization = $this->etm_query->get_query_component( 'gettext_normalization' );
		$rows_updated          = $gettext_normalization->gettext_original_ids_reindex( $language_code, $inferior_limit, $batch_size );

		if ( $rows_updated > 0 ) {
			return false;
		} else {
			return true;
		}
	}

}
