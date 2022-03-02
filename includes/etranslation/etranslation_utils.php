<?php

class TRP_eTranslation_Utils {

    public static array $punt_preceding_space = [",", ".", ";", ")", ":", "?", "!"];
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
            if (in_array($original[0], self::$punt_preceding_space) && $original[1] == $space && $translation[0] != $space && $translation[1] != $space) {
                $result = $original[0] . $space . $translation;
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
}