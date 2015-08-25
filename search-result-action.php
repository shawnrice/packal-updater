<?php

require_once( __DIR__ . '/includes.php' );

class Action {

	public function __construct( $args ) {
		$args = json_decode( $args, true );
		$this->alphred = new Alphred;
		print_r( $args );
		$this->action   = $args['action'];
		$this->target   = isset( $args['target'] ) ? $args['target'] : false;
		$this->workflow = isset( $args['workflow'] ) ? $args['workflow'] : false;
		$this->base_url = PACKAL_BASE_API_URL;
		$this->path     = isset( $args['path'] ) ? $args['path'] : false;
		$this->messages = [
			'status' => 'pass',
			'action' => ( $this->target ) ? $this->action . ' ' . $this->target : $this->action
		];
		$this->resource = isset( $args['resource'] ) ? $args['resource'] : false;
	}

	public function do_action() {
		switch ( $this->action ) :
			case 'clear-caches':
				$this->clear_caches();
				break;
			case 'open':
				$this->open( $this->target );
				break;
			case 'download':
				$this->download( $this->target );
				break;
			case 'install':
				$this->install( $this->target );
				break;
			case 'submit':
				// @todo finish writing this
				$this->submit( 'theme', false );
				break;
			case 'generate_ini':
				$this->generate_ini( $this->path );
				break;
		endswitch;
		if ( ! isset( $this->messages[ 'messages' ] ) ) {
			$this->messages['messages'] = [ 'Success!' ];
		}
		// Output the messages for the notifier.
		return json_encode( $this->messages );
	}

	private function generate_ini( $path ) {
		// Shawn, seriously? Like, fix this shit.
		exec( "php '" . __DIR__ . "/generate-workflow-ini.php' '{$path}'" );
	}

	private function get_filename( $url ) {
		return $this->valid( $url ) ? substr( $url, strrpos( $url, '/' ) + 1 ) : false;
	}

	private function valid_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

	private function valid( $location ) {
		if ( file_exists( $location ) ) {
			return true;
		}
		if ( $this->valid_url( $location ) ) {
			return true;
		}
		return false;
	}

	private function verify_file( $file, $md5 ) {
		return md5_file( $file ) == $md5;
	}

	///// Actions

	private function submit( $type, $resource ) {
		if ( 'theme' == $type ) {
			echo "IN SUBMIT";
			$this->submit_theme( $resource );
		}
	}

	private function submit_theme( $theme ) {
		$this->submit_build_theme_info( ['name' => 'Glass' ] );
	}

	private function submit_workflow( $workflow ) {

	}

	private function submit_build_theme_info( $theme ) {
		$file = $this->slugify( $theme['name'] );
		$file = "{$_SERVER['alfred_workflow_data']}/data/themes/submit-{$file}.json";
		if ( file_exists( $file ) ) {
			$data = json_decode( file_get_contents( $file ), true );
		} else {
			$data = ['theme_name' => $theme['name'], 'theme_description' => '', 'theme_tags' => [] ];
		}
		$metadata = $this->create_build_theme_info_dialog( $data );
		if ( '0' == $metadata['cb'] ) {
			unset( $metadata['cb'] );
			$this->alphred->console( "Saving theme information for `{$theme['name']}`.", 1 );
			file_put_contents( $file, json_encode( $metadata, JSON_PRETTY_PRINT ) );
		} else {
			$this->alphred->console( 'User canceled saving theme information.', 1 );
			// Since it was canceled, let's just exit.
			// $alphred->notify('Should we put some notification here.')
			exit(1);
		}


	}

	private function create_build_theme_info_dialog( $data ) {
		$path = __DIR__ . '/Pashua.app/Contents/MacOS/Pashua';
		$conf = file_get_contents( __DIR__ . '/Resources/pashau-theme-config.ini' );
		$data['theme_tags'] = implode( '[return]', $data['theme_tags'] );
		foreach ( $data as $key => $val ) :
			$conf = str_replace( '%%' . $key . '%%', $val, $conf );
		endforeach;
		$config = tempnam( '/tmp', 'Pashua_' );
		if ( false === $fp = @fopen( $config, 'w' ) ) {
	    throw new \RuntimeException( "Error trying to open {$config}" );
		}
		fwrite( $fp, $conf );
		fclose( $fp );

		// Call pashua binary with config file as argument and read result
		$result = shell_exec( escapeshellarg( $path ) . ' ' . escapeshellarg( $config ) );
		@unlink( $config );
	  // Parse result
		$parsed = array();
		foreach ( explode("\n", $result ) as $line ) {
		    preg_match( '/^(\w+)=(.*)$/', $line, $matches );
		    if ( empty( $matches ) or empty( $matches[1] ) ) {
		        continue;
		    }
		    $parsed[ $matches[1] ] = $matches[2];
		}
		$parsed['theme_tags'] = explode( '[return]', $parsed['theme_tags'] );
		if ( empty( $parsed['theme_tags'][0] ) ) {
			$parsed['theme_tags'] = [];
		}
		$parsed['theme_description'] = str_replace( '[return]', "\n", $parsed['theme_description'] );
 		return $parsed;
	}

	/**
	 * Makes a nice little slug, especially for use with files
	 *
	 * @param  [type] $slug [description]
	 * @return [type]       [description]
	 */
	private function slugify( $slug ) {
		$slug = strtolower( $slug );
		$slug = preg_replace( '/[^\w]{1,}/', '-', $slug );
		$slug = preg_replace( '/[-]{2,}/', '-', $slug );
		if ( '-' == substr( $slug, -1 ) ) {
			$slug = substr( $slug, 0, -1 );
		}
		if ( '-' == substr( $slug, 0, 1 ) ) {
			$slug = substr( $slug, 1 );
		}
		return $slug;
	}

	private function clear_caches() {
		$request = new \Alphred\Request( $this->base_url );
		$request->clear_cache( 'localhost' );
	}

	private function download( $url, $directory = '' ) {
		$directory = empty( $directory ) ? $_SERVER['HOME'] . '/Downloads/' : $directory;
		$file = $this->get_filename( $url );
		if ( file_put_contents( "{$directory}{$file}", file_get_contents( $url ) ) ) {
			return "{$directory}{$file}";
		}
		$this->log_failure( "Could not download file ({$url}) to ({$directory})." );
		return false;
	}

	private function open( $location ) {
		exec( "open {$location}" );
	}

	private function install( $url ) {
		if ( ! $this->valid( $url ) ) {
			$this->log_failure( "Location {$url} is not a valid location." );
			return false;
		}
		// I should replace this method with one that installs silently... in the
		// background.
		if ( $file = $this->download( $url ) ) {
			if ( $this->verify_file( $file, $this->workflow['md5'] ) ) {
		    $front = Alphred\Applescript::get_front()['app'];
		    // Activate Alfred Preferences to make sure that we don't suffer a delay
		    $delay = Alphred\Applescript::activate('Alfred Preferences');
		    // Open the .alfredworkflow file
		    $delay = $this->open( $file );
		    // Delay until the window animation finishes
		    // usleep(1500000);
		    sleep(1);
		    // Press Enter for the User
		    $script = "tell applications \"System Events\"\nkey code 36\nend tell";
				exec( "osascript -e '{$script}'" );
				// Reactivate the front window
				Alphred\Applescript::activate( $front );
				// Sleep for one second
				sleep(1);
				// Delete the file from the Downloads directory
		    unlink( $file );
			} else {
				$this->log_failure( "Could not verify file ({$file}). File hashes do not match." );
			}
		} else {
			$this->log_failure( "Could not download file ({$url})." );
		}
	}

	private function log_failure( $message ) {
		$this->messages['status'] 		= 'fail';
		$this->messages['messages'][] = $message;
	}
}

$action = new Action( $argv[1] );
echo $action->do_action();