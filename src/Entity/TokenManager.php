<?php namespace Tancredi\Entity;

class TokenManager {
    public static function getIdFromToken($token){
        global $config;
        if (file_exists($config['rw_dir'] . 'first_access_tokens/'.$token)) {
            return trim(file_get_contents($config['rw_dir'] . 'first_access_tokens/'.$token));
        } elseif (file_exists($config['rw_dir'] . 'tokens/'.$token)) {
            $id = trim(file_get_contents($config['rw_dir'] . 'tokens/'.$token));
            TokenManager::deleteTokenForId($id,$config['rw_dir'] . 'first_access_tokens/');
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

    public static function deleteToken($token) {
        global $config;
        if (file_exists($config['rw_dir'] . 'first_access_tokens/' . $token)) {
            return unlink($config['rw_dir'] . 'first_access_tokens/' . $token);
        } elseif ($config['rw_dir'] . 'tokens/' . $token) {
            return unlink($config['rw_dir'] . 'tokens/' . $token);
        }
        return false;
    }

    public static function createToken($token,$id,$first_time_access = FALSE) {
        global $config;
        if ($first_time_access) {
            $dir = $config['rw_dir'] . 'first_access_tokens/';
        } else {
            $dir = $config['rw_dir'] . 'tokens/';
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
        return TokenManager::getTokenFromId($id,$config['rw_dir'] . 'first_access_tokens/');
    }

    public static function getToken2($id) {
        global $config;
        return TokenManager::getTokenFromId($id,$config['rw_dir'] . 'tokens/');
    }
}
