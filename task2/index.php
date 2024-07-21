<?php

function decryptor($string) {
    // Base64 decode the encrypted string
    $data = base64_decode($string);

    // Key and IV used in encryption
    $password = 'automaze';
    $key = substr(hash('sha256', $password, true), 0, 32);
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    
    // Check if the data is long enough to contain the IV
    if (strlen($data) < $iv_length) {
        return false;
    }
    
    $iv = substr($data, 0, $iv_length);
    
    // Ciphertext
    $ciphertext = substr($data, $iv_length);
    
    // Decrypt the data
    $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    
    return $decrypted;
}


$string = 'OtSrzlB7n3MjD01XlzM4MfNeam1Z-oCnO3kEkxptuS4';
$decrypted = decryptor($string); // return false;
if ($decrypted !== false) {
    echo $decrypted;
} else {
    echo "Decryption failed";
}
