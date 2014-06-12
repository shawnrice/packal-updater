<?php

require_once( 'libraries/workflows.php' );
require_once( 'functions.php' );

$HOME   = exec( 'echo $HOME' );
$data   = "$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle";
$config = simplexml_load_file( "$data/config/config.xml" );

$option = substr( $argv[1], 0, strpos( $argv[1], ':' ) );
$value = trim( str_replace( "\\", '', substr( $argv[1], strpos( $argv[1], ':' ) + 1 ) ) );

$w = new Workflows;

function setOption() {
	global $data, $config, $option, $value, $w;
	
	if ( empty( $value ) ) {
		if ( isset( $config->$option ) && ( ! empty( $config->$option ) ) ) {
			if ( $option == 'backups' ) {
				$value = (int) $config->$option;	
			} else {
				$value = $config->$option;
			}
		}
	}
	$options = array(
	    'authorName' => 'Please enter the name you use when you write workflows',
	    'packalAccount' => 'Do you have an account on Packal? Please answer yes or no.',
	    'username' => 'Please enter your Packal username',
	    'workflowReporting' => 'Would you like to send anonymous data about your installed workflows to Packal.org? Please enter yes or no.',
	    'backups' => 'Please enter the number of backups you would like to keep'
	);

	$bool = array( 'packalAccount', 'workflowReporting' );

	if ( in_array( $option, $bool ) ) {
		if ( $option == 'workflowReporting' ) {
			if ( $config->workflowReporting == 1 ) {
				$w->result( 'option-set-workflowReporting-1', 'option-set-workflowReporting-1', 'Send anonymous data to Packal.org.', "Currently selected", 'assets/icons/svn-commit.png', 'yes', '' );
				$w->result( 'option-set-workflowReporting-0', 'option-set-workflowReporting-0', 'Do not send anonymous data to Packal.org.', "", '', 'yes', '' );
			} else {
				$w->result( 'option-set-workflowReporting-1', 'option-set-workflowReporting-1', 'Send anonymous data to Packal.org.', "", 'assets/icons/svn-commit.png', 'yes', '' );
				$w->result( 'option-set-workflowReporting-0', 'option-set-workflowReporting-0', 'Do not send anonymous data to Packal.org.', "Currently selected", '', 'yes', '' );
			}
		} else if ( $option == 'packalAccount' ) {
			$w->result( 'option-set-packalAccount-1', 'option-set-packalAccount-1', 'I do have an account on Packal.org', "", '', 'yes', '' );
			$w->result( 'option-set-packalAccount-0', 'option-set-packalAccount-0', 'I do not have an account on Packal.org', "", '', 'yes', '' );
		}
	} else {
		if ( empty( $value ) ) 
			$w->result( "option-set-$option-$value", "option-set-$option-$value", $options[ $option ], "$value", '', 'no', '' );
		else if ( $option == 'backups' ) {
			if ( ( $value > 9 ) || ( $value < 0 ) ) {
				$w->result( "option-set-$option-$value", "option-set-$option-$value", "Error: The number of backups must be positive and less than 10", "", '', 'no', '' );
			} else if ( ! is_numeric( $value ) )
				$w->result( "option-set-$option-$value", "option-set-$option-$value", "Error: The number of backups must be an integer", '', '', 'no', '' );
			else {
				$value = (int) $value;
				$w->result( "option-set-$option-$value", "option-set-$option-$value", "Set the number of backups to keep to '$value'", "$value", '', 'yes', '' );
			}
		} else {
	        if ( $option == 'username' ) {
	        	if ( $config->packalAccount == 1 ) {
				  $w->result( "", "option-set-$option-$value", "Set your " . $option . " to '$value'", "$value", '', 'yes', '' );
		          $w->result( "", "option-set-$option-", 'Clear Packal Username', '', '', 'yes', '');
		        }
    	    } else if ( $option == 'authorName' ) {
    	    	if ( empty( $config->authorName ) ) {
		    		$w->result( "", "option-set-$option-$value", "Set your author name to '$value'", "$value", '', 'yes', '' );
    	    	} else {
				  $w->result( "", "option-set-$option-$value", "Set your author name to '$value'", "$value", '', 'yes', '' );
		          $w->result( "", "option-set-$option-", 'Clear Author Name', '', '', 'yes', '');
    	    	}
    	    } else {
				$w->result( "option-set-$option-$value", "option-set-$option-$value", "Set your " . $option . " to '$value'", "$value", '', 'yes', '' );
    	    }
		}
	}
}

setOption();

echo $w->toxml();

?>