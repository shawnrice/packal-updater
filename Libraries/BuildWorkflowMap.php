<?php
/**
 *
 * @todo Remove the hack in the function: check_for_mine()
 *
 */

require_once( __DIR__ . '/../autoloader.php' );

use CFPropertyList\CFPropertyList as CFPropertyList;
// use Alphred\Ini as Ini;

class MapWorkflows {

	public static function map( $no_cache = false, $ttl = 604800 ) {
		self::make_data_directory();

		if ( self::use_cache( $no_cache, $ttl ) ) {
			return self::map_path();
		}
		$alphred    = new Alphred;
		$me         = $alphred->config_read( 'authorname' );
		$directory  = self::find_workflows_directory();
		$workflows  = array_diff( scandir( $directory ), [ '.', '..' ] );
		$files      = [];
		$old_packal = [];
		$mine       = [];
		foreach ( $workflows as $workflow ) :
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
			if ( true === self::check_for_mine( $file, $me ) ) {
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

	public static function map_path() {
		return "{$_SERVER['alfred_workflow_data']}/data/workflows/workflow_map.json";
	}

	public static function my_workflows_path() {
		return "{$_SERVER['alfred_workflow_data']}/data/workflows/mine.json";
	}

	public static function migrate_path() {
		return "{$_SERVER['alfred_workflow_data']}/data/workflows/old_packal.json";
	}

	public static function clear_cache() {
		@unlink( self::migrate_path() );
		@unlink( self::map_path() );
		@unlink( self::my_workflows_path() );
		return true;
	}

	private static function write_old_packal_migration( $old_packal ) {
		// This is the old Packal workflow list.
		if ( count( $old_packal ) > 0 ) {
			file_put_contents( self::migrate_path(), json_encode( $old_packal, JSON_PRETTY_PRINT ) );
		}
	}

	private static function use_cache( $no_cache, $ttl ) {
		if ( false === $no_cache ) {
			return false;
		}
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

	private static function check_for_expire_cache() {
		if ( file_exists( "{$_SERVER['alfred_workflow_data']}/expire_local_workflow_cache" ) ) {
			unlink( "{$_SERVER['alfred_workflow_data']}/expire_local_workflow_cache" );
			return true;
		}
		return false;
	}

	private static function check_map_cache( $ttl ) {
		// If the cache is set to false or 0, then just return false, indicating that the cache is expired
		if ( ! $ttl ) {
			return false;
		}
		// A value of -1 means an infinite cache
		if ( -1 === $ttl ) {
			return true;
		}
		// Is there still time left in the time to live? Check the file modified time v current time
		if ( ( time() - filemtime( self::map_path() ) ) > $ttl ) {
			return true;
		}
		// The cache is not valid
		return false;
	}

	private static function make_data_directory() {
		if ( ! file_exists( "{$_SERVER['alfred_workflow_data']}/data/workflows/" ) ) {
			mkdir( "{$_SERVER['alfred_workflow_data']}/data/workflows/", 0775, true );
		}
	}

	/**
	 * [remove_old_data description]
	 *
	 * @todo Remove this method
	 *
	 * @return [type] [description]
	 */
	private static function remove_old_data() {
		return self::clear_cache();
	}

	private static function find_workflows_directory() {
		$preferences = "{$_SERVER['HOME']}/Library/Preferences/com.runningwithcrayons.Alfred-Preferences.plist";
		$preferences = new CFPropertyList( $preferences, CFPropertyList::FORMAT_BINARY );
		$preferences = $preferences->toArray();

		if ( isset( $preferences['syncfolder'] ) ) {
			$workflows  = str_replace( '~', $_SERVER['HOME'], $preferences['syncfolder'] );
			$workflows .= '/Alfred.alfredpreferences/workflows';
		} else {
			$workflows = "{$_SERVER['HOME']}/Library/Application Support/Alfred 2/Alfred.alfredpreferences/workflows";
		}
		return $workflows;
	}

	private static function check_for_packal( $directory ) {
		if ( file_exists( "{$directory}/packaging" ) && is_dir( "{$directory}/packaging" ) ) {
			return true;
		}
		return false;
	}

	private static function check_for_old_packal( $directory ) {
		if ( file_exists( "{$directory}/packal/package.xml" ) ) {
			return true;
		}
		return false;
	}

	private static function check_for_plist( $directory ) {
		if ( ! file_exists( "{$directory}/info.plist" ) ) {
			return false;
		}
		return true;
	}

	private static function get_plist_info( $directory ) {
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
			$return['version'] = $version;
		}

		$return['packal'] = self::check_for_packal( $directory );

		return $return;
	}

	/**
	 * [check_for_mine description]
	 *
	 * @todo Remove the hack
	 *
	 * @param  [type] $plist [description]
	 * @param  [type] $me    [description]
	 * @return [type]        [description]
	 */
	private static function check_for_mine( $plist, $me ) {
		// This is a hack
		$me              = mb_ereg_replace( '[A-Za-z][^A-Za-z0-9\.\- ]', '', $me );
		$plist['author'] = mb_ereg_replace( '[^A-Za-z0-9\.\- ]', '', $plist['author'] );
		if ( $me === $plist['author'] ) {
			return true;
		}
		return false;
	}

	private static function read_info_plist( $directory ) {
		$plist = new CFPropertyList( "{$directory}/info.plist", CFPropertyList::FORMAT_XML );
		$plist = $plist->toArray();

		if ( empty( $plist['bundleid'] ) || empty( $plist['createdby'] ) ) {
			return false;
		}
		return $plist;
	}

	private static function read_workflow_ini( $directory ) {
		if ( ! file_exists( "{$directory}/workflow.ini" ) ) {
			return false;
		}
		$ini = \Alphred\Ini::read_ini( "{$directory}/workflow.ini" );
		if ( ! isset( $ini['workflow']['version'] ) ) {
			return false;
		}
		return $ini['workflow']['version'];
	}
}
