<?php


class ETM_String_Translation_API_Regular {
	protected $type = 'regular';
	protected $helper;

	/* @var ETM_Query */

	public function __construct( $settings ) {
		$this->helper = new ETM_String_Translation_Helper();
	}

	public function get_strings() {

		$etm          = ETM_eTranslation_Multilingual::get_etm_instance();
		$etm_query    = $etm->get_component( 'query' );
		$etm_settings = $etm->get_component( 'settings' );
		$settings     = $etm_settings->get_settings();

		$originals_results = $this->helper->get_originals_results(
			$this->type,
			$etm_query->get_table_name_for_original_strings(),
			$etm_query->get_table_name_for_original_meta(),
			'get_table_name',
			array(
				'status'     => 'status',
				'block_type' => 'translation-block-type',
			)
		);

		if ( $originals_results['total_item_count'] > 0 ) {
			// query each language table to retrieve translations
			$dictionaries = array();
			foreach ( $settings['translation-languages'] as $language ) {
				if ( $language === $settings['default-language'] ) {
					continue;
				}
				$dictionaries[ $language ] = $etm_query->get_string_rows( $originals_results['original_ids'], array(), $language, 'OBJECT_K', true );
			}
			$dictionary_by_original = etm_sort_dictionary_by_original( $dictionaries, $this->type, null, null );
		} else {
			$dictionary_by_original = array();
		}
		emt_safe_json_send(
			array(
				'dictionary' => $dictionary_by_original,
				'totalItems' => $originals_results['total_item_count'],
			)
		);
	}


	/** Using editor api function hooked for saving.
	 * Implementing save_strings function is not necessary
	 * Leave this function empty, removing it will cause a thrown notice
	 */
	public function save_strings() {

	}
}
