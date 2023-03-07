<?php

class ETM_Editor_Api_Gettext_Strings {

	/* @var ETM_Query */
	protected $etm_query;
	/* @var ETM_SP_Slug_Manager*/
	protected $slug_manager;
	/* @var ETM_Translation_Render */
	protected $translation_render;
	/* @var ETM_Translation_Manager */
	protected $translation_manager;

	/**
	 * ETM_Translation_Manager constructor.
	 *
	 * @param array $settings       Settings option.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Hooked to wp_ajax_etm_get_translations_gettext
	 */
	public function gettext_get_translations() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_get_translations_gettext' && ! empty( $_POST['string_ids'] ) && ! empty( $_POST['language'] ) && in_array( $_POST['language'], $this->settings['translation-languages'] ) ) {
				check_ajax_referer( 'gettext_get_translations', 'security' );

				$gettext_string_ids = array();
				if ( ! empty( $_POST['string_ids'] ) ) {
					$gettext_string_ids = json_decode( wp_kses_post( wp_unslash( $_POST['string_ids'] ) ) );
				}

				$current_language = sanitize_text_field( wp_unslash( $_POST['language'] ) );
				$dictionaries     = array();

				if ( is_array( $gettext_string_ids ) && etm_is_valid_language_code( $current_language ) ) {

					$etm = ETM_eTranslation_Multilingual::get_etm_instance();
					if ( ! $this->etm_query ) {
						$this->etm_query = $etm->get_component( 'query' );
					}
					if ( ! $this->translation_manager ) {
						$this->translation_manager = $etm->get_component( 'translation_manager' );
					}

					$dictionaries[ $current_language ] = $this->etm_query->get_gettext_string_rows_by_ids( $gettext_string_ids, $current_language );

					/* build the original id array */
					$original_ids = array();
					if ( ! empty( $dictionaries[ $current_language ] ) ) {
						foreach ( $dictionaries[ $current_language ] as $current_language_string ) {
							/* searching by original id */
							$original_ids[] = (int) $current_language_string['ot_id'];
						}
					}
					emt_safe_json_send(
						array(
							'originalIds' => $original_ids,
						)
					);

				}
			}
		}
		wp_die();
	}


	/*
	 * Save gettext translations
	 */
	public function gettext_save_translations() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) ) {
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_save_translations_gettext' && ! empty( $_POST['strings'] ) ) {
				check_ajax_referer( 'gettext_save_translations', 'security' );
				$strings        = sanitize_decode_json_html_recursively( 'strings' );
				// validate input.
				$string_keys = array_keys( get_object_vars( $strings ) );
				if ( array_intersect( $string_keys, $this->settings['translation-languages'] ) !== $string_keys ) {
					// $strings contain some key not present in selected language codes
					wp_die();
				}
				$update_strings = array();
				foreach ( $strings as $language => $language_strings ) {
					if ( in_array( $language, $this->settings['translation-languages'] ) ) {
						$update_strings[ $language ] = array();
						foreach ( $language_strings as $string ) {
							if ( isset( $string->id ) && is_numeric( $string->id ) ) {
								array_push(
									$update_strings[ $language ],
									array(
										'id'          => (int) $string->id,
										'original'    => etm_sanitize_string( $string->original, false ),
										'translated'  => etm_sanitize_string( $string->translated ),
										'domain'      => sanitize_text_field( $string->domain ),
										'status'      => (int) $string->status,
										'plural_form' => (int) $string->plural_form,
										'context'     => $string->context,
									)
								);
							}
						}
					}
				}

				if ( ! $this->etm_query ) {
					$etm             = ETM_eTranslation_Multilingual::get_etm_instance();
					$this->etm_query = $etm->get_component( 'query' );
				}

				foreach ( $update_strings as $language => $update_string_array ) {
					$gettext_insert_update = $this->etm_query->get_query_component( 'gettext_insert_update' );
					$gettext_insert_update->update_gettext_strings( $update_string_array, $language, array( 'id', 'translated', 'status' ) );
					$this->etm_query->remove_possible_duplicates( $update_string_array, $language, 'gettext' );
				}

				do_action( 'etm_save_editor_translations_gettext_strings', $update_strings, $this->settings );
			}
		}
		emt_safe_json_send( $update_strings );
	}
}
