<?php

// This is needed because, Macs don't read EOLs well.
if ( ! ini_get( 'auto_detect_line_endings' ) ) {
	ini_set( 'auto_detect_line_endings', true );
}
// Set date/time to avoid warnings/errors.
if ( ! ini_get( 'date.timezone' ) ) {
	ini_set( 'date.timezone', exec( 'tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}' ) );
}


/**
 * ANSI Colors
 *
 * Bold Background
 * \e[1;30m \e[40m # black
 * \e[1;31m \e[41m # red
 * \e[1;32m \e[42m # green
 * \e[1;33m \e[43m # yellow
 * \e[1;34m \e[44m # blue
 * \e[1;35m \e[45m # purple
 * \e[1;36m \e[46m # cyan
 * \e[1;37m \e[47m # white
 *
 */

/**
 * What is the functionality that we need this thing to do?
 *
 * -- Search for a workflow by
 * 	-- name
 * 	-- bundle
 * 	-- short description
 * -- Search for themes by name
 * -- Install a workflow
 * -- Upgrade a Workflow
 * -- Upgrade all Workflows
 * -- Install a Theme
 *
 */

class CLI {
	const VERSION = '0.0.1';
	const CLI_NAME = 'packal-cli';

	const GREEN =  "\033[32m";
	const RED = "\033[31m";
	const NORMAL = "\033[0m";

	public function __construct( $options = [] ) {
		self::autoloader();

		self::make_directories();
		$this->alphred  = new Alphred;
		$this->packal   = new Packal( ENVIRONMENT );
		$this->workflow = new Workflows( ENVIRONMENT );
		$this->theme    = new Themes( ENVIRONMENT );
		$this->run();
	}

	private static function print_connectivity_error() {
		$error = "\n!!! Error: Cannot connect to Packal servers. If cached data is present, you can install themes and search anything but nothing else.\n\n";
		print self::color( $error, 'red' );
	}

	function install_theme( $slug ) {
		$result = $this->theme->install( $slug );
		if ( false == $result[0] ) {
			print self::highlight( 'Error', "Error: there is no theme with the slug `{$result[1]['slug']}`.\n" );
			exit(1);
		} else {
			print self::highlight( $result[1], "Installing {$result[1]}.\n" );
		}
		exit(0);
	}

	function install_workflow( $bundle ) {
		$workflow = $this->workflow->find_workflow_by_bundle_from_packal( $bundle );
		if ( is_string( $workflow ) ) {
			print self::color( "!!! Error: {$workflow}\n", 'red' );
			exit(1);
		}
		$result = $this->workflow->install( $workflow );
		if ( is_string( $result ) ) {
			$error = "!!! Error: " . $result . '.';
			print self::highlight( $error, $error ) . "\n";
			exit(1);
		}
		print self::color( "Successfully installed `{$workflow['name']}`.\n", 'green' );
		exit(0);
	}

	/**
	 * [upgrade_workflows description]
	 *
	 * @todo Should I cache the results of this to a file?
	 *
	 * @param  array  $workflows [description]
	 * @return [type]            [description]
	 */
	function upgrade_workflows( $workflows = [] ) {
		$this->workflow->find_upgrades();
		if ( count( $this->workflow->upgrades ) > 0 ) {
			$output = self::RED . "There are " . count( $this->workflow->upgrades ) . " upgrade(s)." . self::NORMAL . " available\n";
			foreach( $this->workflow->upgrades as $upgrade ) {
				$output .= "{$upgrade['old']['name']} -- {$upgrade['old']['bundle']} -- (" . self::RED . "{$upgrade['old']['version']}" . self::NORMAL . " -> " . self::GREEN . "{$upgrade['new']['version']}" . self::NORMAL . "), ";
			}
			$output = substr( $output, 0, -2 ) . "\n";
			print $output;
			$this->confirm( 'Continue with upgrades? (Y/n): ', 'Canceled upgrades.' );
			foreach( $this->workflow->upgrades as $upgrade ) :
			// 	print_r( $upgrade );
			// exit();
				if ( true !== $output = $this->workflow->upgrade( $upgrade ) ) {
					print self::highlight( $output, $output );
				} else {
					print "Upgrade successful.";
				}
				print "\n";
			endforeach;
		} else {
			print "All workflows are up to date.\n";
			exit(0);
		}
	}


	function print_search( $search, $key, $type, $identifer ) {
		$red    = "\033[31m";
		$green  = "\033[32m";
		$normal = "\033[0m";

		$items = $this->packal->search( $search, $key, $type, $identifer );

		// Construct initial header
		$output = self::print_divider();
		$output .= "Searching {$red}" . str_replace( '/api/v1/', '', BASE_API_URL ) . "{$normal} for {$type}s.\n";
		$output .= "Found {$red}" . count( $items ) . "{$normal} {$type}(s).\n";

		// If we find nothing, then return early
		if ( 0 === count( $items ) ) {
			$output .= self::print_divider();
			return $output;
		}

		// Add in install instructions
		$output .= "To install use `packal.phar --install-{$type} <{$red}{$identifer}{$normal}>`\n";
		// Add a divider
		$output .= self::print_divider();

		$out = [];
		$id_max = 0;
		$name_max = 0;

		foreach ( $items as $item ) :
			$id = ( 'theme' == $type ) ? $this->theme->find_slug( $item['url'] ) : $item[ $identifer ];
			if ( strlen( $id ) > $id_max ) { $id_max = strlen( $id ); }
			if ( strlen( $item['name'] ) > $name_max ) { $name_max = strlen( $item['name'] ); }
			$out[] = [ $identifer => $id, 'name' => $item['name'], 'desc' => self::scrub( $item['description'] ) ];
		endforeach;

		foreach( $out as $key => $val ) :
			$out[ $key ][ $identifer ] = self::pad_string( $val[ $identifer ], $id_max );
			$out[ $key ]['name'] = self::pad_string( $val['name'], $name_max );
			$out[ $key ] = self::trim( self::highlight( $search, implode( "\t", $out[ $key ] ) ) );
		endforeach;

		// Add column labels
		$output .= self::pad_string( ucfirst( $identifer ), $id_max ) . "\t" . self::pad_string( 'Name', $name_max ) . "\t" . "Description\n";
		// Add a divider
		$output .= self::print_divider();
		return $output . implode( "\n", $out ) . "\n";
	}

	/*********************************************************************
	 * Setup Methods
	 *********************************************************************/

	/**
	 * Autoloads the necessary files
	 */
	private function autoloader() {

		if ( self::is_phar() ) {
			$files = [
				'config.php',
				'Alphred/Main.php',
				'BuildThemeMap.php',
				'BuildWorkflow.php',
				'BuildWorkflowMap.php',
				'Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php',
				'PlistMigration.php',
				'SemVer.php',
				'Submit.php',
				'functions.php',
				'FileSystem.php',
				'Packal.php',
				'Themes.php',
				'Workflows.php',
			];
		} else {
			$files = [
				'config.php',
				'Libraries/Alphred.phar',
				'Libraries/BuildThemeMap.php',
				'Libraries/BuildWorkflow.php',
				'Libraries/BuildWorkflowMap.php',
				'Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php',
				'Libraries/PlistMigration.php',
				'Libraries/SemVer.php',
				'Libraries/Submit.php',
				'Libraries/functions.php',
				'Libraries/FileSystem.php',
				'Libraries/Packal.php',
				'Libraries/Themes.php',
				'Libraries/Workflows.php',

			];
		}

		foreach( $files as $file ) {
			require_once( __DIR__ . '/' . $file );
		}

			// Set some variables here so that Alphred plays nicely, etc....
		$_SERVER['alfred_workflow_data']     = DATA;
		$_SERVER['alfred_workflow_cache']    = CACHE;
		$_SERVER['alfred_workflow_bundleid'] = BUNDLE;
	}

	private function make_directories() {
		$directories = [
			DATA,
			CACHE,
			DATA . ENVIRONMENT,
			DATA . ENVIRONMENT . '/data',
		];
		foreach ( $directories as $dir ) :
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir, 0775, true );
			}
		endforeach;
	}



	/*********************************************************************
	 * "Meta" Methods
	 *********************************************************************/

	private function is_phar() {
		if ( 'Packal.phar' == end( explode( '/', __DIR__ ) ) ) {
			return true;
		}
		return false;
	}

	function parse_options() {
		$shortopts  = "";
		// $shortopts .= "s:";
		// $shortopts .= "f:";  // Required value
		// $shortopts .= "v::"; // Optional value
		// $shortopts .= "abc"; // These options do not accept values

		$longopts  = array(
				"st:",
        "search-theme:",
        "search-themes:",
        "sw:",
        "search-workflow:",
        "search-workflows:",
        "it:",
        "install-theme:",
        "iw:",
        "install-workflow:",
        "upgrade::",
        "upgrade-workflow::",
        "upgrade-workflows::",
        "submit-workflow:",
        "submit-theme:",
		    "help",
		    "version",
		    "clear-cache",
		    "cc",
		);
		$options = getopt( $shortopts, $longopts );

		$aliases = [
			'search-workflows' => [ 'search-workflow', 'sw' ],
			'search-themes' => [ 'search-theme', 'st' ],
			'install-theme' => [ 'install-themes', 'it' ],
			'install-workflow' => [ 'install-workflows', 'iw' ],
			'upgrade' => [ 'upgrade-workflow', 'upgrade-workflows' ],
		];

		foreach ( $aliases as $main => $list ) :
			self::aliases( $main, $list, $options );
		endforeach;
		return $options;
	}

	function aliases( $main, $aliases, &$options ) {
		foreach ( $aliases as $alias ) :
			if ( in_array( $alias, array_keys( $options ) ) ) {
				$options[ $main ] = $options[ $alias ];
				unset( $options[ $alias ] );
			}
		endforeach;
	}

	function help( $options = [] ) {
		$text = file_get_contents( 'Resources/help_template.txt' );
		$replacements = [
			'VERSION' => self::VERSION,
			'CLI_NAME' => self::CLI_NAME,
			'COPYRIGHT' => "2015" . ( ( 2015 == date('Y', time() ) ) ? '.' : '-' . date('Y', time() ) . '.' ),
		];
		foreach ( $replacements as $key => $val ) {
			$text = str_replace( "%%{$key}%%", $val, $text );
		}
		return $text;
	}

	function usage() {
		print "You need to pass things to this script for it to do anything.\n";
	}

	function version() {
		print self::CLI_NAME . " version " . self::VERSION . "\n";
	}



	function run() {
		$options = $this->parse_options();

		if ( empty( $options ) ) {
			$this->usage();
			exit(1);
		}

		if ( ! $this->packal->ping() ) {
			self::print_connectivity_error();
		}

		// So, what are we doing here? Can we do this with a switch statement?
		if ( isset( $options['search-themes'] ) ) {
			print $this->print_search( $options['search-themes'], 'name', 'theme', 'slug' );
			// echo $this->search_theme( $options['st'] );
			exit(0);
		}
		if ( isset( $options['search-workflows'] ) ) {
			print $this->print_search( $options['search-workflows'], 'name', 'workflow', 'bundle' );
			exit(0);
		}
		if ( isset( $options['help'] ) ) {
			print $this->help();
			exit(0);
		}
		if ( isset( $options['version'] ) ) {
			print $this->help();
			exit(0);
		}
		if ( isset( $options['install-theme'] ) ) {
			$this->install_theme( $options['install-theme'] );
			exit(0);
		}
		if ( isset( $options['install-workflow'] ) ) {
			$this->install_workflow( $options['install-workflow'] );
			exit(0);
		}
		if ( isset( $options['upgrade'] ) ) {
			$this->upgrade_workflows( $options['upgrade'] );
			exit(0);
		}
		print "No arguments found.\n";
		print_r($options);
	}

	/*********************************************************************
	 * Input Methods
	 *********************************************************************/

	/**
	 * [input description]
	 * @param  boolean $prompt [description]
	 * @param  boolean $hidden [description]
	 * @return [type]          [description]
	 */
	private function input( $prompt = false, $hidden = false ) {
		if ( $prompt ) { print $prompt; }
		if ( $hidden ) { system( 'stty -echo' ); }
		$return = trim( fgets( STDIN ) );
		if ( $hidden ) { system( 'stty echo' ); }
		return $return;
	}

	/**
	 * [get_input description]
	 * @param  boolean $prompt [description]
	 * @return [type]          [description]
	 */
	private function get_input( $prompt = false ) {
		return self::input( $prompt );
	}

	/**
	 * [get_hidden_input description]
	 * @param  boolean $prompt [description]
	 * @return [type]          [description]
	 */
	private function get_hidden_input( $prompt = false ) {
		return self::input( $prompt, true );
	}

	/**
	 * [confirm description]
	 * @return [type] [description]
	 */
	private function confirm( $prompt = false, $canceled = false ) {
		$prompt = ( $prompt ) ? $prompt : "Continue (Y/n): ";
		$canceled = ( ( $canceled ) ? $canceled : "Canceled action." ) . "\n";
		$answer = strtolower( self::get_input( $prompt ) );
		if ( empty( $answer ) ) {
			return true;
		} else if ( in_array( $answer, [ 'y', 'ye', 'yes' ] ) ) {
			return true;
		}
		print $canceled;
		exit(1);
	}

	/*********************************************************************
	 * String Methods
	 *********************************************************************/

	/**
	 * Adds whitespace to a string to make columns match up
	 *
	 * @param  [type] $string [description]
	 * @param  [type] $max    [description]
	 * @return [type]         [description]
	 */
	private function pad_string( $string, $max ) {
		$len = strlen( $string );
		if ( $len < $max ) {
			for ( $i = 0; $i < ( $max - $len ); $i++ ) :
				$string .= ' ';
			endfor;
		}
		return $string;
	}

	/**
	 * Scrubs out undesirable characters and line breaks
	 *
	 * @param  [type] $string [description]
	 * @return [type]         [description]
	 */
	private function scrub( $string ) {
		$replacements = [
			"&#39;" => "'",
			"\n" => ' ',
			"\t" => ' ',
		];
		foreach ( $replacements as $search => $replace ) :
			$string = str_replace( $search, $replace, $string );
		endforeach;
		return $string;
	}

	/**
	 * Trims the output of a string to the width of the current terminal screen
	 *
	 * @param  [type] $string [description]
	 * @return [type]         [description]
	 */
	private function trim( $string ) {
		$cols   = exec( 'tput cols' );
		if ( strlen( $string ) < $cols ) {
			return $string;
		}
		return substr( $string, 0, $cols ) . '...';
	}

	private function print_divider() {
		$cols = exec( 'tput cols' ) - 10;
		$out = '';
		for ( $i = 0; $i < $cols; $i++ ) :
			$out .= '-';
		endfor;
		return $out . "\n";
	}

	/**
	 * Colors the search text red
	 *
	 * @param  [type] $search [description]
	 * @param  [type] $string [description]
	 * @return [type]         [description]
	 */
	private function highlight( $search, $string ) {
		$red    = "\033[31m";
		$green  = "\033[32m";
		$normal = "\033[0m";
		// Should this be str_replace instead? This highlights everything, but lowercases some weird stuff
		return str_ireplace( $search, "{$red}{$search}{$normal}", $string );
	}

	private function color( $message, $color ) {
		$colors = [
			'red'    => "\033[31m",
			'green'  => "\033[32m",
			'normal' => "\033[0m",
		];
		if ( ! array_key_exists( $color, $colors ) ) {
			return $message;
		}
		return "{$colors[$color]}{$message}{$colors['normal']}";
	}

}

$cli = new CLI();