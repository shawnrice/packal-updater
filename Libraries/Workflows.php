<?php

/**
 * Misc Functions
 */
use CFPropertyList\CFPropertyList as CFPropertyList;

class Workflows {

	function __construct( $environment ) {
		// Do I need environment for this one?
		$this->alphred = new Alphred;
		$this->packal = new Packal( $environment );
	}

	function find_upgrades() {
		$packal_workflows = $this->packal->download_workflow_data();
		$packal_workflows = $packal_workflows['workflows'];
		$my_workflows = json_decode( file_get_contents( MapWorkflows::map() ), true );
		$my_packal_workflows = $this->alphred->filter( $my_workflows, 1, 'packal', [ 'match_type' => MATCH_STARTSWITH ] );
		$this->upgrades = [];
		foreach( $packal_workflows as $workflow ) :
			foreach( $my_packal_workflows as $my_packal_workflow ) :
				if ( $workflow['bundle'] == $my_packal_workflow['bundle'] ) {
					if ( SemVer::gt( $workflow['version'], $my_packal_workflow['version'] ) ) {
						$this->upgrades[] = [ 'old' => $my_packal_workflow, 'new' => $workflow ];
					}
				}
			endforeach;
		endforeach;
	}

	public static function find_workflows_directory() {
		$preferences = "{$_SERVER['HOME']}/Library/Preferences/com.runningwithcrayons.Alfred-Preferences.plist";
		$preferences = new CFPropertyList( $preferences, CFPropertyList::FORMAT_BINARY );
		$preferences = $preferences->toArray();

		if ( isset( $preferences['syncfolder'] ) ) {
			$workflows_directory = str_replace( '~', $_SERVER['HOME'], $preferences['syncfolder'] ) . '/Alfred.alfredpreferences/workflows';
		} else {
			$workflows_directory = "{$_SERVER['HOME']}/Library/Application Support/Alfred 2/Alfred.alfredpreferences/workflows";
		}
		return $workflows_directory;
	}

	public static function create_unique_workflow_directory() {
		$directory = self::find_workflows_directory() . "/" . self::generate_workflow_directory_name();
		if ( ! file_exists( $directory ) ) {
			mkdir( $directory, 0775 );
			return $directory;
		}
		self::create_unique_workflow_directory();
	}

	public static function generate_workflow_directory_name() {
		$chars = str_split( '0123456789ABCDEF' );
		$output = 'user.workflow.';
		foreach( [ 8, 4, 4, 4, 12 ] as $number ) :
			for( $i = 0; $i < $number; $i++ ) :
				$output .= $chars[ rand( 0, 15 ) ];
			endfor;
			$output .= '-';
		endforeach;
		return substr( $output, 0, strlen( $output ) - 1 );
	}

	public static function find_workflow_installed_by_bundle( $bundle ) {
		$workflows = json_decode( file_get_contents( MapWorkflows::map() ), true );
		foreach ( $workflows as $workflow ) :
			if ( $bundle == $workflow['bundle'] ) {
				return true;
			}
		endforeach;
		return false;
	}

	public static function find_workflow_path_by_bundle( $bundle ) {
		$workflows = json_decode( file_get_contents( MapWorkflows::map() ), true );
		foreach ( $workflows as $workflow ) :
			if ( $bundle == $workflow['bundle'] ) {
				return $workflow['path'];
			}
		endforeach;
		return false;
	}

	public function find_workflow_by_bundle_from_packal( $bundle ) {
		$workflows = $this->packal->download_workflow_data();
		foreach( $workflows['workflows'] as $workflow ) :
			if ( $bundle == $workflow['bundle'] ) {
				return $workflow;
			}
		endforeach;
		return "Cannot find workflow with bundle `{$bundle}`";
	}


	private function do_install( $new, $old = false, $verify_signature = true ) {
		if ( false !== $old ) {
			$upgrade = true;
			$old_workflow_path = $old['path'];
			$workflow = $new;
		} else {
			$upgrade = false;
			$workflow = $new;
		}
		$directory = FileSystem::make_random_temp_dir();
		$destination = self::find_workflow_path_by_bundle( $workflow['bundle'] );
		$to_clean = [ $directory ];
		$signature = $new['signature'];
		while ( true ) :
			if ( $upgrade ) {
				$key = "{$old_workflow_path}/packaging/{$old['bundle']}.pub";
				if ( ! $destination ) {
					$return = 'Workflow cannot be upgraded because it is not installed';
					break;
				}
			} else {
				$key = "{$directory}/packaging/{$new['bundle']}.pub";
				if ( $destination ) {
					$return = 'Workflow is already installed';
					break;
				}
				$destination = self::create_unique_workflow_directory();
			}
			if ( ! $file = FileSystem::download_file( $workflow['file'], $directory ) ) {
				$return = 'Could not download the file';
				break;
			}
			if ( ! FileSystem::verify_download( $file, $workflow['md5'] ) ) {
				$return = 'Could not verify the download hash';
				break;
			}
			if ( ! $unpack_directory = FileSystem::extract_to_temp( $file ) ) {
				$return = 'Could not extract the archive';
				break;
			}
			if ( $verify_signature ) {
				if ( $upgrade ) {
					$key = "{$destination}/packaging/{$new['bundle']}.pub";
				} else {
					$key = "{$unpack_directory}/packaging/{$new['bundle']}.pub";
				}

				$signature = $new['signature'];
				if ( false === FileSystem::verify_signature( $signature, $file, $key ) ) {
					$return = 'Could not verify file signature.';
					break;
				}
			}
			if ( $upgrade ) {
				if ( ! Plist::migrate_plist( "{$destination}/info.plist", "{$unpack_directory}/info.plist" ) ) {
					$return = 'Could not migrate plist settings.';
					break;
				}
				FileSystem::recurse_unlink( $destination );
			} else {
				if ( ! Plist::import_sanitize( "{$unpack_directory}/info.plist" ) ) {
					$return = 'Could not santize the new plist.';
					break;
				}
			}
			if ( ! rename( $unpack_directory, $destination ) ) {
				$return = 'Could not move new directory into place.';
				break;
			}
			break;
		endwhile;
		if ( ( false === $upgrade ) && isset( $return ) ) {
			$to_clean[] = $destination;
		}
		@FileSystem::clean_up( $to_clean );
		if ( isset( $return ) ) {
			return $return;
		}
		$this->packal->post_download( 'workflow', $workflow );
		return true;
	}


	public function upgrade( $upgrade ) {
		return $this->do_install( $upgrade['new'], $upgrade['old'] );
	}

	public function install( $workflow ) {
		return $this->do_install( $workflow );
	}

}



