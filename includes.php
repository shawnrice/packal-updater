<?php

// This is a dumb autoloader
$files = [
						'test_vars.php',
						'Libraries/Alphred.phar',
						'Libraries/tweaked_alphred_request.php',
						'config.php',
				 ];

foreach( $files as $file ) :
	require_once( __DIR__ . '/' . $file );
endforeach;