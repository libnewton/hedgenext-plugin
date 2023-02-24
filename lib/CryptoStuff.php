<?php

namespace OCA\HedgeNext;

use OCP\IConfig;


/**
 * Application configutarion
 *
 * @package OCA\HedgeNext
 */
class CryptoStuff {

    public static function encrypt($plaintext, $key) {
        $iv_length = openssl_cipher_iv_length('aes-256-gcm');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $key2 = base64_decode($key);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key2, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        $out = base64_encode(json_encode(array('iv' => base64_encode($iv), 'c' => base64_encode($ciphertext), 'tag' => base64_encode($tag))));
        return $out;
    }
    
    public static function decrypt($inp, $key) {
        try {
            $key2 = base64_decode($key);
            $inp = json_decode(base64_decode($inp), true);
            $iv = base64_decode($inp['iv']);
            $ciphertext = base64_decode($inp['c']);
            $tag = base64_decode($inp['tag']);
    
            $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key2, OPENSSL_RAW_DATA, $iv, $tag);
            return $plaintext;
        }
        catch (Exception $e) {
            return null;
        }

    }
 
}




?>
