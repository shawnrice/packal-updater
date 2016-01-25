<?php

// Usage:
// $archive = new BuildWorkflow( '/path/to/alfredworkflow_directory' );
// $filname = $archive->archive_name();
// Do other things here
// When done:
// $archive->remove_temp_dir();
// $dir = '/Users/Sven/Dropbox/app syncing/alfred2/Alfred.alfredpreferences/workflows/user.workflow.67A4F4B2-CAD7-41E5-8EC9-322620012FFF/';
// $archive = new BuildWorkflow( $dir );
// $filename = $archive->archive_name();
// echo "File: {$filename}\n";
// echo "Tmp dir: {$archive->tmp}\n";

class BuildWorkflow {

	var $excluded_files = [
		'/\.git/',
		'/\.gitignore/',
		'/\.DS_Store/',
		'/__MACOSX/',
		'/.*\.sublime-project/',
		'/.*\.sublime-workspace/',
		'/.*\.py[cod]/',
		'/^packal$/',
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
		// '/^env/',
		// '/^build$/',
		// '/develop-eggs/',
		// '/dist/',
		// '/downloads/',
		// '/eggs/',
		// '/\.eggs/',
		// // '/lib/',
		// // '/lib64/',
		// '/parts/',
		// '/sdist/',
		// // '/var/',
		// '/.*\.egg-info/',
		// '/\.installed\.cfg/',
		// '/.*\.egg/',
		// '/.*\.manifest/',
		// '/.*\.spec/',
		// '/pip-log\.txt/',
		// '/pip-delete-this-directory\.txt/',
		// '/htmlcov/',
		// '/\.tox/',
		// '/\.coverage/',
		// '/\.coverage\..*/',
		// '/\.cache/',
		// '/nosetests\.xml/',
		// '/coverage\.xml/',
		// '/.*\.mo/',
		// '/.*\.pot/',
		// '/.*\.log/',
		// '/docs\/_build/',
		// '/target/',
		// '/.*\.gem/',
		// '/.*\.rbc/',
		// '/\.config/',
		// '/coverage/',
		// '/InstalledFiles/',
		// '/pkg/',
		// '/spec\/reports/',
		// '/test\/tmp/',
		// '/test\/version_tmp/',
		// '/tmp/',
		// '/\.dat.*/',
		// '/\.repl_history/',
		// '/build/',
		// '/\.yardoc/',
		// '/_yardoc/',
		// '/doc/',
		// '/rdoc/',
		// '/\.bundle/',
		// '/vendor\/bundle/',
		// '/lib\/bundler\/man/',
		// '/\.rvmrc/',
		// '/blib/',
		// '/\.build/',
		// '/_build/',
		// '/cover_db/',
		// '/inc/',
		// '/Build/',
		// '/!Build/',
		// '/Build\.bat/',
		// '/\.last_cover_stats/',
		// '/Makefile/',
		// '/Makefile\.old/',
		// '/MANIFEST\.bak/',
		// '/META\.yml/',
		// '/META\.json/',
		// '/MYMETA\..*/',
		// '/nytprof\.out/',
		// '/pm_to_blib/',
		// '/.*\.o/',
		// '/.*\.bs/',
		// '/_eumm/',
		// '/pids/',
		// '/.*\.pid/',
		// '/.*\.seed/',
		// '/logs/',
		// '/.*\.log/',
		// '/lib-cov/',
		// '/coverage/',
		// '/\.grunt/',
		// '/\.lock-wscript/',
		// '/build\/Release/',
		// '/node_modules/',
		// '/[._].*\.s[a-w][a-z]/',
		// '/[._]s[a-w][a-z]/',
		// '/.*\.un~/',
		// '/Session\.vim/',
		// '/\.netrwhist/',
		// '/.*~/',
		// '/sftp-config\.json/',
		// '/.*\.tmlanguage\.cache/',
		// '/.*\.tmPreferences\.cache/',
		// '/.*\.stTheme\.cache/',
		// '/.*\.tmproj/',
		// '/.*\.tmproject/',
		// '/tmtags/',
		// '/\.*\.swp/',
	];

	function __construct( $directory, $screenshots_directory = false, $description_file = false ) {
		$this->directory = $directory;
		if ( ! $this->check_for_workflow() ) {
			// Make this more informative.
			throw new Exception( 'Nope, that did not work' );
			// return false; // Should I raise an exception?
		}

		$this->tmp = FileSystem::make_random_temp_dir();
		FileSystem::recurse_copy( $this->directory, $this->tmp, $this->excluded_files );
		if ( false !== $screenshots_directory && FileSystem::dir_exists( $screenshots_directory ) ) {
			if ( ! file_exists( "{$this->tmp}/packal" ) ) {
				mkdir( "{$this->tmp}/packal", 0755, true );
			}
			if ( ! file_exists( "{$this->tmp}/packal/{$screenshots_directory}" ) ) {
				FileSystem::recurse_copy( $screenshots_directory, "{$this->tmp}/packal/screenshots/", $this->excluded_files );
			}
		}
		if ( false !== $description_file && file_exists( $description_file ) ) {
			if ( ! file_exists( "{$this->tmp}/packal" ) ) {
				mkdir( "{$this->tmp}/packal", 0755, true );
			}
			if ( ! file_exists( "{$this->tmp}/packal/README.md" ) ) {
				copy( $description_file, "{$this->tmp}/packal/README.md" );
			}
		}

		$this->files = [];
		FileSystem::read_directory( $this->tmp, $this->files );
		$this->create_archive();
	}

	public function archive_name() {
		return "{$this->tmp}/workflow.alfredworkflow";
	}

	public function remove_temp_dir( $directory ) {
		FileSystem::recurse_unlink( $this->tmp );
	}

	private function create_archive() {
		$zip = new ZipArchive();
		if ( true !== $zip->open( $this->archive_name(), ZipArchive::CREATE ) ) {
			return false;
		}
		foreach ( $this->files as $file ) :
			$zip->addFile( $file, str_replace( $this->tmp . '/', '', $file ) );
		endforeach;
		$zip->close();
	}

	private function check_for_workflow() {
		if ( ! file_exists( "{$this->directory}/info.plist" ) ) {
			return false;
		}
		if ( ! file_exists( "{$this->directory}/workflow.ini" ) ) {
			return false;
		}
		return true;
	}
}
