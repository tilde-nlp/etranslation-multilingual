<?php

class ETM_Editor_Api_Regular_Strings {

	/* @var ETM_Query */
	protected $etm_query;
	/* @var ETM_Translation_Render */
	protected $translation_render;
	/* @var ETM_Translation_Manager */
	protected $translation_manager;
	/* @var ETM_Url_Converter */
	protected $url_converter;

	/**
	 * ETM_Translation_Manager constructor.
	 *
	 * @param array $settings       Settings option.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns translations based on original strings and ids.
	 *
	 * Hooked to wp_ajax_etm_get_translations_regular
	 *       and wp_ajax_nopriv_etm_get_translations_regular.
	 */
	public function get_translations() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			check_ajax_referer( 'get_translations', 'security' );
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_get_translations_regular' && ! empty( $_POST['language'] ) && in_array( $_POST['language'], $this->settings['translation-languages'] ) ) {
				$originals                = ( empty( $_POST['originals'] ) ) ? array() : sanitize_decode_json_html_recursively( 'originals' );
				$skip_machine_translation = ( empty( $_POST['skip_machine_translation'] ) ) ? array() : sanitize_decode_json_html_recursively( 'skip_machine_translation' );
				$ids                      = ( empty( $_POST['string_ids'] ) ) ? array() : sanitize_decode_json_html_recursively( 'string_ids' );
				if ( is_array( $ids ) || is_array( $originals ) ) {
					$etm = ETM_eTranslation_Multilingual::get_etm_instance();
					if ( ! $this->etm_query ) {
						$this->etm_query = $etm->get_component( 'query' );
					}
					if ( ! $this->translation_manager ) {
						$this->translation_manager = $etm->get_component( 'translation_manager' );
					}
					$block_type   = $this->etm_query->get_constant_block_type_regular_string();
					$dictionaries = $this->get_translation_for_strings( $ids, $originals, $block_type, $skip_machine_translation );

					$localized_text = $this->translation_manager->string_groups();
					$string_group   = __( 'Others', 'etranslation-multilingual' ); // this type is not registered in the string types because it will be overwritten by the content in data-etm-node-type
					if ( isset( $_POST['dynamic_strings'] ) && $_POST['dynamic_strings'] === 'true' ) {
						$string_group = $localized_text['dynamicstrings'];
					}
					$dictionary_by_original = etm_sort_dictionary_by_original( $dictionaries, 'regular', $string_group, sanitize_text_field( wp_unslash( $_POST['language'] ) ) );

					emt_safe_json_send( $dictionary_by_original );
				}
			}
		}

		wp_die();
	}
	/**
	 * Return dictionary with translated strings.
	 *
	 * @param $strings
	 * @param null    $block_type
	 *
	 * @return array
	 */
	protected function get_translation_for_strings( $ids, $originals, $block_type = null, $skip_machine_translation = array() ) {
		$etm = ETM_eTranslation_Multilingual::get_etm_instance();
		if ( ! $this->etm_query ) {
			$this->etm_query = $etm->get_component( 'query' );
		}
		if ( ! $this->translation_render ) {
			$this->translation_render = $etm->get_component( 'translation_render' );
		}
		if ( ! $this->url_converter ) {
			$this->url_converter = $etm->get_component( 'url_converter' );
		}

		$home_url       = home_url();
		$id_array       = array();
		$original_array = array();
		$dictionaries   = array();
		foreach ( $ids as $id ) {
			if ( isset( $id ) && is_numeric( $id ) ) {
				$id_array[] = (int) $id;
			}
		}
		foreach ( $originals as $original ) {
			if ( isset( $original ) ) {
				$trimmed_string = etm_full_trim( etm_sanitize_string( $original, false ) );
				if ( ( filter_var( $trimmed_string, FILTER_VALIDATE_URL ) === false ) ) {
					// not url
					$original_array[] = $trimmed_string;
				} else {
					// is url
					if ( $this->translation_render->is_external_link( $trimmed_string, $home_url ) || $this->url_converter->url_is_file( $trimmed_string ) ) {
						// allow only external url or file urls
						$original_array[] = remove_query_arg( 'etm-edit-translation', $trimmed_string );
					}
				}
			}
		}

		$current_language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : '';

		if ( ! etm_is_valid_language_code( $current_language ) ) {
			wp_die();
		}

		// necessary in order to obtain all the original strings
		if ( $this->settings['default-language'] != $current_language ) {
			if ( ! empty( $original_array ) && current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) ) {
				$this->translation_render->process_strings( $original_array, $current_language, $block_type, $skip_machine_translation );
			}
			$dictionaries[ $current_language ] = $this->etm_query->get_string_rows( $id_array, $original_array, $current_language );
		} else {
			$dictionaries[ $current_language ] = array();
		}

		if ( isset( $_POST['all_languages'] ) && $_POST['all_languages'] === 'true' ) {
			foreach ( $this->settings['translation-languages'] as $language ) {
				if ( $language == $this->settings['default-language'] ) {
					$dictionaries[ $language ]['default-language'] = true;
					continue;
				}

				if ( $language == $current_language ) {
					continue;
				}
				if ( empty( $original_strings ) ) {
					$original_strings = $this->extract_original_strings( $dictionaries[ $current_language ], $original_array, $id_array );
				}
				if ( current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) ) {
					$this->translation_render->process_strings( $original_strings, $language, $block_type, $skip_machine_translation );
				}
				$dictionaries[ $language ] = $this->etm_query->get_string_rows( array(), $original_strings, $language );
			}
		}

		if ( count( $skip_machine_translation ) > 0 ) {
			foreach ( $dictionaries as $language => $dictionary ) {
				if ( $language === $this->settings['default-language'] ) {
					continue;
				}
				foreach ( $dictionary as $key => $string ) {
					if ( $string->status == 1 && in_array( $string->original, $skip_machine_translation ) ) {
						// do not return translation for href and src
						$dictionaries[ $language ][ $key ]->translated = '';
						$dictionaries[ $language ][ $key ]->status     = 0;
					}
				}
			}
		}

		return $dictionaries;
	}

	/**
	 * Return array of original strings given their db ids.
	 *
	 * @param array $strings            Strings object to extract original
	 * @param array $original_array     Original strings array to append to.
	 * @param array $id_array           Id array to extract.
	 * @return array                    Original strings array + Extracted strings from ids.
	 */
	protected function extract_original_strings( $strings, $original_array, $id_array ) {
		if ( count( $strings ) > 0 ) {
			foreach ( $id_array as $id ) {
				if ( is_object( $strings[ $id ] ) ) {
					$original_array[] = $strings[ $id ]->original;
				}
			}
		}
		return array_values( $original_array );
	}

	/**
	 * Save translations from ajax post.
	 *
	 * Hooked to wp_ajax_etm_save_translations_regular.
	 */
	public function save_translations() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) ) {
			check_ajax_referer( 'save_translations', 'security' );
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_save_translations_regular' && ! empty( $_POST['strings'] ) ) {
				$strings = sanitize_decode_json_html_recursively( 'strings' );
				// validate input.
				$string_keys = array_keys( get_object_vars( $strings ) );
				if ( array_intersect( $string_keys, $this->settings['translation-languages'] ) !== $string_keys ) {
					// $strings contain some key not present in selected language codes
					wp_die();
				}
				$update_strings = $this->save_translations_of_strings( $strings );
			}
		}
		emt_safe_json_send( $update_strings );
	}

	/**
	 * Save translations in DB for the strings
	 *
	 * @param $strings
	 * @param null    $block_type
	 */
	protected function save_translations_of_strings( $strings, $block_type = null ) {
		if ( ! $block_type ) {
			if ( ! $this->etm_query ) {
				$etm             = ETM_eTranslation_Multilingual::get_etm_instance();
				$this->etm_query = $etm->get_component( 'query' );
			}
			$block_type = $this->etm_query->get_constant_block_type_regular_string();
		}
		$update_strings = array();
		foreach ( $strings as $language => $language_strings ) {
			if ( in_array( $language, $this->settings['translation-languages'] ) && $language != $this->settings['default-language'] ) {
				$update_strings[ $language ] = array();
				foreach ( $language_strings as $string ) {
					if ( isset( $string->id ) && is_numeric( $string->id ) ) {
						if ( ! isset( $string->block_type ) ) {
							$string->block_type = $block_type;
						}
						array_push(
							$update_strings[ $language ],
							array(
								'id'         => (int) $string->id,
								'original'   => etm_sanitize_string( $string->original, false ),
								'translated' => etm_sanitize_string( $string->translated ),
								'status'     => (int) $string->status,
								'block_type' => (int) $string->block_type,
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
			$this->etm_query->update_strings( $update_string_array, $language, array( 'id', 'translated', 'status', 'block_type' ) );
			$this->etm_query->remove_possible_duplicates( $update_string_array, $language, 'regular' );
		}

		do_action( 'etm_save_editor_translations_regular_strings', $update_strings, $this->settings );

		return $update_strings;
	}

	/**
	 * Set translation block to active.
	 *
	 * Creates TB is not exists. Adds auto translation if one is not provided.
	 * Supports handling multiple translation blocks
	 */
	public function create_translation_block() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) ) {
			check_ajax_referer( 'merge_translation_block', 'security' );
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_create_translation_block' && ! empty( $_POST['strings'] ) && ! empty( $_POST['language'] ) && in_array( $_POST['language'], $this->settings['translation-languages'] ) && ! empty( $_POST['original'] ) ) {
				$strings = sanitize_decode_json_html_recursively( 'strings' );

				// validate input.
				$string_keys = array_keys( get_object_vars( $strings ) );
				if ( array_intersect( $string_keys, $this->settings['translation-languages'] ) !== $string_keys ) {
					// $strings contain some key not present in selected language codes
					wp_die();
				}

				if ( isset( $this->settings['translation-languages'] ) ) {
					$etm = ETM_eTranslation_Multilingual::get_etm_instance();
					if ( ! $this->etm_query ) {
						$this->etm_query = $etm->get_component( 'query' );
					}
					if ( ! $this->translation_render ) {
						$this->translation_render = $etm->get_component( 'translation_render' );
					}

					$active_block_type = $this->etm_query->get_constant_block_type_active();
					foreach ( $this->settings['translation-languages'] as $language ) {
						if ( $language != $this->settings['default-language'] ) {
							$dictionaries = $this->get_translation_for_strings( array(), array( wp_kses_post( wp_unslash( $_POST['original'] ) ) ), $active_block_type, array() );
							break;
						}
					}

					/*
					 * Merging the dictionary received from get_translation_for_strings (which contains ID and possibly automatic translations) with
					 * ajax translated (which can contain manual translations)
					 */
					$originals_array_constructed = false;
					$originals                   = array();
					if ( isset( $dictionaries ) ) {
						foreach ( $dictionaries as $language => $dictionary ) {
							if ( $language == $this->settings['default-language'] ) {
								continue;
							}

							foreach ( $dictionary as $dictionary_string_key => $dictionary_string ) {
								if ( ! isset( $strings->$language ) ) {
									continue;
								}
								$ajax_translated_string_list = $strings->$language;

								foreach ( $ajax_translated_string_list as $ajax_key => $ajax_string ) {
									if ( $this->normalize_linebreaks( etm_full_trim( etm_sanitize_string( $ajax_string->original, false ) ) ) === $this->normalize_linebreaks( $dictionary_string->original ) ) {
										if ( $ajax_string->translated != '' ) {
											$dictionaries[ $language ][ $dictionary_string_key ]->translated = etm_sanitize_string( $ajax_string->translated );
											$dictionaries[ $language ][ $dictionary_string_key ]->status     = (int) $ajax_string->status;
										}
										$dictionaries[ $language ][ $dictionary_string_key ]->block_type = (int) $ajax_string->block_type;
									}
									$dictionaries[ $language ][ $dictionary_string_key ]->new_translation_block = true;
								}

								if ( ! $originals_array_constructed ) {
									$originals[] = $dictionary_string->original;
								}
							}

							$originals_array_constructed = true;
						}
						$this->save_translations_of_strings( $dictionaries, $active_block_type );

						// update deactivated languages
						$copy_of_originals = $originals;
						if ( $originals_array_constructed ) {
							$table_names = $this->etm_query->get_all_table_names( $this->settings['default-language'], $this->settings['translation-languages'] );
							if ( count( $table_names ) > 0 ) {
								foreach ( $table_names as $table_name ) {
									$originals           = $copy_of_originals;
									$language            = $this->etm_query->get_language_code_from_table_name( $table_name );
									$existing_dictionary = $this->etm_query->get_string_rows( array(), $originals, $language, ARRAY_A );
									foreach ( $existing_dictionary as $string_key => $string ) {
										foreach ( $originals as $original_key => $original ) {
											if ( $string['original'] == $original ) {
												unset( $originals[ $original_key ] );
											}
										}
										$existing_dictionary[ $string_key ]['block_type'] = $active_block_type;
										$originals                                        = array_values( $originals );
									}
									$this->etm_query->insert_strings( $originals, $language, $active_block_type );
									$this->etm_query->update_strings( $existing_dictionary, $language );
								}
							}
						}

						emt_safe_json_send( $dictionaries );
					}
				}
			}
		}
		die();
	}

	/**
	 * Set translation block to deprecated
	 *
	 * Can handle splitting multiple blocks.
	 *
	 * @return mixed|string|void
	 */
	public function split_translation_block() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'etm_translating_capability', 'manage_options' ) ) ) {
			check_ajax_referer( 'split_translation_block', 'security' );

			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_split_translation_block' && ! empty( $_POST['strings'] ) ) {
				$raw_original_array = sanitize_decode_json_html_recursively( 'strings' );
				// validate input.
				if ( ! is_array( $raw_original_array ) ) {
					wp_die();
				}
				$etm = ETM_eTranslation_Multilingual::get_etm_instance();
				if ( ! $this->etm_query ) {
					$this->etm_query = $etm->get_component( 'query' );
				}
				$deprecated_block_type = $this->etm_query->get_constant_block_type_deprecated();
				$originals             = array();
				foreach ( $raw_original_array as $original ) {
					$originals[] = etm_sanitize_string( $original, false );
				}

				// even inactive languages ( not in $this->settings['translation-languages'] array ) will be updated
				$all_languages_table_names = $this->etm_query->get_all_table_names( $this->settings['default-language'], array() );
				$rows_affected             = $this->etm_query->update_translation_blocks_by_original( $all_languages_table_names, $originals, $deprecated_block_type );
				if ( $rows_affected == 0 ) {
					// do updates individually if it fails
					foreach ( $all_languages_table_names as $table_name ) {
						$this->etm_query->update_translation_blocks_by_original( array( $table_name ), $originals, $deprecated_block_type );
					}
				}
			}
		}

		die();
	}

	/***
	 * Replaces all line breaks with \n
	 */
	private function normalize_linebreaks( $string ) {
		return preg_replace( '~\R~u', "\n", $string );
	}
}
