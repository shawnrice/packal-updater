<?php

// Currently has all of the functions for plist migration
//require_once('plist-experiments.php');


$appcast = "appcast.xml";
$package = "caffeinate_control.alfredworkflow";
$key = "hancock";
if ( verifySignature( $appcast , $package , $key ) == 1 ) {
	echo "Package is good!\n";
}


/**
 * Checks to the signature of a package
 * @param  string 	$appcast 	an xml file containing the signature (path)
 * @param  string 	$package 	a file that has been signed (path)
 * @param  string 	$key     	the public key to use for checking (path)
 * @return [type]          [description]
 */
function verifySignature( $appcast , $package , $key ) {

	$appcast = simplexml_load_file($appcast);
	$signature = $appcast->signature;

	$data = sha1_file( $package , false );

	// fetch public key from certificate and ready it
	$fp = fopen( $key , 'r' );
	$cert = fread( $fp , filesize($key) );
	fclose( $fp );

	// Get the public key
	$id = openssl_get_publickey( $cert );

	// Get the result of the signature
	$result = openssl_verify( $data , base64_decode( $signature ) , $id , OPENSSL_ALGO_SHA1 );

	// Free key from memory
	openssl_free_key( $id );

	// Return the result
	return $result;

	// 1: okay
	// 0: bad
	// other: wtf?
}
