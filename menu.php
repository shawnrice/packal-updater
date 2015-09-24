<?php
/**
 *
 * Okay, so what I should really do is to create a new class that can eventually
 * be included in Alphred. This class should be something does makes menus
 * recursively out of JSON definitions. So, it would allow for an infinite
 * number of submenus and do an "autocomplete" search filter on the potential
 * submenus...
 *
 * Either JSON or an Array. But, it's turning out to be a bit harder than I thought
 * in order to do that. So... abstracting that is delayed, for now, indefinitely.
 *
 */

require_once( __DIR__ . '/autoloader.php' );
require_once( __DIR__ . '/Libraries/BuildWorkflowMap.php' );

// Stuff for Semantic Versioning
require_once( __DIR__ . '/Libraries/php-semver/src/vierbergenlars/SemVer/expression.php' );
require_once( __DIR__ . '/Libraries/php-semver/src/vierbergenlars/SemVer/version.php' );
require_once( __DIR__ . '/Libraries/php-semver/src/vierbergenlars/SemVer/SemVerException.php' );
use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

// This is a dumb autoloader for all the different menu files.
foreach ( array_diff( scandir( __DIR__ . '/Menus' ), [ '.', '..', '.DS_Store'] ) as $file ) {
	if ( 'php' === pathinfo( $file )['extension'] ) {
		require_once( __DIR__ . "/Menus/{$file}" );
	}
}

function check_connection() {
	global $alphred;
	return $alphred->get( PACKAL_BASE_API_URL . 'ping', false, 0, false );
}

function main( $argv ) {
	global $alphred, $separator, $icon_suffix, $api_available, $endpoints, $original_query;

	$api_available = true;
	// COMMENTED FOR TESTING
	// @todo uncomment the lines below
	// if ( 'pong' !== check_connection() ) {
	// 	$alphred->add_result([
	// 		'title' => 'Cannot connect to Packal Server',
	// 		'subtitle' => 'We will attempt to use cached data if available.',
	// 		'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
	// 		'valid' => false,
	// 	]);
	// 	$api_available = false;
	// }

	$commands = [ 'search', 'submit', 'configure', 'update', 'clear-caches' ];
	$endpoints = [
		'workflow' => PACKAL_BASE_API_URL . 'workflow?all',
		'theme'    => PACKAL_BASE_API_URL . 'theme?all'
	];

	$updates = check_for_updates( $endpoints['workflow'] );

	$query = isset( $argv[1] ) ? $argv[1] : '';

	if ( false === strpos( $query, $separator ) ) {
		return create_root_menu( $alphred->filter( $commands, $query ) );
	}

	// Break it into parts
	$parts = explode( $separator, $query );

	// Trim the spaces
	array_walk( $parts, create_function( '&$val', '$val = strtolower(trim($val));' ) );
	$original_query = $query;
	foreach ( $parts as $key => $part ) :
		if ( empty( $part ) ) {
			unset( $parts[ $key ] );
		}
	endforeach;
	$parts = array_values( $parts );

	if ( 0 == count( $parts ) ) {
		return create_root_menu( $alphred->filter( $commands, $query ) );
	}

	if ( 1 == count( $parts ) ) {
		if ( 'search' == $parts[0] ) {
			return create_search_menu( false );
		} else if ( 'submit' == $parts[0] ) {
			return create_submit_menu( false );
		} else if ( 'configure' == $parts[0] ) {
			return create_configure_menu( false );
		} else if ( 'update' == $parts[0] ) {
			return create_update_menu( false, false );
		}
		return;
	}

	if ( 2 == count( $parts ) ) {
		if ( 'search' == $parts[0] ) {
			if ( 'theme' == $parts[1] ) {
				$themes = $alphred->get( $endpoints['theme'], 3600, true );
				render_themes( $themes, '' );
			} else if ( 'workflow' == $parts[1] ) {
				$workflows = $alphred->get( $endpoints['workflow'], 3600, true );
				render_workflows( $workflows, '' );
			}
		} else if ( 'submit' == $parts[0] ) {
			if ( 'theme' == $parts[1] ) {
				submit_theme_menu( false );
			} else if ( 'workflow' == $parts[1] ) {
				submit_workflow_menu( false );
			}
		} else if ( 'configure' == $parts[0] ) {
			if ( 'username' == $parts[1] ) {
				config_set_username_menu( false );
			} else if ( 'blacklist' == $parts[1] ) {
				create_blacklist_menu( false );
			}
		} else if ( 'update' == $parts[0] ) {
			if ( 'migrate' == $parts[1] ) {
				create_migrate_menu( false, true );
			}

		}
		return;
	}
	if ( 3 == count( $parts ) ) {
		if ( 'search' == $parts[0] ) {
			if ( 'theme' == $parts[1] ) {
				$themes = $alphred->get( $endpoints['theme'], 3600, true );
				render_themes( $themes, $parts[2] );
			} else if ( 'workflow' == $parts[1] ) {
				$alphred->console('Test');
				$workflows = $alphred->get( $endpoints['workflow'], 3600, true );
				render_workflows( $workflows, $parts[2] );
			}
		} else if ( 'submit' == $parts[0] ) {
			if ( 'theme' == $parts[1] ) {
				submit_theme_menu( $parts[2] );
			} else if ( 'workflow' == $parts[1] ) {
				submit_workflow_menu( $parts[2] );
			}
		} else if ( 'configure' == $parts[0] ) {
			if ( 'username' == $parts[1] ) {
				config_set_username_menu( $parts[2] );
			} else if ( 'blacklist' == $parts[1] ) {
				create_blacklist_menu( $parts[2] );
			}
		}
		return;
	}
}

function check_for_updates( $api_endpoint ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	// Retrieve the manifest of workflows, and cache it for a day.
	// $workflows = $alphred->get( $api_endpoint, 86400, true );
	// So, next we load the workflow map, and do a quick comparison to see what has already been installed
	// by Packal, and then we check to see what needs to be updated by comparing the manifest with
	// the workflow.ini files.
	//
	// This should then show whether or not updates are availble.
	return retrieve_remote_data( $api_endpoint );
}

function retrieve_remote_data( $api_endpoint ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	// This is dumb:
	if ( strpos( $api_endpoint, 'workflow' ) ) {
		$type = 'workflow';
	} else if ( strpos( $api_endpoint, 'theme' ) ) {
		$type = 'theme';
	}
	return $alphred->get( $api_endpoint, false, 86400, true );
}

function check_for_old_packal_workflows() {
	// This function will scan through the entire map and check to see if there are any old Packal
	// files (i.e. workflows downloaded from the old Packal). If there are, then it will present
	// an option to update all the options.

	// print_r( json_decode( file_get_contents( MapWorkflows::map() ), true ) );

	$workflows = [];
	return $workflows;
}

$alphred = new Alphred;

if ( DEVELOPMENT_TESTING ) {
	$alphred->add_result([
		'title' => "Environment: " . strtoupper( WORKFLOW_ENVIRONMENT ),
		'subtitle' => 'URL: ' . PACKAL_BASE_API_URL,
		'valid' => false,
	]);
}

$icon_suffix = 'light' == $alphred->theme_background() ? '-dark.png' : '-light.png';
// $separator = '>'; // $separator = '›'; // $separator = ':';
$separator = '›';
main( $argv );
$alphred->to_xml();