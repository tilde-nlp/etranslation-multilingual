<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class TRP_eTranslation_Machine_Translator extends TRP_Machine_Translator {

    private float $db_query_interval = 0.1; //100ms
    public static $error_map = array(
        -20000 => 'Source language not specified',
        -20001 => 'Invalid source language',
        -20002 => 'Target language(s) not specified',
        -20003 => 'Invalid target language(s)',
        -20004 => 'DEPRECATED',
        -20005 => 'Caller information not specified',
        -20006 => 'Missing application name',
        -20007 => 'Application not authorized to access the service',
        -20008 => 'Bad format for ftp address',
        -20009 => 'Bad format for sftp address',
        -20010 => 'Bad format for http address',
        -20011 => 'Bad format for email address',
        -20012 => 'Translation request must be text type, document path type or document base64 type and not several at a time',
        -20013 => 'Language pair not supported by the domain',
        -20014 => 'Username parameter not specified',
        -20015 => 'Extension invalid compared to the MIME type',
        -20016 => 'DEPRECATED',
        -20017 => 'Username parameter too long',
        -20018 => 'Invalid output format',
        -20019 => 'Institution parameter too long',
        -20020 => 'Department number too long',
        -20021 => 'Text to translate too long',
        -20022 => 'Too many FTP destinations',
        -20023 => 'Too many SFTP destinations',
        -20024 => 'Too many HTTP destinations',
        -20025 => 'Missing destination',
        -20026 => 'Bad requester callback protocol',
        -20027 => 'Bad error callback protocol',
        -20028 => 'Concurrency quota exceeded',
        -20029 => 'Document format not supported',
        -20030 => 'Text to translate is empty',
        -20031 => 'Missing text or document to translate',
        -20032 => 'Email address too long',
        -20033 => 'Cannot read stream',
        -20034 => 'Output format not supported',
        -20035 => 'Email destination tag is missing or empty',
        -20036 => 'HTTP destination tag is missing or empty',
        -20037 => 'FTP destination tag is missing or empty',
        -20038 => 'SFTP destination tag is missing or empty',
        -20039 => 'Document to translate tag is missing or empty',
        -20040 => 'Format tag is missing or empty',
        -20041 => 'The content is missing or empty',
        -20042 => 'Source language defined in TMX file differs from request',
        -20043 => 'Source language defined in XLIFF file differs from request',
        -20044 => 'Output format is not available when quality estimate is requested. It should be blank or \'xslx\'',
        -20045 => 'Quality estimate is not available for text snippet',
        -20046 => 'Document too big (>20Mb)',
        -20047 => 'Quality estimation not available',
        -40010 => 'Too many segments to translate',
        -80004 => 'Cannot store notification file at specified FTP address',
        -80005 => 'Cannot store notification file at specified SFTP address',
        -80006 => 'Cannot store translated file at specified FTP address',
        -80007 => 'Cannot store translated file at specified SFTP address',
        -90000 => 'Cannot connect to FTP',
        -90001 => 'Cannot retrieve file at specified FTP address',
        -90002 => 'File not found at specified address on FTP',
        -90007 => 'Malformed FTP address',
        -90012 => 'Cannot retrieve file content on SFTP',
        -90013 => 'Cannot connect to SFTP',
        -90014 => 'Cannot store file at specified FTP address',
        -90015 => 'Cannot retrieve file content on SFTP',
        -90016 => 'Cannot retrieve file at specified SFTP address'
    );

    private function check_document($id, $start_timestamp): string {
        global $wpdb;
        $wp_table = $wpdb->prefix . ETRANSLATION_TABLE;

        $last_checked = microtime(true);
        $timeout = $this->settings['trp_advanced_settings']['etranslation_wait_timeout'] ?? 3;
        while (($last_checked - $start_timestamp) < $timeout || $timeout <= 0) {
            $row = $wpdb->get_row("SELECT * FROM $wp_table WHERE id = '$id' AND status != 'TRANSLATING'");
            if ($row != null) {
                if ($row->status == 'ERROR') {
                    error_log("Translation entry [ID=$id] has status 'ERROR', using original strings");
                    return "";
                }
                $deletion = $wpdb->delete($wp_table, array(
                    'id' => $id
                ));
                if (!$deletion) {
                    error_log("Could not delete row from $wp_table [ID=$id]");
                }
                return $row->body;
            }
            sleep($this->db_query_interval);
            $last_checked = microtime(true);
        }

        $this->on_translation_timeout($id, $timeout);
        return "";
    }

    private function on_translation_timeout($id, $timeout) {
        error_log("Could not find translation in DB [ID=$id, timeout=" . $timeout . "s]");

        global $wpdb;
        $wp_table = $wpdb->prefix . ETRANSLATION_TABLE;
        $result = $wpdb->update($wp_table, array(
            'status' => 'TIMEOUT',
        ) , array(
            'id' => $id
        ));

        if (!$result) {
            error_log("Error updating eTranslation DB entry status [ID=$id]");
        }
    }

    public function translate_document( $source_language, $language_code, $strings_array, $start_timestamp): array {
        $delimiter = "\n";
        $id = uniqid();
        $content = implode($delimiter, $strings_array);
        $document = $this->string_to_base64_data($content);
        $response = $this->send_translate_document_request($source_language, $language_code, $document, $id);
        $request_id = isset($response['body']) && is_numeric($response['body']) ? (int) $response['body'] : null;
        if ($response['response'] != 200 || $request_id < 0) {
            $status = $response['response'];
            $message = self::$error_map[$request_id] ?? $response['body'];
            error_log("Invalid response from eTranslation: status=$status" . "message='$message'");
            return array();
        }
        if ($this->insert_status_in_db($id, $request_id, $source_language, $language_code, $content)) {
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
            return TRP_eTranslation_Utils::arr_restore_spaces_after_translation(array_values($strings_array), $result);
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

    private function insert_status_in_db($id, $request_id, $lang_from, $lang_to, $original) {
        global $wpdb;
        $wp_table = $wpdb->prefix . ETRANSLATION_TABLE;
        $result = $wpdb->insert($wp_table, array(
            'id' => $id,
            'requestId' => $request_id,
            'status' => 'TRANSLATING',
            'from' => $lang_from,
            'to' => $lang_to,
            'original' => $original
        ));
        if (!$result) {
            error_log("Error inserting translation entry in eTranslation DB [ID=$id]");
        }
        return $result;
    }

    private function send_translate_document_request($sourceLanguage, $targetLanguage, $document, $id = ""): array {
        $translationRequest= array(
            'documentToTranslateBase64' => $document,
            'sourceLanguage' => strtoupper($sourceLanguage),
            'targetLanguages' => array(
                strtoupper($targetLanguage)
            ),
            'errorCallback' => get_rest_url() . 'etranslation/v1/error_callback/' . $id,
            'callerInformation' => array(
                'application' => $this->get_app_name()
            ),
            'destinations' => array(
                'httpDestinations' => array(
                    get_rest_url() . 'etranslation/v1/document/destination/' . $id
                )
            )
        );

        $post = json_encode($translationRequest);
        $client=curl_init('https://webgate.ec.europa.eu/etranslation/si/translate');

        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($client, CURLOPT_POST, 1);
        curl_setopt($client, CURLOPT_POSTFIELDS, $post);
        curl_setopt($client, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($client, CURLOPT_USERPWD, $this->get_app_name() . ":" . $this->get_password());
        curl_setopt($client, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($client, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($client, CURLOPT_TIMEOUT, 30);
        curl_setopt($client, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post)
        ));

        $response = curl_exec($client);
        $http_status = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        curl_close($client);

        if ($http_status != 200) {
            error_log("Error sending request to eTranslation: $response [status: $http_status]");
        }

        return array('response' => $http_status, 'body' => json_decode($response));
    }

    /**
     * Checks if sprintf format specifiers were not modified during translation
     *
     * @param string $original      original text sent for translation
     * @param string $translation   received text translation
     * @return bool                 whether sprintf format values have not changed in any way (value, order)
     */
    private function check_sprintf_translation($original, $translation) {
        //$regex = "/%(?:\d+\$)?[+-]?(?:[ 0]|'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX]/";
        $regex = '/%(?:\d+\$)?[dfsu]/';
        $original_matches = $new_matches = array();
        preg_match_all($regex, $original, $original_matches);
        preg_match_all($regex, $translation, $new_matches);
        $original_flattened = array_walk_recursive($original_matches, function($v) use (&$result){ $result[] = $v; });
        $new_flattened = array_walk_recursive($new_matches, function($v) use (&$result){ $result[] = $v; });
        return $original_matches == $new_matches;
    }

    /**
     * Returns an array with the API provided translations of the $new_strings array.
     *
     * @param array $new_strings                    array with the strings that need translation. The keys are the node number in the DOM so we need to preserve the m
     * @param string $target_language_code          language code of the language that we will be translating to. Not equal to the google language code
     * @param string $source_language_code          language code of the language that we will be translating from. Not equal to the google language code
     * @return array                                array with the translation strings and the preserved keys or an empty array if something went wrong
     */
    public function translate_array($new_strings, $target_language_code, $source_language_code = null ){
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
            $response = $this->translate_document($source_language, $target_language, $new_strings_chunk, $start_time);

            // this is run only if "Log machine translation queries." is set to Yes.
            $this->machine_translator_logger->log(array(
                'strings'   => serialize( $new_strings_chunk),
                'response'  => serialize( $response ),
                'lang_source'  => $source_language,
                'lang_target'  => $target_language,
            ));

            /* analyze the response */
            if (is_array($response)) {

                $this->machine_translator_logger->count_towards_quota( $new_strings_chunk );

                foreach ($new_strings_chunk as $key => $old_string) {
                    if (isset($response[$i])) {
                        $translated_strings[$key] = $response[$i];
                    } else {
                        //error_log("[$source_language_code => $target_language_code] Translation not found for key '$key'. Using original string");
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
        $document = $this->string_to_base64_data('about');
        return $this->send_translate_document_request('en', 'lv', $document);
    }

    public function get_app_name() {
        return isset( $this->settings['trp_machine_translation_settings'], $this->settings['trp_machine_translation_settings']['etranslation-app-name'] ) ? $this->settings['trp_machine_translation_settings']['etranslation-app-name'] : '';
    }

    public function get_password() {
        return isset( $this->settings['trp_machine_translation_settings'], $this->settings['trp_machine_translation_settings']['etranslation-pwd'] ) ? $this->settings['trp_machine_translation_settings']['etranslation-pwd'] : '';
    }

    public function get_api_key() {
        return array($this->get_app_name(), $this->get_password());
    }

    public function get_supported_languages(){
        return array(
            "bg",
            "hr",
            "cs",
            "da",
            "nl",
            "en",
            "et",
            "fi",
            "fr",
            "de",
            "el",
            "hu",
            "ga",
            "it",
            "lv",
            "lt",
            "mt",
            "pl",
            "pt",
            "ro",
            "sk",
            "sl",
            "es",
            "sv",
            # unoficial but supported languages
            "is",
            "nb",
            # non-European languages
            "ru",
            "zh",
            "ja",
            "ar"
        );
    }

    public function get_engine_specific_language_codes($languages) {
        return $this->trp_languages->get_iso_codes($languages);
    }

    /*
     * Google does not support formality yet, but we need this for the machine translation tab to show the unsupported languages for formality
     */
    public function check_formality(){

        $formality_supported_languages = array();

        return $formality_supported_languages;
    }
}
