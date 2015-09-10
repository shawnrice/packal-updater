<?php

require_once( __DIR__ . '/includes.php' );

if ( ! isset( $argv[1] ) || ! ( $messages = json_decode( $argv[1], true ) ) ) {
	// Exit early and often
	die( 'We need some damn json, yo.' );
}

$alphred  = new Alphred;

print_r( $messages );

$alphred->send_notification([
  'title' 	 => 'Packal',
  'subtitle' => ( 'pass' == $messages[ 'status' ] ) ?
  	$messages['action'] . ' Completed' : $messages['action'] . ' Failed',
  'text' 		 => implode( "\n", $messages['messages'] ),
]);