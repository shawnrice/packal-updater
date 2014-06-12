<?php

/**
 *
 * This is the file that receives the input from the script filter and then starts everything.
 * 
 */

require_once( 'functions.php' );
require_once( 'alfred.bundler.php' );

// firstRun();

if ( ( ! isset( $argv[1] ) || empty( $argv[1] ) ) ) {
	echo "The controller needs at least one argument.";
	die();
}

$q = $argv[1];

$bundle = 'com.packal';
$HOME = exec( 'echo $HOME' );
$data = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";

// Implement bundler.
// Mod to handle more args.
$osx = exec( "sw_vers | grep 'ProductVersion:' | grep -o '10\.[0-9]*'" );

if ( $q == 'open-gui' ) {
	$dir = escapeshellcmd( exec( 'pwd' ) );


	// Start the webserver
	if ( ( strpos( $osx, '10.9' ) !== FALSE ) || ( strpos( $osx, '10.10' ) !== FALSE ) ) {
		// Since we're using Mavericks, we can just use the native php 5.4 binary.
		exec( "nohup php -S localhost:7893 -t gui/ > /dev/null 2>&1 &" );
	} else {
		// Not using Mavericks, so php 5.3 is installed.
		// For now, fuck it. I've tried to come up with too many workarounds.
		// No GUI for you.
		die();
	}
	
	// Start the webserver kill script
	exec( "nohup '$dir/check-and-kill-webserver.sh'  > /dev/null 2>&1 &" );
	exec( "nohup php '$dir/gui/webserver-keep-alive-update.php'  > /dev/null 2>&1 &" );

	// // Wait a second so that we can make sure that the webserver has started before we open the viewer.
	sleep(2);

	$viewer = __load( 'viewer', 'default', 'utility' );
	// Okay, so if there is an old version of the bundler, then we'll do the correction for this.
	if ( strpos( $viewer, '--args' ) )
		$viewer = substr( $viewer, 0, strlen( $viewer ) - 6 );

	// Open the gui in the viewer
	exec( "nohup open $viewer --args http://localhost:7893  > /dev/null 2>&1 &" );
	die();
}


if ( strpos( $q, 'update-' ) !== FALSE ) {
	$workflows = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );
	$wf = array_keys( $workflows );
	if ( str_replace( 'update-', '', $q ) == 'all' ) {
		$result = exec( 'php cli/packal.php doUpdateAll ');
	} else {
		if ( in_array( str_replace( 'update-', '', $q ), $wf ) ) {
			$cmd = 'php cli/packal.php doUpdate ' . str_replace( 'update-', '', $q );
			$result = exec( $cmd );
			if ( $result == 'TRUE' ) {
				$plist = $workflows[ str_replace( 'update-', '', $q ) ] . '/info.plist';
				$name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$plist' 2> /dev/null" );
				// echo $name . " has been successfully updated.";
			} else {
				$plist = $workflows[ str_replace( 'update-', '', $q ) ] . '/info.plist';
				$name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$plist' 2> /dev/null" );
				echo "Error updating " . $name; 
			}
		}
	}
}

// We haven't possibly needed these yet, so we'll load them here.
$tn = __load( 'terminal-notifier' , 'default' , 'utility' );
$endpoints = json_decode( file_get_contents( "$data/endpoints/endpoints.json" ), TRUE );


if ( strpos( $q, 'option-set-' ) !== FALSE ) {
	$set = str_replace( 'option-set-', '', $q );
	$set = explode( '-', $set );
	$set[1] = (string) $set[1];

	if ( count( $set ) > 2 ) {
		echo "Too many hyphens.";
		die();
	}

	if ( empty( $set[1] ) )
	   $set[1] = 'null';

	// Just in case something wasn't quoted correctly.
	$set[1] = str_replace( '\ ', ' ', $set[1] );

	if ( ( $set[0] == 'username' ) && ( $set[1] == 'null' ) ) {
		$cmd = ( "php cli/packal.php setOption packalAccount 0" );
		exec( $cmd );
		$cmd = ( "php cli/packal.php setOption username ''" );
		exec( $cmd );
		exec( "$tn -title 'Packal Updater' -message 'You no longer have a Packal username set.' -group 'packal-updater-settings'" );

		die();
	}
	if ( ( $set[0] == 'packalAccount') && ( $set[1] == '1' ) ) {
		$cmd = ( "php cli/packal.php setOption packalAccount 1" );
		exec( $cmd );
		$script = 'tell application "Alfred 2" to run trigger "set-option" in workflow "com.packal" with argument "username:"';
		exec( "osascript -e '$script'" );
		die();
	}

	if ( ( $set[0] == 'packalAccount') && ( $set[1] == '0' ) ) {
		$cmd = ( "php cli/packal.php setOption packalAccount 0" );
		exec( $cmd );
		$cmd = ( "php cli/packal.php setOption username ''" );
		exec( $cmd );
		exec( "$tn -title 'Packal Updater' -message 'You no longer have a Packal username set.' -group 'packal-updater-settings'" );
		die();
	}
	if ( ( $set[0] == 'authorName' ) && ( $set[1] == 'null' ) ) {
		$cmd = ( "php cli/packal.php setOption authorName ''" );
		exec( $cmd );
		exec( "$tn -title 'Packal Updater' -message 'You no longer have a author name set.' -group 'packal-updater-settings'" );

		die();
	}

	if ( $set[0] == 'workflowReporting' ) {
		
		// Possible Data Correction
		if ( $set[1] == 'null' )
			$set[1] = 0;

		// Set the value
		$cmd = "php cli/packal.php setOption workflowReporting " . $set[1];
		exec( $cmd );

		// Send the notification
		if ( $set[1] == 1 )
			exec( "$tn -title 'Packal Updater' -message 'You will now send anonymous usage data to Packal.org.' -group 'packal-updater-settings'" );
		else
			exec( "$tn -title 'Packal Updater' -message 'You will __not__ send anonymous usage data to Packal.org.' -group 'packal-updater-settings'" );

		// Die. Die. Die.
		die();
	}

	$cmd = ( "php cli/packal.php setOption " . $set[0] . " '" . $set[1] . "'" );
	exec( $cmd );

	switch ( $set[0] ) :
		case 'backups' :
			$message = "The updater will now keep " . $set[1] . " backups of workflows.";
			break;
		case 'authorName' :
			$message = "Your author name is now set to " . $set[1] . ".";
			break;
		case 'username' :
			$message = "Your Packal.org username is now set to " . $set[1] . ".";
			break;
	endswitch;

	if ( isset( $message ) )
		exec( "$tn -title 'Packal Updater' -message '$message' -group 'packal-updater-settings'" );

	// Option to set a custom icon. It doesn't seem to be working right now, so commented out.
	// if ( $osx == '10.9' || $osx == '10.10' )
		// $tnicon = "-appIcon '" . __DIR__ . "assets/icons/package.png'";




	// @TODO: Add in Terminal Notifier notification.
	die();

}

if ( strpos( $q, 'set-' ) !== FALSE ) {
	$option = str_replace( 'set-', '', $q );
	$script = 'tell application "Alfred 2" to run trigger "set-option" in workflow "com.packal" with argument "' . $option . ': "';
	exec( "osascript -e '$script'" );
	die();
}

if ( strpos( $q, 'blacklist-' ) !== FALSE ) {
	$workflow = str_replace( 'blacklist-', '', $q );
	$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );
	if ( ! in_array( $workflow, $blacklist ) ) {
		$blacklist[] = $workflow;
		file_put_contents( "$data/config/blacklist.json", json_encode( $blacklist ) );
	}
	$plist = $endpoints[ $workflow ] . '/info.plist';
	$name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$plist' 2> /dev/null" );
	exec( "$tn -title 'Packal Updater' -message '$name has been put on the blacklist.'" );
	die();
}

if ( strpos( $q, 'whitelist-' ) !== FALSE ) {
	$workflow = str_replace( 'whitelist-', '', $q );
	$blacklist = json_decode( file_get_contents( "$data/config/blacklist.json" ), TRUE );
	if ( in_array( $workflow, $blacklist ) ) {
		unset( $blacklist[ array_search( $workflow, $blacklist ) ] );
		file_put_contents( "$data/config/blacklist.json", json_encode( $blacklist ) );
	}
	$plist = $endpoints[ $workflow ] . '/info.plist';
	$name = exec( "/usr/libexec/PlistBuddy -c \"Print :name\" '$plist' 2> /dev/null" );
	exec( "$tn -title 'Packal Updater' -message '$name has been removed from the blacklist.'" );
	die();
}

if ( strpos( $q, 'install-cron-script' ) !== FALSE ) {
	$dir = escapeshellcmd( exec( 'pwd' ) );
	exec( "'$dir/install-alfred-cron-script.sh'" );
	exec( "$tn -title 'Packal Updater' -message 'Alfred Cron will now check for workflow updates.'" );
}

?>