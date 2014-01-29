<?php

// the data
$data = sha1_file(dirname(__FILE__) . '/testsign.jpg', false);
echo "\nSHA of Data: $data\n\n";
$signature = null;

////////////////////////////
// SIGN
////////////////////////////

// fetch public key from certificate and ready it
$fp = fopen(dirname(__FILE__) . '/private.pem', "r");
$cert = fread($fp, 8192);
fclose($fp);
$privateid = openssl_get_privatekey($cert);

$sigraw = null;
openssl_sign($data, $sigraw, $privateid, OPENSSL_ALGO_SHA1);

$signature = base64_encode($sigraw);

echo "Signature: $signature\n\n";

////////////////////////////
// VERIFY
////////////////////////////

// fetch public key from certificate and ready it
$fp = fopen(dirname(__FILE__) . '/public.pem', "r");
$cert = fread($fp, 8192);
fclose($fp);
$pubkeyid = openssl_get_publickey($cert);

// state whether signature is okay or not
$ok = openssl_verify($data, base64_decode($signature), $pubkeyid, OPENSSL_ALGO_SHA1);
if ($ok == 1) {
    echo "** Verified OK **\n\n";
} elseif ($ok == 0) {
    echo "Bad\n\n";
} else {
    echo "Ugly, error checking signature\n\n";
}
// free the key from memory
openssl_free_key($pubkeyid);

?>
