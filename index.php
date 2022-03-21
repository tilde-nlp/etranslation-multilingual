<?php
/*
Plugin Name: eTranslation Multilingual
Plugin URI: https://ec.europa.eu/info/index_en
Description: Experience a better way of translating your WordPress site using a visual front-end translation editor, with full support for WooCommerce and site builders.
Version: 0.0.1
Author: EC
Author URI: https://ec.europa.eu
Text Domain: translatepress-multilingual
Domain Path: /languages
License: GPL2
WC requires at least: 2.5.0
WC tested up to: 6.2

== Copyright ==

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Register callback methods for eTranslation
add_action('rest_api_init', 'register_callback');

function register_callback() {
    register_rest_route('etranslation/v1', 'error_callback/(?P<id>[a-zA-Z0-9._-]+)', array(
        'methods' => array(
            'GET',
            'POST'
        ) ,
        'callback' => 'translation_error_callback',
        'args' => array() ,
        'permission_callback' => function () {
            return true;
        }
    ));
    register_rest_route('etranslation/v1', 'document/destination/(?P<id>[a-zA-Z0-9._-]+)', array(
        'methods' => array(
            'GET',
            'POST'
        ) ,
        'callback' => 'translation_document_destination',
        'args' => array() ,
        'permission_callback' => function () {
            return true;
        }
    ));
}

function translation_error_callback(WP_REST_Request $request): WP_REST_Response {
    $id = $request['id'];
    $db_entry = get_incomplete_db_entry($id);

    if ($db_entry != null) {
        global $wpdb;
        $wp_table = $wpdb->prefix . ETRANSLATION_TABLE;
        $error_code = $request->get_param('error_code');

        $update_result = $wpdb->update($wp_table, array(
            'status' => 'ERROR',
            'body' => $error_code
        ) , array(
            'id' => $id
        ));

        if (!$update_result) {
            error_log("Error updating eTranslation DB entry [ID=$id]");
        }
    } else {
        error_log("Translation error received has no entry in database [ID=$id]");
    }

    $error_message = $request->get_param('error-message');
    error_log("Error translating document with eTranslation: $error_message [code: $error_code] ID=$id");

    $response = new WP_REST_Response(array($id, $error_code));
    $response->set_status(200);

    return $response;
}

function translation_document_destination(WP_REST_Request $request): WP_REST_Response {
    $id = $request['id'];
    $db_row = get_incomplete_db_entry($id);

    if ($db_row != null) {
        global $wpdb;
        $wp_table = $wpdb->prefix . ETRANSLATION_TABLE;
        $translation = base64_decode($request->get_body());

        if ($db_row->status == 'TIMEOUT') {
            //timeout has been reached, update translation manually & delete row from job table
            $manually_updated = update_translation_manually($db_row, $translation);
            if ($manually_updated) {
                $deletion = $wpdb->delete($wp_table, array(
                    'id' => $db_row->id
                ));
                if (!$deletion) {
                    error_log("Could not delete $wp_table row after updating translation manually [ID=$db_row->id]");
                }
            }
        } else {
            //translation completed in time
            $update_result = $wpdb->update($wp_table, array(
                'status' => 'DONE',
                'body' => $translation
            ) , array(
                'id' => $id
            ));

            if (!$update_result) {
                error_log("Error updating eTranslation DB entry [ID=$id]");
            }
        }
    } else {
        error_log("Translation received has no entry in database [ID=$id]");
    }

    $response = new WP_REST_Response(array($id, $request->get_body()));
    $response->set_status(200);

    return $response;
}

function update_translation_manually($details_row, $translations): bool {
    $trp = TRP_Translate_Press::get_trp_instance();
    $trp_query = $trp->get_component( 'query' );
    $trp_settings = $trp->get_component( 'settings' )->get_settings();
    $default_language = strtolower($trp_settings['default-language']);
    global $wpdb;
    $dict_table = $wpdb->get_row("SELECT TABLE_NAME AS tname FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE 'wp_etm_dictionary_" . $default_language . "_$details_row->to%'");
    if ($dict_table != null) {
        $delimiter = "\n";
        $target_language_code = explode($default_language . "_", $dict_table->tname)[1];
        $original_strings = explode($delimiter, $details_row->original);
        $decoded_translations = decode_untranslated_symbols(explode($delimiter, $translations), $original_strings);
        $translation_strings = TRP_eTranslation_Utils::arr_restore_spaces_after_translation($original_strings, $decoded_translations);

        //insert original strings in table if they don't exist        
        $original_inserts = $trp_query->original_strings_sync($target_language_code, $original_strings);

        $max_id = $wpdb->get_row("SELECT MAX(id) as id FROM $dict_table->tname")->id;
        $next_id = intval($max_id) + 1;

        $update_strings = array();
        for ( $i = 0; $i < count($original_strings); $i++ ) {
            $string = $original_strings[$i];
            array_push( $update_strings, array(
                'id'          => $next_id + $i,
                'original_id' => $original_inserts[ $string ]->id,
                'original'    => $string,
                'translated'  => trp_sanitize_string( $translation_strings[ $i ] ),
                'status'      => $trp_query->get_constant_machine_translated() ) );
        }

        //insert translations
        $trp_query->update_strings( $update_strings, $target_language_code, array( 'id', 'original', 'translated', 'status', 'original_id' ) );

        //delete previously inserted untranslated rows 
        $trp_query->remove_possible_duplicates($update_strings, $target_language_code, 'regular');
    } else {
        error_log("Could not find dictionary table by languages ($details_row->from, $details_row->to) [ID=$details_row->id]");
    }
    return false;
}

function decode_untranslated_symbols($translations, $originals) {
    $excluded_words_from_automatic_translation = apply_filters('trp_exclude_words_from_automatic_translation', TRP_eTranslation_Utils::get_strings_to_encode_before_translation());
    for ($i = 0; $i < count($translations); $i++) {
        $translation = $translations[$i];
        //check if decoding needed
        if (str_contains($translation, "1TP")) {
            $original = $originals[$i];
            $replacements = array();
            foreach($excluded_words_from_automatic_translation as $s) {
                $replacements += strpos_all($original, $s); 
            }
            ksort($replacements);
            $translations[$i] = preg_replace_callback('/1TP[0-9]+T/', function($matches) use (&$replacements) {
                return array_shift($replacements);
            }, $translations[$i]);
        }
    }
    return $translations;
}

function strpos_all($haystack, $needle) {
    $offset = 0;
    $allpos = array();
    while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
        $offset   = $pos + 1;
        $allpos[$pos] = $needle;
    }
    return $allpos;
}

function get_incomplete_db_entry($id) {
    global $wpdb;
    $wp_table = $wpdb->prefix . ETRANSLATION_TABLE;
    return $wpdb->get_row("SELECT * FROM $wp_table WHERE id = '$id' AND status IN ('TRANSLATING', 'TIMEOUT')");
}

function trp_enable_translatepress(){
	$enable_translatepress = true;
	$current_php_version = apply_filters( 'trp_php_version', phpversion() );

	// 5.6.20 is the minimum version supported by WordPress
	if ( $current_php_version !== false && version_compare( $current_php_version, '5.6.20', '<' ) ){
		$enable_translatepress = false;
		add_action( 'admin_menu', 'trp_translatepress_disabled_notice' );
	}

	return apply_filters( 'trp_enable_translatepress', $enable_translatepress );
}

defined('ETRANSLATION_TABLE') or define('ETRANSLATION_TABLE', 'etm_etranslation_jobs');

if ( trp_enable_translatepress() ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-translate-press.php';

	/** License classes includes here
	 * Since version 1.4.6
	 * It need to be outside of a hook so it load before the classes that are in the addons, that we are trying to phase out
	 */
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-edd-sl-plugin-updater.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/etranslation/etranslation_utils.php';

    create_etranslation_database_table();

	/* make sure we execute our plugin before other plugins so the changes we make apply across the board */
	add_action( 'plugins_loaded', 'trp_run_translatepress_hooks', 1 );
}

function create_etranslation_database_table() {
    global $wpdb;
    $wp_track_table = $wpdb->prefix . ETRANSLATION_TABLE;

    #Check to see if the table exists already, if not, then create it
    if ($wpdb->get_var("show tables like '$wp_track_table'") != $wp_track_table) {
        $sql = "CREATE TABLE `" . $wp_track_table . "` ( ";
        $sql .= "  `id`  VARCHAR(13) NOT NULL, ";
        $sql .= " `requestId` BIGINT NOT NULL, ";
        $sql .= "  `status`  ENUM('TRANSLATING','DONE','ERROR','TIMEOUT') NOT NULL DEFAULT 'TRANSLATING', ";
        $sql .= "  `body`  TEXT NULL DEFAULT NULL, ";
        $sql .= " `from` VARCHAR(5) NULL, ";
        $sql .= " `to` VARCHAR(200) NULL, ";
        $sql .= " `original` TEXT NULL, ";
        $sql .= " `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ";
        $sql .= "  PRIMARY KEY (`id`) ";
        $sql .= ") COLLATE='utf8mb4_unicode_520_ci'; ";
        require_once (ABSPATH . '/wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);

        if (!$result) {
            error_log("Error creating eTranslation DB");
        }
    }
}

function trp_run_translatepress_hooks(){
	$trp = TRP_Translate_Press::get_trp_instance();
	$trp->run();
}

function trp_translatepress_disabled_notice(){
	echo '<div class="notice notice-error"><p>' . wp_kses( sprintf( __( '<strong>eTranslation Multilingual</strong> requires at least PHP version 5.6.20+ to run. It is the <a href="%s">minimum requirement of the latest WordPress version</a>. Please contact your server administrator to update your PHP version.','translatepress-multilingual' ), 'https://wordpress.org/about/requirements/' ), array( 'a' => array( 'href' => array() ), 'strong' => array() ) ) . '</p></div>';
}


//This is for the DEV version
if( file_exists(plugin_dir_path( __FILE__ ) . '/index-dev.php') )
    include_once( plugin_dir_path( __FILE__ ) . '/index-dev.php');