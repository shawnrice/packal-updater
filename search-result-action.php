<?php

require_once( __DIR__ . '/includes.php' );
require_once( __DIR__ . '/generate-workflow-ini.php' );
require_once( __DIR__ . '/Libraries/BuildWorkflow.php' );
require_once( __DIR__ . '/submit.php' );
use Alphred\Ini as Ini;

class Action {

	public function __construct( $args ) {
		$args = json_decode( $args, true );
		$this->alphred = new Alphred;
		// print_r( $args );
		$this->action   = $args['action'];
		$this->base_url = PACKAL_BASE_API_URL;

		foreach( [ 'target', 'workflow', 'path', 'theme', 'value' ] as $key ) :
			$this->$key = isset( $args[ $key ] ) ? $args[ $key ] : false;
		endforeach;

		/* @todo I NEED TO FIX THESE MESSAGES */
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
				if ( false !== strpos( $this->target, 'alfred://' ) ) {
					$this->post_download( 'theme', [ 'theme' => $this->theme['id'], 'name' => $this->theme['name'] ] );
				}
				break;
			case 'download':
				if ( $this->workflow ) {
					$this->post_download( 'workflow',
					                     [
					                     	'workflow' => $this->workflow['id'],
					                     	'revision' => $this->workflow['revision_id'],

					                     ]
					);
				}
				$this->download( $this->target );
				break;
			case 'install':
				if ( $this->workflow ) {
					$this->post_download( 'workflow',
					                     [
					                     	'workflow' => $this->workflow['id'],
					                     	'revision' => $this->workflow['revision_id'],

					                     ]
					);
				}
				$this->install( $this->target );
				break;
			case 'submit_theme':
				// @todo finish writing this
				$this->submit( 'theme', $this->theme );
				break;
			case 'submit_workflow':
				// @todo finish writing this
				$this->submit( 'workflow', $this->path );
				break;
			case 'generate_ini':
				$this->generate_ini( $this->path );
				break;
			case 'configure':
				$this->configure( $this->target, $this->value );
				break;
			case 'report':
				$this->report();
				break;
		endswitch;
		if ( ! isset( $this->messages[ 'messages' ] ) ) {
			$this->messages['messages'] = [ 'Success!' ];
		}
		// Output the messages for the notifier.
		return json_encode( $this->messages );
	}

	private function report() {
		$path = __DIR__ . '/Pashua.app/Contents/MacOS/Pashua';
		$conf = file_get_contents( __DIR__ . '/Resources/pashau-report-config.ini' );
		$values = [ 'workflow_name' => $this->workflow['name'],
								'version' => $this->workflow['version'],
								'revision' => $this->workflow['revision_id'],
								'workflow' => $this->workflow['id'],
		];
		foreach ( $values as $key => $val ) :
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
		foreach ( explode( "\n", $result ) as $line ) {
	    preg_match( '/^(\w+)=(.*)$/', $line, $matches );
	    if ( empty( $matches ) or empty( $matches[1] ) ) {
	        continue;
	    }
	    $parsed[ $matches[1] ] = $matches[2];
		}
		if ( 1 == $parsed['cb'] ) {
			return false;
		}
		print_r( $parsed );
		$params = [
			'workflow_revision_id' => $parsed['revision'],
			'report_type' => $parsed['type'],
			'message' => $parsed['message']
		];
		return submit_report( $params );
	}

	private function configure( $key, $value = false ) {
		if ( 'password' == $key ) {
			$password = $this->alphred->get_password_dialog(
				'Please enter your Packal.org password. If you do not have one, then please make an account on Packal.org and then set this.',
				'Packal Workflow'
			);
			if ( 'canceled' !== $password ) {
				$this->alphred->save_password( 'packal.org', $password );
				$this->alphred->console( 'Set Packal.org password in the keychain', 1 );
				$this->messages['messages'] = [ 'Successfully set Packal.org password.' ];
			} else {
				$this->alphred->console( 'Canceled `set password` operation.', 1 );
			}
			return;
		}
		$this->alphred->config_set( $key, $value );
	}

	private function generate_ini( $path ) {
		generate_ini( $path );
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
			$this->submit_theme( $resource );
		} else if ( 'workflow' == $type ) {
			$this->submit_workflow( $resource );
		}
	}

	/**
	 * [post_download description]
	 *
	 * Sends a POST request to track downloads / installs on workflows and themes.
	 *
	 * @param  [type] $type       [description]
	 * @param  [type] $properties [description]
	 * @return [type]             [description]
	 */
	private function post_download( $type, $properties ) {
		$id = ( 'theme' == $type ) ? $properties['theme'] : $properties['workflow'];
		$json = [
				'id' => self::uuid(),
				'visit_id' => self::uuid(),
				'user_id' => null,
				'name' => "{$type}-{$id}",
				'properties' => $properties,
				'time' =>	date_format( date_create('now', new DateTimeZone( 'Etc/UTC' ) ), 'Y-m-d H:i:s' ),
				'theme_id' => ($type == 'theme') ? $id : null,
				'workflow_revision_id' => ($type == 'workflow') ? (int)$properties['revision'] : null,
				'workflow_id' => ($type == 'workflow') ? $id : null,
		];
		// I wanted to use $alphred->post, but it doesn't encode the query fields correctly.
		$c = curl_init('http://localhost:3000/ahoy/events');
		curl_setopt( $c, CURLOPT_POST, true );
		curl_setopt( $c, CURLOPT_POSTFIELDS, json_encode( $json ) );
		curl_setopt( $c, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_exec( $c );
		curl_close( $c );
	}

	private function uuid() {
		return self::random(8) . '-' . self::random(4) . '-' . self::random(4) . '-' . self::random(4). '-' . self::random(12);
	}

	private function random( $length ) {
		$string = 'abcdef0123456789';
		$value = '';
		for ( $i = 0; $i < $length; $i++ ) :
			$value .= substr( $string, rand( 0, 15 ), 1 );
		endfor;
		return $value;
	}

	private function submit_theme( $theme ) {
		$metadata = $this->submit_build_theme_info( $theme );
		$uri = $theme['uri'];
		// print_r( $metadata );
		submit_theme([
		  'uri' => $theme['uri'],
		  'description' => $metadata['theme_description'],
		  'tags' => $metadata['theme_tags'],
		  'name' => $theme['name'],
		]);

	}

	private function submit_workflow( $workflow_path ) {
		if ( ! $username = $this->alphred->config_read( 'username' ) ) {
			$this->alphred->console( 'Could not read username in the config file.', 4 );
			$dialog = new \Alphred\Dialog([
				'title' => 'Packal Error: No Username Set',
				'text' => 'Please set your username with the `config` option to submit a workflow',
				'button' => 'Okay',
				'icon' => 'stop',
			]);
			$dialog->execute();
			return;
		}
		if ( ! $password = $this->alphred->get_password( 'packal.org' ) ) {
			$dialog = new \Alphred\Dialog([
				'title' => 'Packal Error: No Password Set',
				'text' => 'Please set your password with the `config` option to submit a workflow',
				'icon' => 'stop',
			]);
			$dialog->execute();
			return;
		}
		$ini = Ini::read_ini( "{$workflow_path}/workflow.ini" );
		$version = $ini['workflow']['version'];
		if ( file_exists( "{$workflow_path}/screenshots" ) ) {
			$workflow = new BuildWorkflow( $workflow_path, "{$workflow_path}/screenshots" );
		} else {
			$workflow = new BuildWorkflow( $workflow_path );
		}
		// Let's actually do some submitting here
		$json = json_encode( [
      'file' => $workflow->archive_name(),
      'username' => $username,
      'password' => $password,
      'version' => $version,
		]);
		$output = submit_workflow([ 'file' => $workflow->archive_name(), 'version' => $version ]);
		$this->alphred->console( print_r( $output, true ), 4 );
	}

	private function submit_build_theme_info( $theme ) {
		$file = "{$_SERVER['alfred_workflow_data']}/data/themes/submit-" . $this->slugify( $theme['name'] ) . "json";
		if ( file_exists( $file ) ) {
			$data = json_decode( file_get_contents( $file ), true );
		} else {
			$data = [ 'theme_description' => '', 'theme_tags' => [] ];
		}
		$data['theme_name'] = $theme['name'];
		$metadata = $this->create_build_theme_info_dialog( $data );
		if ( $metadata ) {
			unset( $metadata['cb'] );
			$this->alphred->console( "Saving theme information for `{$theme['name']}`.", 1 );
			file_put_contents( $file, json_encode( $metadata, JSON_PRETTY_PRINT ) );
		} else {
			$this->alphred->console( 'User canceled saving theme information.', 1 );
			// Since it was canceled, let's just exit.
			// $alphred->notify('Should we put some notification here.')
			exit(1);
		}
		return $metadata;
	}

	private function create_build_theme_info_dialog( $data ) {
		$path = __DIR__ . '/Pashua.app/Contents/MacOS/Pashua';
		$conf = file_get_contents( __DIR__ . '/Resources/pashau-theme-config.ini' );
		$data['theme_tags'] = implode( '[return]', $data['theme_tags'] );
		foreach ( $data as $key => $val ) :
			$conf = str_replace( '%%' . $key . '%%', $val, $conf );
		endforeach;
		$values = [ 'theme_name' => $data['theme_name'] ];
		foreach ( $values as $key => $val ) :
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
		if ( 1 == $parsed['cb'] ) {
			return false;
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
