<?php namespace Tancredi\Entity;

include_once(__DIR__.'/../init.php');

class TokenManager {
    public static function getIdFromToken($token){
        global $config;
        if (file_exists($config['first_access_tokens_dir'].$token)) {
            return trim(file_get_contents($config['first_access_tokens_dir'].$token));
        } elseif (file_exists($config['tokens_dir'].$token)) {
            $id = trim(file_get_contents($config['tokens_dir'].$token));
            TokenManager::deleteTokenForId($id,$config['first_access_tokens_dir']);
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
        global $config;
        if ($first_time_access) {
            $dir = $config['first_access_tokens_dir'];
        } else {
            $dir = $config['tokens_dir'];
        }
        TokenManager::deleteTokenForId($id,$dir);
        return file_put_contents($dir.$token,$id);
    }

    private static function getTokenFromId($id,$dir) {
        foreach (scandir($dir) as $token) {
            if ($token === '.' or $token === '..') continue;
            if (trim(file_get_contents($dir.$token)) === $id) {
                return $token;
            }
        }
        return null;
    }

    public static function getToken1($id) {
        global $config;
        return TokenManager::getTokenFromId($id,$config['first_access_tokens_dir']);
    }

    public static function getToken2($id) {
        global $config;
        return TokenManager::getTokenFromId($id,$config['tokens_dir']);
    }
}
