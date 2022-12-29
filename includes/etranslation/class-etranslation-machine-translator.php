<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class ETM_eTranslation_Machine_Translator extends ETM_Machine_Translator {

    private $db_query_interval = 0.1; //100ms
    private $etranslation_service;
    public $etranslation_query;

    public function __construct( $settings ) {
        parent::__construct($settings);
        
        $this->etranslation_service = new eTranslation_Service($this->get_app_name(), $this->get_password());
        $this->etranslation_query = new eTranslation_Query();
    }

    private function check_document($id, $start_timestamp): string {
        $last_checked = microtime(true);
        $timeout = $this->settings['etm_advanced_settings']['etranslation_wait_timeout'] ?? DEFAULT_ETRANSLATION_TIMEOUT;
        while (($last_checked - $start_timestamp) < $timeout || $timeout <= 0) {
            $translation = $this->etranslation_query->search_saved_translation($id);
            if ($translation != null) {
                return $translation;
            }
            sleep($this->db_query_interval);
            $last_checked = microtime(true);
        }

        $this->on_translation_timeout($id, $timeout);
        return "";
    }

    private function on_translation_timeout($id, $timeout) {
        error_log("Could not find translation in DB [ID=$id, timeout=" . $timeout . "s]");
        $this->etranslation_query->update_translation_status($id, 'TIMEOUT');
    }

    public function translate_document( $source_language_code, $target_language_code, $strings_array, $original_strings, $start_timestamp): array {
        $delimiter = "\n";
        $id = uniqid();

        $source_language = $this->machine_translation_codes[$source_language_code];
        $target_language = $this->machine_translation_codes[$target_language_code];
        $domain = $this->settings['translation-languages-domain-parameter'][$target_language_code] ?? "GEN";

        $content = implode($delimiter, $strings_array);
        $document = $this->string_to_base64_data($content);

        $temp_entry = $this->etranslation_query->insert_translation_entry($id, strtolower($source_language_code), strtolower($target_language_code), implode($delimiter, $original_strings));
        if ($temp_entry) {
            $response = $this->etranslation_service->send_translate_document_request($source_language, $target_language, $document, $domain, $id);
            $request_id = isset($response['body']) && is_numeric($response['body']) ? (int) $response['body'] : null;
            if ($response['response'] != 200 || $request_id < 0) {
                return array();
            }

            $translation = $this->check_document($id, $start_timestamp);
            if (empty($translation)) {
                return array();
            }
            $result = explode($delimiter, $translation);
            $original_count = count($strings_array);
            $translation_count = count($result);
            if ($translation_count != $original_count && !($translation_count == $original_count + 1 && end($result) == "")) {
                error_log("Original string list size differs from translation list size (". count($strings_array) . " != " . count($result) . ") [ID=$id]");
            }
            return ETM_eTranslation_Utils::arr_restore_spaces_after_translation(array_values($strings_array), $result);
        } else {
            return array();
        }
    }

    private function string_to_base64_data($string) {
        $base64_string = base64_encode($string);
        return array(
            "content" => $base64_string,
            "format" => "txt",
            "filename" => "translateMe"
        );
    }

    /**
     * Returns an array with the API provided translations of the $new_strings array.
     *
     * @param array $new_strings                    array with the strings that need translation. The keys are the node number in the DOM so we need to preserve the m
     * @param string $original_strings              untransformed version of $new_strings, matching 'original' column values in database. Needed to manually replace translations after eTranslation timeout.
     * @param string $target_language_code          language code of the language that we will be translating to. Not equal to the google language code
     * @param string $source_language_code          language code of the language that we will be translating from. Not equal to the google language code
     * @return array                                array with the translation strings and the preserved keys or an empty array if something went wrong
     */
    public function translate_array($new_strings, $original_strings, $target_language_code, $source_language_code = null ) {    
        if ( $source_language_code == null ){
            $source_language_code = $this->settings['default-language'];
        }
        if( empty( $new_strings ) || !$this->verify_request_parameters( $target_language_code, $source_language_code ) )
            return array();

        $start_time = microtime(true);

        $source_language = $this->machine_translation_codes[$source_language_code];
        $target_language = $this->machine_translation_codes[$target_language_code];

        $translated_strings = array();

        $length_limit = 20e+6 / 4;
        $new_strings_chunks = array_chunk( $new_strings, $length_limit, true );
        /* if there are more than 20MB we make multiple requests */
        foreach( $new_strings_chunks as $new_strings_chunk ) {
            $i = 0;
            $response = $this->translate_document($source_language_code, $target_language_code, $new_strings_chunk, $original_strings, $start_time);

            // this is run only if "Log machine translation queries." is set to Yes.
            $this->machine_translator_logger->log(array(
                'strings'   => serialize( $new_strings_chunk),
                'response'  => serialize( $response ),
                'lang_source'  => $source_language,
                'lang_target'  => $target_language,
            ));

            /* analyze the response */
            if (is_array($response) && !empty($response)) {

                $this->machine_translator_logger->count_towards_quota( $new_strings_chunk );

                if (count($response) > 0 && count($response) < count($new_strings_chunk)) {
                    error_log("[$source_language_code => $target_language_code] Translation list is incomplete. Using original string for last " . (count($new_strings_chunk) - count($response)) . " strings.");
                }
                foreach ($new_strings_chunk as $key => $old_string) {
                    if (isset($response[$i])) {
                        $translated_strings[$key] = $response[$i];
                    } else {
                        $translated_strings[$key] = $old_string;
                    }
                    $i++;
                }
            }

            if( $this->machine_translator_logger->quota_exceeded() ) {
                break;
            }
        }

        // will have the same indexes as $new_string or it will be an empty array if something went wrong
        return $translated_strings;
    }

    /**
     * Send a test request to verify if the functionality is working
     */
    public function test_request(){
        return $this->etranslation_service->send_translate_document_request('en', 'lv', $this->string_to_base64_data('about'));
    }

    public function get_app_name() {
        return isset( $this->settings['etm_machine_translation_settings'], $this->settings['etm_machine_translation_settings']['etranslation-app-name'] ) ? $this->settings['etm_machine_translation_settings']['etranslation-app-name'] : '';
    }

    public function get_password() {
        return isset( $this->settings['etm_machine_translation_settings'], $this->settings['etm_machine_translation_settings']['etranslation-pwd'] ) ? ETM_eTranslation_Utils::decrypt_password($this->settings['etm_machine_translation_settings']['etranslation-pwd']) : '';
    }

    public function get_api_key() {
        return array($this->get_app_name(), $this->get_password());
    }

    public function get_supported_languages() {
        $response = $this->etranslation_service->get_available_domain_language_pairs();        
        if ($response['response'] == 200) {
            $domains = $response['body'];
            $language_pairs = $domains->GEN->languagePairs;
            $from_languages = array_map(array($this, 'extract_source_language'), $language_pairs);
            return array_unique($from_languages);
        }
        return array();       
    }

    private function extract_source_language($lang_pair_str) {
        return strtolower(explode("-", $lang_pair_str)[0]);
    }

    public function get_all_domains() {
        $option_name = 'etm_etranslation_domains';
        $stored_domains = get_option($option_name);
        if ($stored_domains && !empty($stored_domains)) {
            return $stored_domains;
        } else {
            $response = $this->etranslation_service->get_available_domain_language_pairs();
            if ($response['response'] == 200) {
                $domains = $response['body'];        
                update_option($option_name, $domains);
                return $domains;
            }
        }
        return array();       
    }

    public function get_engine_specific_language_codes($languages) {
        return $this->etm_languages->get_iso_codes($languages);
    }

    public function check_formality(){

        $formality_supported_languages = array();

        return $formality_supported_languages;
    }

    public function check_api_key_validity() {
        $machine_translator = $this;
        $translation_engine = $this->settings['etm_machine_translation_settings']['translation-engine'];
        $api_key            = $machine_translator->get_api_key();

        $is_error       = false;
        $return_message = '';

        if ( 'etranslation' === $translation_engine && isset($this->settings['etm_machine_translation_settings']['machine-translation']) &&
                $this->settings['etm_machine_translation_settings']['machine-translation'] === 'yes') {

            if ( isset( $this->correct_api_key ) && $this->correct_api_key != null ) {
                return $this->correct_api_key;
            }

            if ( !$this->credentials_set() ) {
                $is_error       = true;
                $return_message = __( 'Please enter your eTranslation credentials.', 'etranslation-multilingual' );
            } else {
                $response = $machine_translator->test_request();
                $code     = $response["response"];
                if ( 200 !== $code ) {
                    $is_error        = true;
                    $translate_response = etm_etranslation_response_codes( $code );
                    $return_message     = $translate_response['message'];

                    error_log("Error on eTranslation request: " . print_r($response, true));
                }
            }
            $this->correct_api_key = array(
                'message' => $return_message,
                'error'   => $is_error,
            );
        }

        return array(
            'message' => $return_message,
            'error'   => $is_error,
        );
    }
}
