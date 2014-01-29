<?php

/**
 *
 *	Functions for working with the configuration files.
 * 
 */

/**
 * Loads the config file and returns it
 * @return array				an xml object of the config file
 */
function loadConfig() {

	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the config file
	$config = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/config/config.xml";
	if ( file_exists( $config ) ) {
		// If the file isn't a valid one, then the place it was called will take care of 
		// the error handling
		$options = simplexml_load_file( $config );

		return $options;
	}

	// The config file doesn't exist, so write it with the defaults plus the arguments.
	return false;
}

/**
 * Writes the config file for Packal in XML.
 * @param  int $backup    		how many backups to keep
 * @param  bool $auto_add  		whether to automatically control workflows found on the manifest
 * @param  bool $report    		whether to send information to packal for statistics
 * @param  string $notify   	how to be notified per workflow ( silent , growl , native )
 * @return bool           		just returns true
 */
function writeConfig( $backup = 3 , $auto_add = 1 , $report = 1 , $notify = "native" , $username = '' , $api_key = '' ) {

	// Set the user's home directory
	$home = exec('echo $HOME');
	// The location of the config file
	$config = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/config/config.xml";

	$file  = "<?xml version='1.0' encoding='UTF-8' ?>\n";
	$file .= "<config>\n";
	$file .= "\t<backup>$backup</backup>\n";
	$file .= "\t<auto_add>$auto_add</auto_add>\n";
	$file .= "\t<report>$report</report>\n";
	$file .= "\t<notify>$notify</notify>\n";
	$file .= "\t<username>$username</username>\n";
	$file .= "\t<api_key>$api_key</api_key>\n";
	$file .= "</config>";

	// Write the XML to the config file
	file_put_contents( $config , $file );
}


/**
 * Rewrites regular configuration file with the defaults.
 * @return bool 	"true"
 */
function resetToDefaults() {
// @todo : add in error handling just to ensure nothing goes wrong.
// like the directories not working...

	$home = exec('echo $HOME');
	// The location of the config file
	$config = "$home/Library/Application Support/Alfred 2/Workflow Data/com.packal.shawn.patrick.rice/config/config.xml";

	$file  = "<?xml version='1.0' encoding='UTF-8' ?>\n";
	$file .= "<config>\n";
	$file .= "\t<backup>3</backup>\n";
	$file .= "\t<auto_add>1</auto_add>\n";
	$file .= "\t<report>1</report>\n";
	$file .= "\t<notify>native</notify>\n";
	$file .= "\t<username></username>\n";
	$file .= "\t<api_key></api_key>\n";
	$file .= "</config>";

	try {

		// Write the XML to the config file
		file_put_contents( $config , $file );
		return TRUE;

	} catch (Exception $e) {

		// We do nothing here yet, except return false.
		return FALSE;
		
	}
}