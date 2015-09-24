<?php

/**
 * Misc Functions
 */

function generate_uuid() {
	$chars = str_split( '0123456789ABCDEF' );
	$output = 'user.workflow.';
	foreach( [ 8, 4, 4, 4, 12 ] as $number ) :
		for( $i = 0; $i < $number; $i++ ) :
			$output .= $chars[ rand( 0, 15 ) ];
		endfor;
		$output .= '-';
	endforeach;
	return substr( $output, 0, strlen( $output ) - 1 );
}