<?php
// These are just some global variables that are set in order for me to test the scripts from the
// command line rather than in Alfred.
if ( ! isset( $_SERVER['alfred_workflow_bundleid'] ) ) {
	$_SERVER['alfred_workflow_bundleid'] = 'com.push.workflow.update.packal';
	$_SERVER['alfred_workflow_data'] = '/Users/Sven/Library/Application Support/Alfred 2/Workflow Data/com.push.workflow.update.packal';
	$_SERVER['alfred_workflow_cache'] = '/Users/Sven/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/com.push.workflow.update.packal';
}
