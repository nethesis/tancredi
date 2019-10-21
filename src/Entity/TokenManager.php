<?php namespace Tancredi\Entity;

define ("FIRST_ACCESS_TOKENS_DIR", "/usr/share/nethvoice/tancredi/data/first_access_tokens/");
define ("TOKENS_DIR", "/usr/share/nethvoice/tancredi/data/tokens/");

class TokenManager {
    public static function getIdFromToken($token){
        if (file_exists(FIRST_ACCESS_TOKENS_DIR.$token)) {
            return trim(file_get_contents(FIRST_ACCESS_TOKENS_DIR.$token));
        } elseif (file_exists(TOKENS_DIR.$token)) {
            $id = trim(file_get_contents(TOKENS_DIR.$token));
            TokenManager::deleteTokenForId($id,FIRST_ACCESS_TOKENS_DIR);
            return $id;
        } else {
            // Token not found
            return false;
        }
    }

    public static function deleteTokenForId($id,$dir) {
        foreach (scandir($dir) as $token) {
            if ($token === '.' or $token === '..') continue;
            if (file_get_contents($dir.$token) === $id) {
                return unlink($dir.$token);
            }
        }
    }

    public static function createToken($token,$id,$first_time_access = FALSE) {
        if ($first_time_access) {
            $dir = FIRST_ACCESS_TOKENS_DIR;
        } else {
            $dir = TOKENS_DIR;
        }
        TokenManager::deleteTokenForId($id,$dir);
        return file_put_contents($dir.$token,$id);
    }
}
