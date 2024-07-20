<?php
namespace App\Helpers;
define('OPENSSL_CIPHER_NAME', 'aes-128-cbc');
define('CIPHER_KEY_LEN', 16);

class AesCipher 
{
    private static function fixKey($key) {
        if (strlen($key) < CIPHER_KEY_LEN) {
            return str_pad("$key", CIPHER_KEY_LEN, "0");
        }
        if (strlen($key) > CIPHER_KEY_LEN) {
            return substr($key, 0, CIPHER_KEY_LEN);
        }
        return $key;
    }

    static function getIV() {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(OPENSSL_CIPHER_NAME));
        return $iv;
    }

    static function encrypt($key, $iv, $data) {
        $key = hash('sha512', $key, false);
        $key = substr($key, 0, 16);
        $encodedEncryptedData = base64_encode(openssl_encrypt($data, OPENSSL_CIPHER_NAME, AesCipher::fixKey($key), OPENSSL_RAW_DATA, $iv));
        $encodedIV = base64_encode($iv);
        $encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;
        return $encryptedPayload;
    }

    static function decrypt($key, $data) {
        $key = hash('sha512', $key, false);
        $key = substr($key, 0, 16);
        // 6yhWTCML34Ri48eepWFC20O+1qgQeAlLhkbd3bEUIshSEFG91XVxC0QaznvjPA80:bbC+7ZsBFKVBwfXAAKhVoQ==
        $parts = explode(':', $data);
        $encrypted = $parts[0];
        $iv = $parts[1];
        $decryptedData = openssl_decrypt(base64_decode($encrypted), OPENSSL_CIPHER_NAME, AesCipher::fixKey($key), OPENSSL_RAW_DATA, base64_decode($iv));
        return $decryptedData;
    }
}
?>
