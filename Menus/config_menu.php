<?php

function create_configure_menu( $query = false) {
	global $alphred, $separator, $icon_suffix;

	$username = ( $alphred->config_read( 'username' ) ) ? $alphred->config_read( 'username' ) : 'not set';
	$password =	( $alphred->get_password( 'packal.org' ) ) ? 'Password is set.' : 'Password is not set.';
	$authorname = ( $alphred->config_read( 'authorname' ) ) ? $alphred->config_read( 'authorname' ) : 'not set';

	$alphred->add_result([
		'title' => 'Set Packal.org Username',
		'uid' => 'packal-configure-username',
		'arg' => 'packal-configure-username',
		'subtitle' => "Current: {$username}.",
		'autocomplete' => "configure{$separator}username{$separator}",
		'valid' => false,
	]);
	$alphred->add_result([
		'title' => 'Set Packal.org Password',
		'uid' => 'packal-configure-password',
		'subtitle' => $password,
		'arg' => json_encode([ 'action' => 'configure', 'target' => 'password' ]),
		'valid' => true,
	]);
	$alphred->add_result([
		'title' => 'Set Author Name',
		'uid' => 'packal-configure-author-name',
		'subtitle' => "Current: {$authorname}.",
		'arg' => json_encode([ 'action' => 'configure', 'target' => 'authorname' ]),
		'autocomplete' => "configure{$separator}authorname{$separator}",
		'valid' => false,
	]);
	$alphred->add_result([
		'title' => 'Set Blacklist Options',
		'uid' => 'packal-configure-blacklist',
		'arg' => 'packal-configure-blacklist',
		'autocomplete' => "configure{$separator}blacklist{$separator}",
		'valid' => false,
	]);
}

function config_set_username_menu( $query = false ) {
	global $alphred, $separator, $icon_suffix, $original_query;
	$subtitle = 'Current: ' . $alphred->config_read( 'username' ) . '. ';

	// We need to do this below to get the case correct for the name
	$name = end( explode( $separator, $original_query ) );
	$title = 'Set Packal username to: ';
	if ( $query ) {
		$title = "{$title}`{$name}`";
	}
	$valid = false;
	if ( strlen( $query ) > 3 ) {
		$valid = true;
	}
	if ( ! $valid ) {
		$subtitle .= "Keep typing for a valid username.";
	} else {
		$subtitle .= '';
	}
	$alphred->add_result([
		'title'    => $title,
		'subtitle' => $subtitle,
		'valid'    => $valid,
		'arg'      => json_encode([ 'action' => 'configure', 'target' => 'username', 'value' => $name ]),
	]);
}

function config_set_authorname_menu( $query = false ) {
	global $alphred, $separator, $icon_suffix, $original_query;
	$subtitle = 'Current: ' . $alphred->config_read( 'authorname' ) . '. ';

	// We need to do this below to get the case correct for the name
	$name = end( explode( $separator, $original_query ) );
	$title = 'Set Author Name to: ';
	if ( $query ) {
		$title = "{$title}`{$name}`";
	}
	$valid = false;
	if ( strlen( $query ) > 2 ) {
		$valid = true;
	}
	if ( ! $valid ) {
		$subtitle .= "Keep typing for a valid Author Name.";
	} else {
		$subtitle .= '';
	}
	$alphred->add_result([
		'title'    => $title,
		'subtitle' => $subtitle,
		'valid'    => $valid,
		'arg'      => json_encode([ 'action' => 'configure', 'target' => 'authorname', 'value' => $name ]),
	]);
}

/**
 * [create_blacklist_menu description]
 *
 * This menu also has an action. If the autocomplete contains a 'true' or 'false',
 * then it will add or remove the workflow in the updater blacklist. While this
 * sort of violates the principle of functions having a single action, it does
 * make the user interaction much nicer. I could probably factor some of the logic
 * out of this function, but I'll save that for when I refactor this entire file
 * into something more OOP-esque.
 *
 * @param  boolean|string $query The Alfred query
 */
function create_blacklist_menu( $query = false ) {
	global $alphred, $separator, $icon_suffix;
	if ( strpos( $query, ' - false' ) ) {
		$action = 'whitelist';
	} else if ( strpos( $query, ' - true' ) ) {
		$action = 'blacklist';
	}

	$query = str_replace( ' - false', '', $query );
	$query = str_replace( ' - true', '', $query );
	$workflows = json_decode(
    file_get_contents(
      "{$_SERVER['alfred_workflow_data']}/data/workflows/workflow_map.json"
    ),
    true
	);

	if ( file_exists( "{$_SERVER['alfred_workflow_data']}/data/workflows/blacklist.json" ) ) {
		$blacklist = json_decode(
		  file_get_contents( "{$_SERVER['alfred_workflow_data']}/data/workflows/blacklist.json" ),
		  true
		);
	} else {
		$blacklist = [];
		file_put_contents(
		  "{$_SERVER['alfred_workflow_data']}/data/workflows/blacklist.json",
		  json_encode( $blacklist, JSON_PRETTY_PRINT )
		);
	}
	if ( $query ) {
		foreach ( $workflows as $workflow ) :
			if ( isset( $workflow['name'] ) && strtolower( $query ) == strtolower( $workflow['name'] ) ) {
				if ( isset( $action ) && 'blacklist' == $action ) {
					$blacklist[ $query ] = true;
				} else if ( isset( $action ) && 'whitelist' == $action ) {
				 $blacklist[ $query ] = false;
				}
				file_put_contents(
				  "{$_SERVER['alfred_workflow_data']}/data/workflows/blacklist.json",
				  json_encode( $blacklist, JSON_PRETTY_PRINT )
				);
				break;
			}
		endforeach;
	}

	$workflows = $alphred->filter(
	  $workflows,
	  $query,
	  'name',
	  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
	);

	foreach ( $workflows as $workflow ) :
		if ( $workflow['packal'] ) {
			if ( isset( $blacklist[ strtolower( $workflow['name'] ) ] ) && $blacklist[ strtolower( $workflow['name'] ) ] ) {
				$icon = __DIR__ . '/assets/images/icons/bullet-dark.png';
				$subtitle = 'Update ' . $workflow['name'] . ' via the Packal updater';
				$title = 'Whitelist ' . $workflow['name'];
				$autocomplete = "configure{$separator}blacklist{$separator}{$workflow['name']} - false";
			} else {
				$icon = __DIR__ . '/assets/images/icons/bullet-light.png';
				$subtitle = 'Do not update ' . $workflow['name'] . ' via the Packal updater';
				$title = 'Blacklist ' . $workflow['name'];
				$autocomplete = "configure{$separator}blacklist{$separator}{$workflow['name']} - true";
			}
			$alphred->add_result([
				'title' => $title,
				'subtitle' => $subtitle,
				'icon' => $icon,
				'valid' => false,
				'autocomplete' => $autocomplete,
			]);
		}
	endforeach;
}