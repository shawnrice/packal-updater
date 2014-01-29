<?php
/**
 *
 * Fileblock
 * 
 */

require_once('config-functions.php');

if ( isset( $_POST ) && ( ! empty( $_POST ) ) ) {
	if ( isset( $_POST['backup'] ) ) {
		$backup = $_POST['backup'];
	}
	if ( isset( $_POST['auto_add'] ) ) {
		$auto_add = $_POST['auto_add'];
	}
	if ( isset( $_POST['report'] ) ) {
		$report = $_POST['report'];
	}
	if ( isset( $_POST['notify'] ) ) {
		$notify = $_POST['notify'];
	}
	if ( isset( $_POST['username'] ) ) {
		$username = $_POST['username'];
	}
	if ( isset( $_POST['api_key'] ) ) {
		$api_key = $_POST['api_key'];
	}
} else {
	echo "0";
	return false; // Not needed because this should only be called with ajax
}

// Load the config to see what's changed.
$options = loadConfig();
if ( $options ) {
	if ( ! ( isset( $backup ) ) ) {
		// The backup variable wasn't in the callback, so we'll just use the saved value
		$backup = $options->backup;
	}
	if ( ! ( isset( $auto_add ) ) ) {
		// The backup variable wasn't in the callback, so we'll just use the saved value
		$auto_add = $options->auto_add;
	}
	if ( ! ( isset( $report ) ) ) {
		// The backup variable wasn't in the callback, so we'll just use the saved value
		$report = $options->report;
	}
	if ( ! ( isset( $notify ) ) ) {
		// The backup variable wasn't in the callback, so we'll just use the saved value
		$notify = $options->notify;
	}
	if ( ! ( isset( $username ) ) ) {
		// The backup variable wasn't in the callback, so we'll just use the saved value
		$username = $options->username;
	}
	if ( ! ( isset( $api_key ) ) ) {
		// The backup variable wasn't in the callback, so we'll just use the saved value
		$api_key = $api_key->api_key;
	}		
	writeConfig( $backup , $auto_add , $report , $notify , $username , $api_key );
} else {
	// The config file didn't exist or was bad, so let's write a default one.
	resetToDefaults();
}
print_r($_POST);
