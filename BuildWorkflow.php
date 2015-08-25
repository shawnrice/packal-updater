<?php

// Usage:
// $archive = new BuildWorkflow( '/path/to/alfredworkflow_directory' );
// $filname = $archive->archive_name();
// Do other things here
// When done:
// $archive->remove_temp_dir();


class BuildWorkflow {

	var $excluded_files = [
		'/\.git/',
		'/\.gitignore/',
		'/\.DS_Store/',
		'/__MACOSX/',
		'/.*\.sublime-project/',
		'/.*\.sublime-workspace/',
		'/.*\.py[cod]/',
		'/packal/',
		'/.*\.iml/',
		'/\.idea/',
		'/.*\.ipr/',
		'/.*\.iws/',
		'/\.idea_modules/',
		'/__pycache__/',
		'/.*\.pyc/',
		'/.*\.pyo/',
		'/.*\.pyd/',
		'/.*\.so/',
		'/\.Python/',
		'/env/',
		'/build/',
		'/develop-eggs/',
		'/dist/',
		'/downloads/',
		'/eggs/',
		'/\.eggs/',
		'/lib/',
		'/lib64/',
		'/parts/',
		'/sdist/',
		'/var/',
		'/.*\.egg-info/',
		'/\.installed\.cfg/',
		'/.*\.egg/',
		'/.*\.manifest/',
		'/.*\.spec/',
		'/pip-log\.txt/',
		'/pip-delete-this-directory\.txt/',
		'/htmlcov/',
		'/\.tox/',
		'/\.coverage/',
		'/\.coverage\..*/',
		'/\.cache/',
		'/nosetests\.xml/',
		'/coverage\.xml/',
		'/.*\.mo/',
		'/.*\.pot/',
		'/.*\.log/',
		'/docs\/_build/',
		'/target/',
		'/.*\.gem/',
		'/.*\.rbc/',
		'/\.config/',
		'/coverage/',
		'/InstalledFiles/',
		'/pkg/',
		'/spec\/reports/',
		'/test\/tmp/',
		'/test\/version_tmp/',
		'/tmp/',
		'/\.dat.*/',
		'/\.repl_history/',
		'/build/',
		'/\.yardoc/',
		'/_yardoc/',
		'/doc/',
		'/rdoc/',
		'/\.bundle/',
		'/vendor\/bundle/',
		'/lib\/bundler\/man/',
		'/\.rvmrc/',
		'/blib/',
		'/\.build/',
		'/_build/',
		'/cover_db/',
		'/inc/',
		'/Build/',
		'/!Build/',
		'/Build\.bat/',
		'/\.last_cover_stats/',
		'/Makefile/',
		'/Makefile\.old/',
		'/MANIFEST\.bak/',
		'/META\.yml/',
		'/META\.json/',
		'/MYMETA\..*/',
		'/nytprof\.out/',
		'/pm_to_blib/',
		'/.*\.o/',
		'/.*\.bs/',
		'/_eumm/',
		'/pids/',
		'/.*\.pid/',
		'/.*\.seed/',
		'/logs/',
		'/.*\.log/',
		'/lib-cov/',
		'/coverage/',
		'/\.grunt/',
		'/\.lock-wscript/',
		'/build\/Release/',
		'/node_modules/',
		'/[._].*\.s[a-w][a-z]/',
		'/[._]s[a-w][a-z]/',
		'/.*\.un~/',
		'/Session\.vim/',
		'/\.netrwhist/',
		'/.*~/',
		'/sftp-config\.json/',
		'/.*\.tmlanguage\.cache/',
		'/.*\.tmPreferences\.cache/',
		'/.*\.stTheme\.cache/',
		'/.*\.tmproj/',
		'/.*\.tmproject/',
		'/tmtags/',
		'/\.*\.swp/',
	];

	function __construct( $directory, $screenshots_directory = false ) {
		$this->directory = $directory;
		if ( ! $this->check_for_workflow() ) {
			throw new Exception( 'Nope, that did not work' );
			// return false; // Should I raise an exception?
		}

		$this->make_random_temp_dir();
		$this->recurse_copy( $this->directory, $this->tmp );
		if ( ( $screenshots_directory ) && file_exists( $screenshots_directory ) && is_dir( $screenshots_directory ) ) {
			// I could always add in something here to make sure that the directory name is called 'screenshots'
			$this->recurse_copy( $screenshots_directory, $this->tmp );
		}
		$this->files = [];
		$this->read_directory( $this->tmp );
		$this->create_archive();
	}

	public function archive_name() {
		return "{$this->tmp}/workflow.alfredworkflow";
	}

	private function create_archive() {
		$zip = new ZipArchive();
		if ( true !== $zip->open( "{$this->tmp}/workflow.alfredworkflow", ZipArchive::CREATE ) ) {
	    return false;
		}
		foreach( $this->files as $file ) :
			$zip->addFile( $file, str_replace( $this->tmp, '', $file ) );
		endforeach;
		$zip->close();
	}

	public function remove_temp_dir( $directory ) {
		recurse_unlink( $this->tmp );
	}

	private function read_directory( $directory ) {
		foreach( array_diff( scandir( $directory ), [ '.', '..' ] ) as $file ) :
			if ( is_dir( "{$directory}/{$file}" ) ) {
				$this->read_directory( "{$directory}/{$file}" );
			} else {
				$this->files[] = "{$directory}/{$file}";
			}
		endforeach;

	}

	private function make_random_temp_dir() {
		$letters = 'abcdefghijklmnopqrstuvwxyz';
		$random = '';
		for ($i = 0; $i < 10; $i++ ) :
			$random .= $letters[rand( 0 , strlen( $letters ) - 1 )];
		endfor;

		$this->tmp = sys_get_temp_dir() . '/' . $random;
		mkdir( $this->tmp );
	}

	private function check_for_workflow() {
		if ( ! file_exists( "{$this->directory}/info.plist" ) ) {
			return false;
		}
		// if ( ! file_exists( "{$this->directory}/workflow.ini" ) ) {
		// 	return false;
		// }
		return true;
	}

	private function recurse_copy( $source, $destination ) {

		$excluded = [ '/\.git/', '/.*\.pyc/' ];

    $directory = opendir( $source );
    if ( ! file_exists( $destination ) ) {
    	mkdir( $destination );
    }
    while( false !== ( $file = readdir( $directory ) ) ) :
      if ( ( $file != '.' ) && ( $file != '..' ) ) {
      	$valid = true;
      	foreach ( $this->excluded_files as $pattern ) :
	    		if ( preg_match( $pattern, $file ) ) {
	    			$valid = false;
	    			break;
	    		}
    		endforeach;
    		if ( ! $valid ) {
    			continue;
    		}
        if ( is_dir( $source . '/' . $file ) ) {
          $this->recurse_copy( $source . '/' . $file, $destination . '/' . $file );
        } else {
          copy( $source . '/' . $file, $destination . '/' . $file );
        }
      }
    endwhile;

    closedir( $directory );
	}

	private function recurse_unlink( $directory ) {
	  if ( ! $directory_handle = @opendir( $directory ) ) {
	    return;
	  }

	  while ( false !== ( $file = readdir( $directory_handle ) ) ) :
	    if( $file == '.' || $file == '..' ) {
	      continue;
	    }
	    if ( is_dir( $directory . '/' . $file ) ) {
	    	$this->recurse_unlink( $directory . '/' . $file );
	    }
	  endwhile;

	  closedir( $directory_handle );
	  @rmdir( $directory );

	  return;
	}

}

$dir = '/Users/Sven/Dropbox/app syncing/alfred2/Alfred.alfredpreferences/workflows/user.workflow.67A4F4B2-CAD7-41E5-8EC9-322620012FFF';
$archive = new BuildWorkflow( $dir );
echo $archive->archive_name();