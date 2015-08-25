<?php

// This is a file
function create_root_menu( $possible ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	if ( in_array( 'search', $possible ) ) {
		$alphred->add_result([
			'title' => 'Search Themes or Workflows',
			'uid' => 'packal-search',
			'autocomplete' => "search{$separator}",
			'valid' => false,
		]);
	}
	if ( in_array( 'submit', $possible ) ) {
		if ( $api_available ) {
				$alphred->add_result([
				  'title' => 'Submit a Theme or a Workflow',
				  'uid' => 'packal-submit',
					'autocomplete' => "submit{$separator}",
					'valid' => false,
				]);
		} else {
				$alphred->add_result([
				  'title' => 'Submit a Theme or a Workflow',
				  'uid' => 'packal-submit',
					'autocomplete' => '',
					'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
					'valid' => false,
				]);
		}
	}
	if ( in_array( 'configure', $possible ) ) {
		$alphred->add_result([
		  'title' => 'Configure Packal Workflow',
		  'uid' => 'packal-configure',
			'autocomplete' => "configure{$separator}",
			'valid' => false,
		]);
	}
	if ( in_array( 'update', $possible ) ) {
		$alphred->add_result([
		  'title' => 'Update Workflows',
		  'uid' => 'packal-update',
			'autocomplete' => "update{$separator}",
			'valid' => false,
		]);
	}
	// Add in the clear-caches ability to remove __ALL__ data
	if ( in_array( 'clear-caches', $possible ) ) {
		$alphred->add_result([
		  'title' => 'Clear Packal Caches',
		  'uid' => 'packal-clear-caches',
			'arg' => json_encode([ 'action' => 'clear-caches' ]),
			'valid' => true,
		]);
	}
}
