<?php
require_once( __DIR__ . '/../autoloader.php' );

use CFPropertyList\CFPropertyList as CFPropertyList;
use Alphred\Ini as Ini;

class MapWorkflows {

	public static function map( $no_cache = false, $ttl = 604800 ) {
		self::make_data_directory();

		if ( self::use_cache( $no_cache, $ttl ) ) {
			return self::map_path();
		}

		$directory = self::find_workflows_directory();
		$workflows = array_diff( scandir( $directory ), [ '.', '..' ] );
		$files = [];
		$old_packal = [];
		$mine = [];
		foreach( $workflows as $workflow ) :
			if ( ! self::check_for_plist( "{$directory}/{$workflow}" ) ) {
				continue;
			}
			// This is a poorly named variable now.
			if ( ! $file = self::get_plist_info( "{$directory}/{$workflow}" ) ) {
				continue;
			}
			// I should probably add in something to get the version number to put in here.
			if ( self::check_for_old_packal( "{$directory}/{$workflow}" ) ) {
				$old_packal[] = $file;
			}
			if ( true === self::check_for_mine( $file ) ) {
				$mine[] = $file;
			}
			$files[] = $file;
		endforeach;

		self::write_old_packal_migration( $old_packal );
		file_put_contents( self::my_workflows_path(), json_encode( $mine, JSON_PRETTY_PRINT ) );
		// This creates the map
		if ( file_put_contents( self::map_path(), json_encode( $files, JSON_PRETTY_PRINT ) ) ) {
			return self::map_path();
		}
		return false;
	}

	public function my_workflows_path() {
		return "{$_SERVER['alfred_workflow_data']}/data/workflows/mine.json";
	}

	public function clear_cache() {
		if ( file_exists( "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json" ) ) {
			unlink( "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json" );
		}
		if ( file_exists( self::map_path() ) ) {
			unlink( self::map_path() );
		}
		return true;
	}

	private function write_old_packal_migration( $old_packal ) {
		// This is the old Packal workflow list.
		if ( count( $old_packal ) > 0 ) {
			file_put_contents(
			  "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json",
			  json_encode( $old_packal, JSON_PRETTY_PRINT ) );
		}
	}

	private function use_cache( $no_cache, $ttl ) {
		// So, first we're going try to deal with some caching in order to
		// speed this up. If the file exists, then we'll see if we can
		// use the cache.
		if ( ! file_exists( self::map_path() ) ) {
			// We'll check to see if we have some instructions to force expire the cache.
			// This check is usually redundant, but I'm okay with redundancy, as long as
			// it doesn't take long.
			return false;
		}
		if ( self::check_for_expire_cache() ) {
			return false;
		}
		// If the no_cache flag variable was not set, then we'll
		// see if the cache is valid.
		if ( ! $no_cache ) {
			// If the cache is valid, then just return the path.
			if ( self::check_map_cache( $ttl ) ) {
				return true;
			}
		}
		return false;
	}

	private function map_path() {
		return "{$_SERVER['alfred_workflow_data']}/data/workflows/workflow_map.json";
	}

	private function check_for_expire_cache() {
		if ( file_exists( "{$_SERVER['alfred_workflow_data']}/expire_local_workflow_cache" ) ) {
			unlink( "{$_SERVER['alfred_workflow_data']}/expire_local_workflow_cache" );
			return true;
		}
		return false;
	}

	private function check_map_cache( $ttl ) {
		if ( ! $ttl ) {
			// If the cache is set to false or 0, then just return false, indicating
			// that the cache is expired
			return false;
		}
		if ( ( filemtime( self::map_path() ) + $ttl ) > time() ) {
			return true;
		}
		return false;
	}

	private function make_data_directory() {
		if ( ! file_exists( "{$_SERVER['alfred_workflow_data']}/data/workflows/" ) ) {
			mkdir( "{$_SERVER['alfred_workflow_data']}/data/workflows/", 0775, true );
		}
	}

	private function remove_old_data() {
		if ( file_exists( "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json" ) ) {
			unlink( "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json" );
		}
		if ( file_exists( "{$_SERVER['alfred_workflow_data']}/data/workflows/workflow_map.json") ) {
			unlink( "{$_SERVER['alfred_workflow_data']}/data/workflows/workflow_map.json" );
		}
	}

	private function find_workflows_directory() {
		$preferences = "{$_SERVER['HOME']}/Library/Preferences/com.runningwithcrayons.Alfred-Preferences.plist";
		$preferences = new CFPropertyList( $preferences, CFPropertyList::FORMAT_BINARY);
		$preferences = $preferences->toArray();

		if ( isset( $preferences['syncfolder'] ) ) {
			$workflows = str_replace( '~', $_SERVER['HOME'], $preferences['syncfolder']) . '/Alfred.alfredpreferences/workflows';
		} else {
			$workflows = "{$_SERVER['HOME']}/Library/Application Support/Alfred 2/Alfred.alfredpreferences/workflows";
		}
		return $workflows;
	}

	private function check_for_packal( $directory ) {
		if ( file_exists( "{$directory}/packaging" ) && is_dir( "{$directory}/packaging" ) ) {
			return true;
		}
		return false;
	}

	private function check_for_old_packal( $directory ) {
		if ( file_exists( "{$directory}/packal/package.xml" ) ) {
			return true;
		}
		return false;
	}

	private function check_for_plist( $directory ) {
		if ( ! file_exists( "{$directory}/info.plist" ) ) {
			return false;
		}
		return true;
	}

	private function get_plist_info( $directory ) {
		if ( ! $plist = self::read_info_plist( $directory ) ) {
			return false;
		}
		$return = [
			'path'   => $directory,
			'bundle' => $plist['bundleid'],
			'author' => $plist['createdby'],
			'name'   => $plist['name'],
		];
		if ( $version = self::read_workflow_ini( $directory ) ) {
			$return[ 'version' ] = $version;
		}

		$return['packal'] = self::check_for_packal( $directory );

		return $return;
	}

	private function check_for_mine( $plist ) {

		$alphred = new Alphred;
		$me = $alphred->config_read( 'username' );
		if ( $me == $plist['author'] ) {
			return true;
		}
		return false;
	}

	private function read_info_plist( $directory ) {
		$plist = new CFPropertyList( "{$directory}/info.plist", CFPropertyList::FORMAT_XML);
		$plist = $plist->toArray();

		if ( empty( $plist['bundleid'] ) || empty( $plist['createdby'] ) ) {
			return false;
		}
		return $plist;
	}

	private function read_workflow_ini( $directory ) {
		if ( ! file_exists( "{$directory}/workflow.ini" ) ) {
			return false;
		}
		$ini = Ini::read_ini( "{$directory}/workflow.ini" );
		if ( ! isset( $ini['global']['version'] ) ) {
			return false;
		}
		return $ini['global']['version'];
	}
}