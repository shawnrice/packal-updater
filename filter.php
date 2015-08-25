<?php

require_once( __DIR__ . '/Alphred.phar' );
require_once( __DIR__ . '/Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php' );

use CFPropertyList\CFPropertyList as CFPropertyList;
$a = new Alphred;

$query = '';
if ( isset( $argv[1] ) ) {
	$query = $argv[1];
}

if ( ! $username = $a->config_read('username') ) {
	if ( empty( $query ) ) {
		$title = 'Set Username';
		$valid = false;
	} else {
		$title = 'Set Username to `' . $query . '`';
		$valid = true;
	}
	$a->add_result([
		'title' => 'Set Username',
		'subtitle' => 'Keep typing to set the username',
		'arg' => 'set-username' . $query
	]);
	$a->to_xml();
	die();
}


// Map workflows.
// Identify which ones you created.
// Check workflow.ini file
// Generate workflow.ini file
// Submit workflow

$me = 'Shawn Patrick Rice';

// print_r( create_workflow_map() );

function create_workflow_map() {
	$workflows = [];

	$table = [
		'name' => 'name',
		'author' => 'createdby',
		'bundle' => 'bundleid',
		'readme' => 'readme',
		'short'  => 'description',
		'url'		 => 'webaddress'
	];

	$workflows_dir = Alphred\Globals::get('alfred_preferences') . '/workflows';
	foreach ( array_diff( scandir( $workflows_dir ), [ '.', '..' ] ) as $dir ) :
		if ( file_exists( $workflows_dir . '/' . $dir . '/info.plist' ) ) {
			try {
				$plist = new CFPropertyList( $workflows_dir . '/' . $dir . '/info.plist', CFPropertyList::FORMAT_XML);
			} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

			$workflow_data = $plist->toArray();

			$workflow = [];
			$workflow['directory'] = $workflows_dir . '/' . $dir;
			foreach ( $table as $mine => $theirs ) :
				if ( isset( $workflow_data[ $theirs ] ) ) {
					$workflow[ $mine ] = $workflow_data[ $theirs ];
				} else {
					$workflow[ $mine ] = null;
				}
			endforeach;

			if ( ! empty( $workflow['name'] ) && ! empty( $workflow['bundle'] ) ) {
				$workflows[] = $workflow;
			}
		}
	endforeach;

	return $workflows;
}


$workflows = Alphred\Globals::get('alfred_preferences') . '/workflows';
foreach ( array_diff( scandir( $workflows ), [ '.', '..' ] ) as $dir ) :
	if ( file_exists( $workflows . '/' . $dir . '/info.plist' ) ) {
		$plist = new CFPropertyList( $workflows . '/' . $dir . '/info.plist', CFPropertyList::FORMAT_XML);
		$workflow = $plist->toArray();
		if ( $me === $workflow['createdby'] ) {
			$ini = false;
			$arg = 'submit-' . $workflow['bundleid'];
			if ( file_exists( $workflows . '/' . $dir . '/workflow.ini' ) ) {
				$ini = true;
			}
			$subtitle = $workflow['createdby'];
			if ( true === $ini ) {
				$subtitle .= ' WORKFLOW.INI';
				$arg = 'create-ini-' . $workflow['bundleid'];
			}
			$a->add_result([
			  'title' => $workflow['name'],
			  'subtitle' => $subtitle,
			  'icon' => $workflows . '/' . $dir . '/icon.png'
			]);
		}

	}
endforeach;

$a->to_xml();


