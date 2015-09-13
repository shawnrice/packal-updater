<?php

// This is a dumb autoloader
$files = [
						'Libraries/Alphred.phar',
						'Libraries/tweaked_alphred_request.php',
						'config.php',
						'Libraries/Pashua.php',
						'Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php',
						'Libraries/php-semver/src/vierbergenlars/SemVer/expression.php',
						'Libraries/php-semver/src/vierbergenlars/SemVer/version.php',
						'Libraries/php-semver/src/vierbergenlars/SemVer/SemVerException.php',
						'Libraries/Submit.php',
						'Libraries/generate-workflow-ini.php',
						'Libraries/BuildWorkflow.php',
				 ];

foreach( $files as $file ) :
	require_once( __DIR__ . '/' . $file );
endforeach;