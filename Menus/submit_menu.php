<?php

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

function create_submit_menu( $possible ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	if ( $api_available ){
		$alphred->add_result([
	  	'title' => 'Submit a Theme',
	  	'uid' => 'packal-submit-theme',
	  	'autocomplete' => "submit{$separator}theme{$separator}",
	  	'valid' => false,
		]);
		$alphred->add_result([
	  	'title' => 'Submit a Workflow',
	  	'uid' => 'packal-submit-workflow',
	  	'autocomplete' => "submit{$separator}workflow{$separator}",
	  	'valid' => false,
		]);
	} else {
		$alphred->add_result([
	  	'title' => 'Submit a Workflow',
	  	'uid' => 'packal-submit-workflow',
	  	'subtitle' => 'Cannot connect to server, and so we cannot submit a workflow',
	  	'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
	  	'autocomplete' => "submit{$separator}workflow{$separator}",
	  	'valid' => false,
		]);
		$alphred->add_result([
	  	'title' => 'Submit a Theme',
	  	'uid' => 'packal-submit-theme',
	  	'subtitle' => 'Cannot connect to server, and so we cannot submit a theme',
	  	'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
	  	'autocomplete' => "submit{$separator}theme{$separator}",
	  	'valid' => false,
		]);
	}
}

function submit_theme_menu( $query = false ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	$me = $alphred->config_read( 'username' );
	$themes = array_values( encode_themes( get_themes(), $me ) );
	foreach( $themes as $theme ) :
		$alphred->add_result([
		  'title' => "Submit {$theme['name']} to Packal.org",
		  'valid' => true,
		  'arg'  => json_encode( [ 'action' => 'submit_theme', 'theme' => $theme ] ),
		]);
	endforeach;
}


function submit_workflow_menu( $query = false ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	$packal_workflows = get_packal_workflows();
	$me = $alphred->config_read( 'username' );
	$ttl = $alphred->config_read( 'workflow_map_cache' );

	// I should look to make sure that this is called somewhere earlier.
	MapWorkflows::map( true, $ttl );
	$workflows = json_decode( file_get_contents( MapWorkflows::my_workflows_path() ), true );

	// Filter down the workflows
	$workflows = $alphred->filter( $workflows, $query, 'name', [ 'match_type' => DEFAULT_FILTER_PARAMS ] );

	foreach( $workflows as $workflow ) :
		$valid = true;
		$arg = false;

		// Make sure the workflow.ini file exists
		if ( file_exists( $workflow['path'] . '/workflow.ini') ) {
			if ( true === ( $subtitle = validate_workflow_ini_file( $workflow, $packal_workflows ) ) ) {
				$subtitle = 'Ready to submit.';
				$arg = json_encode([
					'action' => 'submit_workflow',
					'path' => $workflow['path'],
				]);
			} else {
				$subtitle = "Edit workflow.ini: {$subtitle}";
			}
		} else {
			// There is no workflow.ini file, so we will generate one.
			$subtitle = 'Generate workflow.ini file';
			$arg = json_encode([ 'action' => 'generate_ini', 'path' => $workflow['path'] ]);
		}

		$alphred->add_result([
			'title' => "Submit `{$workflow['name']}` to Packal.org",
			'icon' => "{$workflow['path']}/icon.png",
			'subtitle' => $subtitle,
			'valid' => $valid,
			'arg' => $arg,
		]);
	endforeach;
}

function get_packal_workflows() {
	global $alphred, $endpoints;
	return $alphred->get( $endpoints['workflow'], 3600, true );
}

function validate_workflow_ini_file( $workflow, $packal_workflows ) {
	global $alphred;
	$ini = $workflow['path'] . '/workflow.ini';
	foreach( json_decode( $packal_workflows, true )['workflows'] as $w ) :
		if ( ! isset( $w['bundle'] ) ) {
			continue;
		}
		if ( $workflow['bundle'] != $w['bundle'] ) {
			continue;
		} else {
			$alphred->console( "VERSION: {$w['version']}", 4 );
			$packal_version = $w['version'];
			break;
		}
	endforeach;

	$ini = \Alphred\Ini::read_ini( $ini );
	// Check for general workflow section in ini file
	if ( ! isset( $ini['workflow'] ) ) {
		return 'No workflow section in workflow.ini';
	}
	// Check to make sure a version is set in workflow ini file
	if ( ! isset( $ini['workflow']['version'] ) ) {
		return 'No version is set in workflow.ini';
	}
	// Check to make sure that there is a packal section in the workflow.ini file,
	// although we will be forgiving if it isn't filled out, although it should
	// be.
	if ( ! isset( $ini['packal'] ) ) {
		return 'No Packal section in the workflow.ini file';
	}
	// Make sure that if there is already a version on Packal that the submission
	// will be an update so that the updater functionality actually works correctly.
	if ( isset( $packal_version ) ) {
		if ( version::gt( $packal_version, $ini['workflow']['version'] ) ) {
			return true;
		} else {
			// return 'A version the same or greater has already been submitted on Packal. Please update the workflow.ini file';
			return true;
		}
	} else {
		return true;
	}
}