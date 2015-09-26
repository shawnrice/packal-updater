<?php

require_once( __DIR__ . '/autoloader.php' );
use CFPropertyList\CFPropertyList as CFPropertyList;
use Alphred\Ini as Ini;

class Action {

	public function __construct( $args ) {
		$args = json_decode( $args, true );
		$this->alphred = new Alphred;
		// print_r( $args );
		$this->action   = $args['action'];
		$this->base_url = BASE_API_URL;

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
				$this->submit( 'theme', $this->theme );
				$this->clear_caches();
				break;
			case 'submit_workflow':
				$this->submit( 'workflow', $this->path );
				$this->clear_caches();
				break;
			case 'generate_ini':
				$this->generate_ini( $this->path );
				break;
			case 'configure':
				$this->configure( $this->target, $this->value );
				break;
			case 'report':
				$result = $this->report();
				if ( false === $result ) {
					$this->messages['subtitle'] = $this->workflow['name'];
					$this->messages['text'] = 'Canceled sending report';
				} else {
					$result = json_decode( $result, true );
					$this->messages['subtitle'] = 'Report `' . $this->workflow['name'] . '`';
					$this->messages['messages'] = [ $result[0]['message'] ];
				}
				break;
		endswitch;
		if ( ! isset( $this->messages ) ) {
			$this->messages['messages'] = [ 'Success!' ];
		}
		// Output the messages for the notifier.
		return json_encode( $this->messages );
	}

	private function report() {
		$values = [ 'workflow_name' => $this->workflow['name'],
								'version' => $this->workflow['version'],
								'revision' => $this->workflow['revision_id'],
								'workflow' => $this->workflow['id'],
		];
		if ( ! $parsed = Pashua::go( 'pashau-report-config.ini', $values ) ) {
			return false;
		}
		$params = [
			'workflow_revision_id' => $parsed['revision'],
			'report_type' => $parsed['type'],
			'message' => $parsed['message']
		];
		$output = submit_report( $params );
		return $output;
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
		$return = generate_ini( $path );
		$this->alphred->console( print_r( $workflow, true), 4);
		if ( $return[0] ) {
			$this->messages['subtitle'] = [ 'Workflow Generation Success' ];
			$this->messages['messages'] = [ 'Generated `workflow.ini` for ' . $return[1] ];
		} else {
			$this->messages['subtitle'] = [ 'Workflow Generation Failure' ];
			$this->messages['messages'] = [ 'Canceled `workflow.ini` for ' . $return[1] ];
		}

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
		$dir = "{$_SERVER['alfred_workflow_data']}/data/themes";
		if ( ! file_exists( "{$_SERVER['alfred_workflow_data']}/data/themes" ) ) {
			mkdir( "{$_SERVER['alfred_workflow_data']}/data/themes" );
		}
		$file = "{$_SERVER['alfred_workflow_data']}/data/themes/submit-" . $this->slugify( $theme['name'] ) . ".json";
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
		$data['theme_tags'] = implode( '[return]', $data['theme_tags'] );
		if ( ! $parsed = Pashua::go( 'pashau-theme-config.ini', $data ) ) {
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
		$request->clear_cache( PRIMARY_CACHE_BIN );
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
		$this->messages = [];
		exec( "open {$location}" );
	}

	private function find_workflow_by_bundle( $bundle ) {
		$workflows = json_decode( file_get_contents( MapWorkflows::map() ), true );
		foreach ( $workflows as $workflow ) :
			if ( $bundle == $workflow['bundle'] ) {
				return true;
			}
		endforeach;
		return false;
	}

	/**
	 * Installs a workflow file
	 *
	 * @todo Sanitize the plist before installing.
	 *
	 * @param  [type] $url [description]
	 * @return [type]      [description]
	 */
	private function install( $url ) {
		if ( $this->find_workflow_by_bundle( $this->workflow['bundle'] ) ) {
			$this->messages['subtitle'] = 'Install `' . $this->workflow['name'] . '`';
			$this->messages['messages'] = [ 'Error: workflow already installed.' ];
			return true;
		}
		if ( ! $this->valid( $url ) ) {
			$this->log_failure( "Location {$url} is not a valid location." );
			return false;
		}
		// I should replace this method with one that installs silently... in the
		// background.
		if ( $file = $this->download( $url ) ) {
			if ( $this->verify_file( $file, $this->workflow['md5'] ) ) {
				$directory = $this->create_unique_workflow_directory();
				$zip = new ZipArchive;
				if ( true === $zip->open( $file ) ) {
				  $zip->extractTo( $directory );
				  $zip->close();
				  Plist::import_sanitize( "{$directory}/info.plist" );
  				$this->messages['subtitle'] = 'Install `' . $this->workflow['name'] . '`';
					$this->messages['messages'] = [ 'Success!' ];

					// Re-map the workflows so that they aren't stale.
					MapWorkflows::map( true, 0 );
				} else {
					$this->messages['subtitle'] = 'Install `' . $this->workflow['name'] . '`';
					$this->messages['messages'] = [ 'Error unzipping file.' ];
				  $this->log_failure( "Could not unpack ({$file})." );
				}
				// Delete the file from the Downloads directory
		    unlink( $file );
			} else {
				$this->messages['subtitle'] = 'Install `' . $this->workflow['name'] . '`';
				$this->messages['messages'] = [ 'Error: file hashes do not match.' ];
				$this->log_failure( "Could not verify file ({$file}). File hashes do not match." );
			}
		} else {
			$this->messages['subtitle'] = 'Install `' . $this->workflow['name'] . '`';
			$this->messages['messages'] = [ 'Error: could not download file.' ];
			$this->log_failure( "Could not download file ({$url})." );
		}
	}

	private function create_unique_workflow_directory() {
		$workflow_directory = $this->find_workflows_directory();
		$dir = $workflow_directory . "/" . generate_uuid();
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir, 0775 );
			return $dir;
		}
		$this->create_unique_workflow_directory();
	}

	private function find_workflows_directory() {
		$preferences = "{$_SERVER['HOME']}/Library/Preferences/com.runningwithcrayons.Alfred-Preferences.plist";
		$preferences = new CFPropertyList( $preferences, CFPropertyList::FORMAT_BINARY );
		$preferences = $preferences->toArray();

		if ( isset( $preferences['syncfolder'] ) ) {
			$workflows = str_replace( '~', $_SERVER['HOME'], $preferences['syncfolder'] ) . '/Alfred.alfredpreferences/workflows';
		} else {
			$workflows = "{$_SERVER['HOME']}/Library/Application Support/Alfred 2/Alfred.alfredpreferences/workflows";
		}
		return $workflows;
	}

	private function log_failure( $message ) {
		$this->messages['status'] 		= 'fail';
		$this->messages['messages'][] = $message;
	}
}

$action = new Action( $argv[1] );
echo $action->do_action();
