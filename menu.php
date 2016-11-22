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

// Checks to make sure that Packal is reachable via grabbing a very low-payload URL off Packal
function check_connection() {
	global $alphred;
	return $alphred->get( BASE_URL . '/ping', false, 0, false );
}

function main( $argv ) {
	global $alphred, $separator, $icon_suffix, $api_available, $endpoints, $original_query;

	// Check to see if
	$api_available = ( 'pong' === check_connection() ) ? true : false;

	if ( ! $api_available ) {
		$alphred->add_result([
			'icon'     => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
			'subtitle' => 'Attempting to use cached data.',
			'title'    => 'Cannot connect to Packal Server',
			'valid'    => false,
		]);
	}

	$commands  = [ 'search', 'submit', 'configure', 'update', 'clear-caches' ];
	$endpoints = [
		'workflow' => BASE_API_URL . 'workflow?all',
		'theme'    => BASE_API_URL . 'theme?all',
	];

	$updates = check_for_updates( $endpoints['workflow'] );

	$query = isset( $argv[1] ) ? $argv[1] : '';

	if ( false === strpos( $query, $separator ) ) {
		return create_root_menu( $alphred->filter( $commands, $query ) );
	}

	// Break it into parts
	$parts = explode( $separator, $query );

	// Trim the spaces
	$parts = array_map( 'trim', $parts );
	$parts = array_map( 'strtolower', $parts );

	$original_query = $query;
	foreach ( $parts as $key => $part ) :
		if ( empty( $part ) ) {
			unset( $parts[ $key ] );
		}
	endforeach;
	$parts = array_values( $parts );

	switch ( count( $parts ) ) :
		case 0:
			return create_root_menu( $alphred->filter( $commands, $query ) );

		case 1:
			switch ( $parts[0] ) :
				case 'search':
					return create_search_menu( false );
				case 'submit':
					return create_submit_menu( false );
				case 'configure':
					return create_configure_menu( false );
				case 'update':
					return create_update_menu( false, false );
				default:
					return;
			endswitch;

		case 2:
			switch ( $parts[0] ) :
				case 'search':
					switch ( $parts[1] ) :
						case 'theme':
							$themes = $alphred->get( $endpoints['theme'], 3600, true );
							return render_themes( $themes, '' );
						case 'workflow':
							$workflows = $alphred->get( $endpoints['workflow'], 3600, true );
							return render_workflows( $workflows, '' );
						default:
							return;
					endswitch;
				case 'submit':
					switch ( $parts[1] ) :
						case 'theme':
							return submit_theme_menu( false );
						case 'workflow':
							return submit_workflow_menu( false );
						default:
							return;
					endswitch;
				case 'configure':
					switch ( $parts[1] ) :
						case 'username':
							return config_set_username_menu( false );
						case 'authorname':
							return config_set_authorname_menu( false );
						case 'blacklist':
							return create_blacklist_menu( false );
						default:
							return;
					endswitch;
				case 'update':
					if ( 'migrate' === $parts[1] ) {
						return create_migrate_menu( false, true );
					}
					return;
				default:
					return;
			endswitch;

		case 3:
			switch ( $parts[0] ) :
				case 'search' :
					switch ( $parts[1] ) :
						case 'theme':
							$themes = $alphred->get( $endpoints['theme'], 3600, true );
							return render_themes( $themes, $parts[2] );
						case 'workflow':
							$alphred->console( 'Test' );
							$workflows = $alphred->get( $endpoints['workflow'], 3600, true );
							return render_workflows( $workflows, $parts[2] );
						default :
							return;
					endswitch;
				case 'submit' :
					switch ( $parts[1] ) :
						case 'theme':
							return submit_theme_menu( $parts[2] );
						case 'workflow':
							return submit_workflow_menu( $parts[2] );
						default:
							return;
					endswitch;
				case 'configure' :
					switch ( $parts[1] ) :
						case 'username':
							return config_set_username_menu( $parts[2] );
						case 'authorname':
							return config_set_authorname_menu( $parts[2] );
						case 'blacklist':
							return create_blacklist_menu( $parts[2] );
						default:
							return;
					endswitch;
			endswitch;

		default :
			return;
	endswitch;
}

function check_for_updates( $api_endpoint ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	// Retrieve the manifest of workflows, and cache it for a day.
	// i.e.: $workflows = $alphred->get( $api_endpoint, 86400, true );
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
	} elseif ( strpos( $api_endpoint, 'theme' ) ) {
		$type = 'theme';
	}
	return $alphred->get( $api_endpoint, [], 86400, true );
}

function check_for_old_packal_workflows() {
	// This function will scan through the entire map and check to see if there are any old Packal
	// files (i.e. workflows downloaded from the old Packal). If there are, then it will present
	// an option to update all the options.

	// print_r( json_decode( file_get_contents( MapWorkflows::map() ), true ) );

	$workflows = [];
	return $workflows;
}

function check_old_connection() {
	if ( file_exists( __CACHE__ . '/connection.txt' ) ) {
		if ( 5 * 60 < time() - filemtime( __CACHE__ . '/connection.txt' ) ) {
			return true;
		}
	}

	$ch = curl_init( 'https://raw.githubusercontent.com/packal/repository/master/manifest.xml' );
	curl_setopt( $ch , CURLOPT_FILETIME, true );
	curl_setopt( $ch , CURLOPT_NOBODY, true );
	curl_setopt( $ch , CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch , CURLOPT_HEADER, true );
	$header = curl_exec( $ch );
	$info   = curl_getinfo( $ch );
	curl_close( $ch );
	file_put_contents( __CACHE__ . '/connection.txt', 'conn' );
	return 200 === $info['http_code'];
}

function old( $argv ) {
	global $alphred, $separator, $icon_suffix, $original_query;
	if ( check_old_connection() );
}

$alphred = new Alphred;

if ( DEVELOPMENT_TESTING ) {
	$alphred->add_result( [
		'icon'     => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/BurningIcon.icns',
		'subtitle' => 'URL: ' . BASE_API_URL,
		'title'    => 'Environment: ' . strtoupper( ENVIRONMENT ),
		'valid'    => false,
	] );
}

$icon_suffix = ( 'light' === $alphred->theme_background() ) ? '-dark.png' : '-light.png';
// $separator = '›';
$separator = '»';

if ( __LEGACY__ ) {
	old( $argv );
	$alphred->to_xml();
	exit( 0 );
}

main( $argv );
$alphred->to_xml();
