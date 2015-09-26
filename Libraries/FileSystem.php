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
	    if ( is_dir( "{$directory}/{$file}" ) ) {
	    	self::recurse_unlink( "{$directory}/{$file}" );
	    } else {
	    	unlink( "{$directory}/{$file}" );
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

	public static function get_filename( $url ) {
		return self::valid_location( $url ) ? substr( $url, strrpos( $url, '/' ) + 1 ) : false;
	}

	public static function valid_location( $location ) {
		if ( file_exists( $location ) ) {
			return true;
		}
		if ( self::verify_url( $location ) ) {
			return true;
		}
		return false;
	}

	public static function verify_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

	public static function download_file( $url, $directory ) {
		if ( ! ( $file = self::get_filename( $url ) ) ) {
			return false;
		}
		if ( file_put_contents( "{$directory}/{$file}", file_get_contents( $url ) ) ) {
			return "{$directory}/{$file}";
		}
		return false;
	}

	public static function verify_download( $file, $md5 ) {
		return md5_file( $file ) == $md5;
	}

	public static function extract_archive( $archive, $destination ) {
		$zip = new ZipArchive;
		if ( true === $zip->open( $archive ) ) {
		  $zip->extractTo( $destination );
		  $zip->close();
		 } else {
		 	return false;
		 }
		 return true;
	}


	public static function extract_to_temp( $file ) {
		$directory = self::make_random_temp_dir();
		if ( ! self::extract_archive( $file, $directory ) ) {
			self::recurse_unlink( $directory );
			return false;
		}
		return $directory;
	}

	public static function clean_up( $directories ) {
		foreach( $directories as $directory ) :
			self::recurse_unlink( $directory );
		endforeach;
	}

	// This method should not be in the FileSystem class
	private function log( $message ) {
		if ( class_exists( 'Alphred' ) ) {
			$alphred = new Alphred;
			$alphred->console( "{$message}", 4 );
		} else {
			echo "{$message}\n";
		}
	}

	public static function slugify( $slug ) {
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

}



