<?php

function create_update_menu( $query = false, $full = false ) {
	global $alphred, $separator, $icon_suffix, $api_available, $endpoints;

	// This checks to see if they've set a different config item for how long they
	// want their workflow map cache to live. If so, we'll use that.
	$ttl = $alphred->config_read( 'workflow_map_cache' );

	$workflows = json_decode( file_get_contents( MapWorkflows::map( true, $ttl ) ), true );
	$remote_workflows = check_for_updates( $endpoints['workflow'] );

	//
	// This is the path to the file that contains the list of workflows that need to be migrated.
	// "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json",
	create_migrate_menu( $query, $full );

	find_updates( $workflows );
}

/**
 * Create Menu if needing to migrate workflows from the old packal to the new one
 * @param  boolean $query [description]
 * @param  boolean $full  [description]
 * @return [type]         [description]
 */
function create_migrate_menu( $query = false, $full = false ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	$ttl = $alphred->config_read( 'workflow_map_cache' );
	$workflows = MapWorkflows::map( $ttl );

	$old_packal_workflows = json_decode( file_get_contents( "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json" ), true );

	if ( ! ( is_array( $old_packal_workflows ) && count( $old_packal_workflows ) > 0 ) ) {
		return;
	}

	$alphred->console( 'There are workflows from the old Packal that need migrating.', 2 );

	$alphred->add_result([
		'title' => 'Migrate to the new Packal',
		'subtitle' => 'You have workflows that need to be redownloaded from the new Packal in order to continue updating them.',
		'valid' => false,
		'autocomplete' => "update{$separator}migrate",
	]);
	if ( $full ) {
		$alphred->add_result([
			'title' => 'Migrate all workflows',
			'valid' => true,
			'arg'   => json_encode([ 'action' => 'migrate-old-packal-workflows' ]),
		]);
		foreach ( $old_packal_workflows as $workflow ) :
			$alphred->add_result([
				'title' => "Migrate `{$workflow['name']}`",
				'valid' => true,
				'arg' 	=> json_encode([ 'action' => 'migrate-workflow', 'workflow' => $workflow ]),
			]);
		endforeach;
	}

}

function find_updates( $workflows ) {
	global $alphred, $separator, $icon_suffix, $api_available, $endpoints;
	$updates = json_decode( retrieve_remote_data( $endpoints['workflow'] ), true );
	print_r( $updates );
	foreach ( $workflows as $key => $workflow ) :
		if ( ! $workflow['packal'] ) {
			unset( $workflows[$key] );
		} else {
			$ini = \Alphred\Ini::read_ini( $workflow['path'] . '/workflow.ini' );
			// if ( isset( $))
			$version = $ini['workflow']['version'];
			$icon = $workflow['path'] . '/icon.png';
			$alphred->add_result([
				'title' => $workflow['name'],
				'icon' => $icon,
				'subtitle' => "Current version: {$version}",
			]);
		}
	endforeach;


	$alphred->add_result([
		'title' => 'All workflows up-to-date.',
		'valid' => false,
  ]);
}

