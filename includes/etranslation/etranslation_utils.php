<?php

class ETM_eTranslation_Utils {

    public static $punt_preceding_space = [", ", ". ", "; ", ") ", ": ", "? ", "! "];
    private function __construct() {}

    public static function str_restore_spaces_after_translation($original, $translation) {
        $space = " ";
        $result = $translation;
        if (strlen($original) > 0 && strlen($translation) > 0) {
            if ($original[0] == $space && $translation[0] != $space) {
                $result = $space . $translation;
            }
            if (str_ends_with($original, $space) && !str_ends_with($translation, $space)) {
                $result = $result . $space;
            }
            if (in_array(substr($original, 0, 2), self::$punt_preceding_space) && !in_array(substr($translation, 0, 2), self::$punt_preceding_space)) {
                $result = substr($original, 0, 2) . ltrim($translation);
            }
        }
        return $result;
    }

    public static function arr_restore_spaces_after_translation($originals, $translations): array {
        $results = [];
        for ($i = 0; $i < count($originals); $i++) {
            $res = self::str_restore_spaces_after_translation($originals[$i], $translations[$i]);
            $results[] = $res;
        }
        return $results;
    }

    public static function encrypt_password($plaintext) {
        $parsedUrl = parse_url(get_site_url());
        $method = "AES-256-CBC";
        $key = hash('sha256', $parsedUrl['host'], true);
        $iv = openssl_random_pseudo_bytes(16);
    
        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);
    
        return base64_encode($iv . $hash . $ciphertext);
    }

    public static function decrypt_password($base64cipher) {
        $parsedUrl = parse_url(get_site_url());
        $method = "AES-256-CBC";
        $ivHashCiphertext = base64_decode($base64cipher);
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash('sha256', $parsedUrl['host'], true);
    
        if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) return null;
    
        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function get_strings_to_encode_before_translation(): array {
        $result = array();
        $letters = array("s", "d", "f", "u");
        foreach ($letters as $l) {
            $result[] = "%$l";
            for ($i = 1; $i < 10; $i++) {
                $result[] = "%$i\$$l";
            }
        }
        return array_merge($result, array('%', '$', '#', "\r\n", "\n"));
    }
}