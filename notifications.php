<?php

require_once( __DIR__ . '/includes.php' );

if ( ! isset( $argv[1] ) || ! ( $messages = json_decode( $argv[1], true ) ) ) {
	// Exit early and often
	die( 'We need some damn json, yo.' );
}

if ( 0 === count( $messages ) ) {
	exit(0);
}

$alphred  = new Alphred;

if ( isset( $messages['subtitle'] ) ) {
	$subtitle = $messages['subtitle'];
} else {
	$subtitle = ( 'pass' == $messages[ 'status' ] ) ?
		$messages['action'] . ' Completed' : $messages['action'] . ' Failed';
}

if ( isset( $messages['text'] ) ) {
	$text = $messages['text'];
} else {
	$text = implode( "\n", $messages['messages'] );
}

$alphred->send_notification([
  'title' 	 => 'Packal',
  'subtitle' => $subtitle,
  'text' 		 => $text,
]);