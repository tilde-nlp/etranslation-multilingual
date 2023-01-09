<?php

class ETM_Translation_Memory {
	protected $db;
	protected $settings;
	/* @var ETM_Query */
	protected $etm_query;

	const MIN_NUMBER_OF_CHARS_FOR_FULLTEXT = 20;

	/**
	 * ETM_Translation_Memory constructor.
	 *
	 * @param $settings
	 */
	public function __construct( $settings ) {
		global $wpdb;
		$this->db       = $wpdb;
		$this->settings = $settings;
	}

	/**
	 * Finding similar strings in the database and returning an array with possible translations.
	 *
	 * @param string $string         The original string we're searching a similar one.
	 * @param string $table_name          The table where we should look for similar strings in. Default dictionary.
	 * @param int    $number         The number of similar strings we want to return.
	 * @return array                    Array with (original => translated ) pairs based on the number of strings we should account for. Empty array if nothing is found.
	 */
	public function get_similar_string_translation( $string, $number, $table_name ) {
		if ( empty( $table_name ) ) {
			return array();
		}

		$etm = ETM_eTranslation_Multilingual::get_etm_instance();
		if ( ! $this->etm_query ) {
			$this->etm_query = $etm->get_component( 'query' );
		}

		$query  = '';
		$query .= 'SELECT original,translated, status FROM `'
				 . sanitize_text_field( $table_name )
				 . '` WHERE status != ' . ETM_Query::NOT_TRANSLATED . " AND `original` != '%s' AND MATCH(original) AGAINST ('%s' IN NATURAL LANGUAGE MODE ) LIMIT " . $number;

		$query  = $this->db->prepare( $query, array( $string, $string ) );
		$result = $this->db->get_results( $query, ARRAY_A );

		return $result;
	}

	/**
	 * Ajax Callback for getting similar translations for strings.
	 *
	 * @return string       Json Array with (original => translated ) pairs based on the number of strings we should account for. Empty json array if nothing is found.
	 */
	public function ajax_get_similar_string_translation() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'etm_get_similar_string_translation' && ! empty( $_POST['original_string'] ) && ! empty( $_POST['language'] ) && ! empty( $_POST['type'] ) && in_array( $_POST['language'], $this->settings['translation-languages'] ) ) {
				global $ETM_LANGUAGE;
				check_ajax_referer( 'getsimilarstring', 'security' );
				$string        = ( isset( $_POST['original_string'] ) ) ? wp_kses_post( wp_unslash( $_POST['original_string'] ) ) : '';
				$language_code = ( isset( $_POST['language'] ) ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : $ETM_LANGUAGE;
				$type          = ( isset( $_POST['type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
				$number        = ( isset( $_POST['number'] ) ) ? (int) $_POST['number'] : 3;

				$etm = ETM_eTranslation_Multilingual::get_etm_instance();
				if ( ! $this->etm_query ) {
					$this->etm_query = $etm->get_component( 'query' );
				}

				$table_name = null;

				// there is no dictionary table with the default language
				if ( $language_code !== $this->settings['default-language'] ) {
					// data-etm-translate-id, data-etm-translate-id-innertext are in the wp_etm_dictionary_* tables
					$table_name = $this->etm_query->get_table_name( $language_code );
				}

				if ( $type == 'gettext' ) {
					$table_name = $this->etm_query->get_gettext_table_name( $language_code );
				}

				if ( $table_name === null ) {
					$dictionary = array();
				} else {
					$dictionary = $this->get_similar_string_translation( $string, $number, $table_name );
				}
				emt_safe_json_send( $dictionary );
			}
		}
		emt_safe_json_send( array() );
	}
}
