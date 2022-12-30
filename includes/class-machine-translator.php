<?php

/**
 * Class ETM_Machine_Translator
 *
 * Facilitates Machine Translation calls
 */
class ETM_Machine_Translator {
    protected $settings;
	protected $referer;
	protected $url_converter;
	protected $machine_translator_logger;
	protected $machine_translation_codes;
	protected $etm_languages;
    protected $correct_api_key = null;
    /**
     * ETM_Machine_Translator constructor.
     *
     * @param array $settings         Settings option.
     */
    public function __construct( $settings ){
        $this->settings = $settings;

        $etm                             = ETM_eTranslation_Multilingual::get_etm_instance();
        if ( ! $this->machine_translator_logger ) {
            $this->machine_translator_logger = $etm->get_component('machine_translator_logger');
        }
        if ( ! $this->etm_languages ) {
            $this->etm_languages = $etm->get_component('languages');
        }
        $this->machine_translation_codes = $this->etm_languages->get_iso_codes($this->settings['translation-languages']);
        add_filter( 'etm_exclude_words_from_automatic_translation', array( $this, 'sort_exclude_words_from_automatic_translation_array' ), 99999, 1 );
        add_filter( 'etm_exclude_words_from_automatic_translation', array( $this, 'exclude_special_symbol_from_translation' ), 9999, 2 );
    }

    /**
     * Whether automatic translation is available.
     *
     * @param array $languages
     * @return bool
     */
    public function is_available( $languages = array() ){
        if( !empty( $this->settings['etm_machine_translation_settings']['machine-translation'] ) &&
            $this->settings['etm_machine_translation_settings']['machine-translation'] == 'yes'
        ) {
            if ( empty( $languages ) ){
                // can be used to simply know if machine translation is available
                return true;
            }

            return $this->check_languages_availability($languages);

        }else {
            return false;
        }
    }

    public function check_languages_availability( $languages, $force_recheck = false ){
        if ( !method_exists( $this, 'get_supported_languages' ) || !method_exists( $this, 'get_engine_specific_language_codes' ) || !$this->credentials_set() ){
            return true;
        }
        $force_recheck = ( current_user_can('manage_options') &&
            !empty( $_GET['etm_recheck_supported_languages']) && $_GET['etm_recheck_supported_languages'] === '1' &&
            wp_verify_nonce( sanitize_text_field( $_GET['etm_recheck_supported_languages_nonce'] ), 'etm_recheck_supported_languages' ) ) ? true : $force_recheck; //phpcs:ignore
        $data = get_option('etm_db_stored_data', array() );
        if ( isset( $_GET['etm_recheck_supported_languages'] )) {
            unset($_GET['etm_recheck_supported_languages'] );
        }

        // if supported languages are not stored, fetch them and update option
        if ( empty( $data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['last-checked'] ) || $force_recheck || ( method_exists($this,'check_formality') && !isset($data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['formality-supported-languages']))){
            if ( empty( $data['etm_mt_supported_languages'] ) ) {
                $data['etm_mt_supported_languages'] = array();
            }
            if ( empty( $data['etm_mt_supported_languages'][ $this->settings['etm_machine_translation_settings']['translation-engine'] ] ) ) {
                $data['etm_mt_supported_languages'][ $this->settings['etm_machine_translation_settings']['translation-engine'] ] = array( 'languages' => array() );
            }

            $data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['languages'] = $this->get_supported_languages();
            if (method_exists($this, 'check_formality')) {
                $data['etm_mt_supported_languages'][ $this->settings['etm_machine_translation_settings']['translation-engine'] ]['formality-supported-languages'] = $this->check_formality();
            }
            $data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['last-checked'] = date("Y-m-d H:i:s" );
            update_option('etm_db_stored_data', $data );
        }

        $languages_iso_to_check = $this->get_engine_specific_language_codes( $languages );

        $all_are_available = !array_diff($languages_iso_to_check, $data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['languages']);

        return apply_filters('etm_mt_available_supported_languages', $all_are_available, $languages, $this->settings );
    }

    public function get_last_checked_supported_languages(){
        $data = get_option('etm_db_stored_data', array() );
        if ( empty( $data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['last-checked'] ) ){
            $this->check_languages_availability( $this->settings['translation-languages'], true);
        }
        return $data['etm_mt_supported_languages'][$this->settings['etm_machine_translation_settings']['translation-engine']]['last-checked'];
    }

    /**
     * Output an SVG based on translation engine and error flag.
     *
     * @param bool $show_errors true to show an error SVG, false if not.
     */
    public function automatic_translation_svg_output( $show_errors ) {
        if ( method_exists( $this, 'automatic_translate_error_check' ) ) {
            if ( $show_errors ) {
                etm_output_svg( 'error' );
            } else {
                etm_output_svg( 'check' );
            }
        }
        
    }

    /**
     *
     * @deprecated
     * Check the automatic translation API keys for eTranslation.
     *
     * @param ETM_eTranslation_Multilingual $machine_translator Machine translator instance.
     * @param string $translation_engine              The translation engine
     * @param string $api_key                         The API key to check.
     *
     * @return array [ (string) $message, (bool) $error ].
     */
    public function automatic_translate_error_check( $machine_translator, $translation_engine, $api_key ) {

        $is_error       = false;
        $return_message = '';

        switch ( $translation_engine ) {
            case 'etranslation':
                $appname = $api_key[0];
                $password = $api_key[1];
                if (!isset($appname) || !isset($password)) {
                    $is_error = true;
                    $return_message = __( 'Please enter your eTranslation credentials.', 'etranslation-multilingual' );
                } else {
                    $response = $machine_translator->test_request();
                    $code     = $response["response"];
                    if ( 200 !== $code ) {
                        $is_error        = true;
                        $translate_response = etm_etranslation_response_codes( $code );
                        $return_message     = $translate_response['message'];

                        error_log("Error on eTranslation request: $response");
                    }
                }
                break;
            default:
                break;
        }


        $this->correct_api_key=array(
            'message' => $return_message,
            'error'   => $is_error,
        );

        return $this->correct_api_key;
    }

    // checking if the api_key is correct in order to display unsupported languages

    public function is_correct_api_key(){

        if(method_exists($this, 'check_api_key_validity')){
            $verification = $this->check_api_key_validity();
        }else {
            //we only need this values for automatic translate error check function for backwards compatibility

            $machine_translator = $this;
            $translation_engine = $this->settings['etm_machine_translation_settings']['translation-engine'];
            $api_key = $this->get_api_key();
            $verification = $this->automatic_translate_error_check( $machine_translator, $translation_engine, $api_key );
        }
        if($verification['error']== false) {
            return true;
        }
        return false;
    }


	/**
	 * Return site referer
	 *
	 * @return string
	 */
	public function get_referer(){
		if( ! $this->referer ) {
			if( ! $this->url_converter ) {
				$etm = ETM_eTranslation_Multilingual::get_etm_instance();
				$this->url_converter = $etm->get_component( 'url_converter' );
			}

			$this->referer = $this->url_converter->get_abs_home();
		}

		return $this->referer;
	}

    /**
     * Verifies that the machine translation request is valid
     *
     * @param  string $target_language_code language we're looking to translate to
     * @param  string $source_language_code language we're looking to translate from
     * @return bool
     */
    public function verify_request_parameters($target_language_code, $source_language_code){
        if( !$this->credentials_set() ||
            empty( $target_language_code ) || empty( $source_language_code ) ||
            empty( $this->machine_translation_codes[$target_language_code] ) ||
            empty( $this->machine_translation_codes[$source_language_code] ) ||
            $this->machine_translation_codes[$target_language_code] == $this->machine_translation_codes[$source_language_code]
        )
            return false;

        // Method that can be extended in the child class to add extra validation
        if( !$this->extra_request_validations( $target_language_code ) )
            return false;

        // Check if crawlers are blocked
        if( !empty( $this->settings['etm_machine_translation_settings']['block-crawlers'] ) && $this->settings['etm_machine_translation_settings']['block-crawlers'] == 'yes' && $this->is_crawler() )
            return false;

        // Check if daily quota is met
        if( $this->machine_translator_logger->quota_exceeded() )
            return false;

        return true;
    }

    public function credentials_set() {
        $engine = $this->settings['etm_machine_translation_settings']['translation-engine'];
        if ($engine == 'etranslation') {
            return is_array($this->get_api_key()) && !in_array('', $this->get_api_key());
        } else {
            return !empty($this->get_api_key());
        }
    }

    /**
     * Verifies user agent to check if the request is being made by a crawler
     *
     * @return boolean
     */
    private function is_crawler(){
        if( !isset( $_SERVER['HTTP_USER_AGENT'] ) )
            return false;

        $crawlers = apply_filters( 'etm_machine_translator_crawlers', 'rambler|abacho|acoi|accona|aspseek|altavista|estyle|scrubby|lycos|geona|ia_archiver|alexa|sogou|skype|facebook|twitter|pinterest|linkedin|naver|bing|google|yahoo|duckduckgo|yandex|baidu|teoma|xing|java\/1.7.0_45|bot|crawl|slurp|spider|mediapartners|\sask\s|\saol\s' );

        return preg_match( '/'. $crawlers .'/i', sanitize_text_field ( $_SERVER['HTTP_USER_AGENT'] ) );
    }

    private function get_placeholders( $count ){
	    $placeholders = array();
	    for( $i = 1 ; $i <= $count; $i++ ){
            $placeholders[] = '1TP' . $i . 'T';
        }
	    return $placeholders;
    }

    /**
     * Function to be used externally
     *
     * @param $strings
     * @param $target_language_code
     * @param $source_language_code
     * @return array
     */
    public function translate($strings, $target_language_code, $source_language_code = null ){
        if ( !empty($strings) && is_array($strings) && method_exists( $this, 'translate_array' ) && apply_filters( 'etm_disable_automatic_translations_due_to_error', false ) === false ) {

            /* google (and eTranslation) has a problem translating this characters ( '%', '$', '#' )...for some reasons it puts spaces after them so we need to 'encode' them and decode them back. hopefully it won't break anything important */
            /* we put '%s' before '%' because google seems to transform %s into % in strings for some languages which causes a 500 Fatal Error in PHP 8*/
            $imploded_strings = implode(" ", $strings);
            $etm_exclude_words_from_automatic_translation = apply_filters('etm_exclude_words_from_automatic_translation', array('%s', '%d', '%', '$', '#'), $imploded_strings);
            $placeholders = $this->get_placeholders(count($etm_exclude_words_from_automatic_translation));
            $shortcode_tags_to_execute = apply_filters( 'etm_do_these_shortcodes_before_automatic_translation', array('etm_language') );

            $strings = array_unique($strings);
            $original_strings = $strings;

            foreach ($strings as $key => $string) {
                /* html_entity_decode is needed before replacing the character "#" from the list because characters like &#8220; (8220 utf8)
                 * will get an extra space after '&' which will break the character, rendering it like this: & #8220;
                 */

                $strings[$key] = str_replace($etm_exclude_words_from_automatic_translation, $placeholders, html_entity_decode( $string ));
                $strings[$key] = etm_do_these_shortcodes( $strings[$key], $shortcode_tags_to_execute );
            }

            if ($this->settings['etm_machine_translation_settings']['translation-engine'] === 'etranslation') {
                $machine_strings = $this->translate_array($strings, $original_strings, $target_language_code, $source_language_code);
            } else {
                $machine_strings = $this->translate_array($strings, $target_language_code, $source_language_code);
            }

            $machine_strings_return_array = array();
            if (!empty($machine_strings)) {
                foreach ($machine_strings as $key => $machine_string) {
                    $machine_strings_return_array[$original_strings[$key]] = str_ireplace( $placeholders, $etm_exclude_words_from_automatic_translation, $machine_string );
                }
            }
            return $machine_strings_return_array;
        }else {
            return array();
        }
    }

    /**
     * @param $etm_exclude_words_from_automatic_translation
     * @return mixed
     *
     * We need to sort the $etm_exclude_words_from_automatic_translation array descending because we risk to not translate excluded multiple words when one
     * is repeated ( example: Facebook, Facebook Store, Facebook View, because Facebook was the first one in the array it was replaced with a code and the
     * other words group ( Store, View) were translated)
     */
    public function sort_exclude_words_from_automatic_translation_array($etm_exclude_words_from_automatic_translation){
        usort($etm_exclude_words_from_automatic_translation, array($this,"sort_array"));

        return $etm_exclude_words_from_automatic_translation;
    }

    public function sort_array($a, $b){
        return strlen($b)-strlen($a);
    }


    public function test_request(){}

    public function get_api_key(){
        return false;
    }

    public function extra_request_validations( $to_language ){
        return true;
    }

    public function exclude_special_symbol_from_translation($array, $strings){
        $float_array_symbols = array('d', 's', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'u');
        foreach ($float_array_symbols as $float_array_symbol){
            for($i= 1; $i<=10; $i++) {
                $symbol = '%'.$i .'$'.$float_array_symbol;
                if ( strpos( $strings, $symbol ) !== false ) {
                    $array[] = '%' . $i . '$' . $float_array_symbol;
                }
            }
        }
        return $array;
    }

}
