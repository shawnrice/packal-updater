<?php

// Create the keypair
$res = openssl_pkey_new();

// Get private key
openssl_pkey_export($res, $private);

// Get public key
$public = openssl_pkey_get_details($res);

// Put the Private Key in the directory and fix perms
file_put_contents("/www/data/files/packal/resources/keys/private/$bundle.pem", $private);
chmod("/www/data/files/packal/resources/keys/private/$bundle.pem", 0600);

// Put the Public Key in the directory and fix perms
file_put_contents("/www/data/files/packal/resources/keys/public/$bundle.pub", $public["key"];);
chmod("/www/data/files/packal/resources/keys/public/$bundle.pub", 0644);