<?php

require_once( __DIR__ . '/autoloader.php' );
use CFPropertyList\CFPropertyList as CFPropertyList;
use Alphred\Ini as Ini;

class Action {

	public function __construct( $args ) {
		// Decode the args sent over from the script filter
		$args = json_decode( $args, true );
		// Setup common variables for use
		$this->alphred  = new Alphred;
		$this->packal   = new Packal( ENVIRONMENT );
		$this->workflow = new Workflows( ENVIRONMENT );
		$this->theme    = new Themes( ENVIRONMENT );

		$this->do_action( $args['action'], ( isset( $args['resource'] ) ? $args['resource'] : false ) );
	}

	public function do_action( $action, $resource ) {
		switch ( $action ) :
			case 'install':
				echo "IN INSTALL";
				break;
			default:
				echo "NO ACTION FOUND";
				break;
		endswitch;
	}

}

$action = new Action( $argv[1] );
