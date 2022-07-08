<?php

class eTranslation_Query {

    private $db;
    private $jobs_table;

    function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->jobs_table = $wpdb->prefix . 'etm_etranslation_jobs';
        $this->create_etranslation_database_table();
    }

    function translation_document_destination(WP_REST_Request $request): array {
        $id = $request['id'];
        $db_row = $this->get_incomplete_db_entry($id);
    
        if ($db_row != null) {
            $translation = base64_decode($request->get_body());
    
            if ($db_row->status == 'TIMEOUT') {
                //timeout has been reached, update translation manually & delete row from job table
                $this->update_translation_manually($db_row, $translation);
                $deletion = $this->db->delete($this->jobs_table, array(
                    'id' => $db_row->id
                ));
                if (!$deletion) {
                    $error = $this->db->last_error;
                    error_log("Could not delete $this->jobs_table row after updating translation manually [ID=$db_row->id, error=$error]");
                }
            } else {
                //translation completed in time
                $update_result = $this->db->update($this->jobs_table, array(
                    'status' => 'DONE',
                    'body' => $translation
                ) , array(
                    'id' => $id
                ));
    
                if (!$update_result) {
                    $error = $this->db->last_error;
                    error_log("Error updating eTranslation DB entry [ID=$id, error=$error]");
                }
            }
        } else {
            error_log("Translation received has no entry in database [ID=$id]");
        }
    
        return array($id, $request->get_body());
    }

    function translation_error_callback(WP_REST_Request $request): array {
        $id = $request['id'];
        $db_entry = $this->get_incomplete_db_entry($id);
    
        if ($db_entry != null) {
            $error_code = $request->get_param('error_code');
    
            $update_result = $this->db->update($this->jobs_table, array(
                'status' => 'ERROR',
                'body' => $error_code
            ) , array(
                'id' => $id
            ));
    
            if (!$update_result) {
                $error = $this->db->last_error;
                error_log("Error updating eTranslation DB entry [ID=$id, error=$error]");
            }
        } else {
            error_log("Translation error received has no entry in database [ID=$id]");
        }
    
        $error_message = $request->get_param('error-message');
        error_log("Error translating document with eTranslation: $error_message [code: $error_code] ID=$id");
    
        return array($id, $error_code);
    }

    function search_saved_translation($id) {
        $row = $this->db->get_row("SELECT * FROM $this->jobs_table WHERE id = '$id' AND status != 'TRANSLATING'");
        if ($row != null) {
            if ($row->status == 'ERROR') {
                error_log("Translation entry [ID=$id] has status 'ERROR', using original strings");
                return "";
            }
            $deletion = $this->db->delete($this->jobs_table, array(
                'id' => $id
            ));
            if (!$deletion) {
                $error = $this->db->last_error;
                error_log("Could not delete row from $this->jobs_table [ID=$id, error=$error]");
            }
            return $row->body;
        }
        return null;
    }

    function update_translation_status($id, $status) {
        $result = $this->db->update($this->jobs_table, array(
            'status' => $status,
        ) , array(
            'id' => $id
        ));

        if (!$result) {
            $error = $this->db->last_error;
            error_log("Error updating eTranslation DB entry status [ID=$id, error=$error]");
        }
    }

    function insert_translation_entry($id, $request_id, $lang_from, $lang_to, $original) {
        $result = $this->db->insert($this->jobs_table, array(
            'id' => $id,
            'requestId' => $request_id,
            'status' => 'TRANSLATING',
            'from' => $lang_from,
            'to' => $lang_to,
            'original' => $original
        ));
        if (!$result) {
            $error = $this->db->last_error;
            error_log("Error inserting translation entry in eTranslation DB [ID=$id, error=$error]");
        }
        return $result;
    }

    function create_etranslation_database_table() {    
        #Check to see if the table exists already, if not, then create it
        if ($this->db->get_var("show tables like '$this->jobs_table'") != $this->jobs_table) {
            $sql = "CREATE TABLE `" . $this->jobs_table . "` ( ";
            $sql .= "  `id`  VARCHAR(13) NOT NULL, ";
            $sql .= " `requestId` BIGINT NOT NULL, ";
            $sql .= "  `status`  ENUM('TRANSLATING','DONE','ERROR','TIMEOUT') NOT NULL DEFAULT 'TRANSLATING', ";
            $sql .= "  `body`  LONGTEXT NULL DEFAULT NULL, ";
            $sql .= " `from` VARCHAR(5) NULL, ";
            $sql .= " `to` VARCHAR(200) NULL, ";
            $sql .= " `original` LONGTEXT NULL, ";
            $sql .= " `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ";
            $sql .= "  PRIMARY KEY (`id`) ";
            $sql .= ") COLLATE='utf8mb4_unicode_520_ci'; ";
            require_once (ABSPATH . '/wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
    
            if (!$result) {
                error_log("Error creating eTranslation jobs DB table");
            }
        }
    }

    private function update_translation_manually($details_row, $translations) {
        $trp = TRP_Translate_Press::get_trp_instance();
        $trp_query = $trp->get_component( 'query' );

        $dict_table = 'wp_etm_dictionary_' . $details_row->from . '_' . $details_row->to;
        $delimiter = "\n";
        $original_strings = explode($delimiter, $details_row->original);
        $decoded_translations = self::decode_untranslated_symbols(explode($delimiter, $translations), $original_strings);
        $translation_strings = TRP_eTranslation_Utils::arr_restore_spaces_after_translation($original_strings, $decoded_translations);
    
        //insert original strings in table if they don't exist        
        $original_inserts = $trp_query->original_strings_sync($details_row->to, $original_strings);
    
        $max_id = $this->db->get_row("SELECT MAX(id) as id FROM $dict_table")->id;
        $next_id = intval($max_id) + 1;
    
        $update_strings = array();
        for ( $i = 0; $i < count($original_strings); $i++ ) {
            $string = $original_strings[$i];
            array_push( $update_strings, array(
                'id'          => $next_id + $i,
                'original_id' => $original_inserts[ $string ]->id,
                'original'    => $string,
                'translated'  => trp_sanitize_string( $translation_strings[ $i ] ),
                'status'      => $trp_query->get_constant_machine_retranslated() ) );
        }
    
        //insert translations
        $updated_rows = $trp_query->update_strings( $update_strings, $details_row->to, array( 'id', 'original', 'translated', 'status', 'original_id' ) );

        if (count($update_strings) != $updated_rows) {
            error_log("Translation list size differs from updated row count (" . count($update_strings) . " vs " . $updated_rows . ")");
        }
    
        //delete previously inserted untranslated rows 
        $trp_query->remove_possible_duplicates($update_strings, $details_row->to, 'regular');
    }

    private function decode_untranslated_symbols($translations, $originals) {
        $excluded_words_from_automatic_translation = apply_filters('trp_exclude_words_from_automatic_translation', TRP_eTranslation_Utils::get_strings_to_encode_before_translation());
        for ($i = 0; $i < count($translations); $i++) {
            $translation = $translations[$i];
            //check if decoding needed
            if (str_contains($translation, "1TP")) {
                $original = $originals[$i];
                $replacements = array();
                foreach($excluded_words_from_automatic_translation as $s) {
                    $replacements += self::strpos_all($original, $s); 
                }
                ksort($replacements);
                $translations[$i] = preg_replace_callback('/1TP[0-9]+T/', function($matches) use (&$replacements) {
                    return array_shift($replacements);
                }, $translations[$i]);
            }
        }
        return $translations;
    }
    
    private static function strpos_all($haystack, $needle) {
        $offset = 0;
        $allpos = array();
        while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
            $offset   = $pos + 1;
            $allpos[$pos] = $needle;
        }
        return $allpos;
    }
    
    private function get_incomplete_db_entry($id) {
        return $this->db->get_row("SELECT * FROM $this->jobs_table WHERE id = '$id' AND status IN ('TRANSLATING', 'TIMEOUT')");
    }

}