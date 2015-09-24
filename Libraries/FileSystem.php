<?php

class FileSystem {

	public function make_random_temp_dir() {
		$letters = '0123456789abcdefghijklmnopqrstuvwxyz';
		for ($i = 0; $i < 20; $i++ ) :
			@$random .= $letters[ rand( 0 , strlen( $letters ) - 1 ) ];
		endfor;

		$dir = sys_get_temp_dir() . '/' . $random;
		mkdir( $dir, 0775 );
		return $dir;
	}

	public function recurse_copy( $source, $destination, $excluded = [] ) {

		$excluded = [ '/\.git/', '/.*\.pyc/' ];

    $directory = opendir( $source );
    if ( ! file_exists( $destination ) ) {
    	mkdir( $destination );
    }
    while( false !== ( $file = readdir( $directory ) ) ) :
      if ( ( $file != '.' ) && ( $file != '..' ) ) {
      	$valid = true;
      	foreach ( $excluded as $pattern ) :
	    		if ( preg_match( $pattern, $file ) ) {
	    			$valid = false;
	    			break;
	    		}
    		endforeach;
    		if ( ! $valid ) {
    			self::log( "Excluding {$file}.\n" );
    			continue;
    		}
    		// Don't recurse through symbolic links...
    		if ( is_link( "{$source}/{$file}" ) ) {
    			continue;
    		}
        if ( is_dir( "{$source}/{$file}" ) ) {
          self::recurse_copy( "{$source}/{$file}", "{$destination}/{$file}" );
        } else {
          copy( "{$source}/{$file}", "{$destination}/{$file}" );
        }
      }
    endwhile;

    closedir( $directory );
	}

	public function recurse_unlink( $directory ) {
	  if ( ! $directory_handle = @opendir( $directory ) ) {
	    return;
	  }

	  while ( false !== ( $file = readdir( $directory_handle ) ) ) :
	    if( $file == '.' || $file == '..' ) {
	      continue;
	    }
	    if ( is_dir( $directory . '/' . $file ) ) {
	    	self::recurse_unlink( $directory . '/' . $file );
	    }
	  endwhile;

	  closedir( $directory_handle );
	  @rmdir( $directory );

	  return;
	}

	public function read_directory( $directory, &$files ) {
		foreach( array_diff( scandir( $directory ), [ '.', '..' ] ) as $file ) :
			if ( is_dir( "{$directory}/{$file}" ) ) {
				self::read_directory( "{$directory}/{$file}", $files );
			} else {
				$files[] = "{$directory}/{$file}";
			}
		endforeach;
	}

	public function dir_exists( $dir ) {
		return ( $dir ) && file_exists( $dir ) && is_dir( $dir );
	}

	// This method should not be in the FileSystem class
	private function log( $message ) {
		if ( class_exists( 'Alphred' ) ) {
			$alphred = new Alphred;
			$alphred->console( "{$message}\n", 4 );
		} else {
			echo "{$message}\n";
		}
	}

}



