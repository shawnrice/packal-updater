<?php

	/**
	 * Small convenience class to call Pashua. This is very much this-workflow specific
	 */
	class Pashua {

		private static $path = '/../Resources/Pashua.app/Contents/MacOS/Pashua';
		private static $conf_dir =  '/../Resources';

		static public function go( $conf, $replacements = false ) {
			$conf = file_get_contents( realpath( __DIR__ . self::$conf_dir . '/' . $conf ) );
			$conf = self::replace_values( $conf, $replacements );
			$temp = self::write_temp( $conf );
			$results = self::call( $temp );
			return self::parse( $results );
		}

		private function path() {
			return realpath( __DIR__ . self::$path );
		}

		private function replace_values( $conf, $replacements ) {
			foreach ( $replacements as $key => $val ) :
				$conf = str_replace( '%%' . $key . '%%', $val, $conf );
			endforeach;
			return $conf;
		}

		private function write_temp( $conf ) {
			$config = tempnam( '/tmp', 'Pashua_' );
			if ( false === $fp = @fopen( $config, 'w' ) ) {
			    throw new \RuntimeException( "Error trying to open {$config}" );
			}
			fwrite( $fp, $conf );
			fclose( $fp );
			return $config;
		}

		private function call( $temp ) {
			// Call pashua binary with config file as argument and read result
			$result = shell_exec( escapeshellarg( self::path() ) . ' ' . escapeshellarg( $temp ) );
			@unlink( $temp );
			return $result;
		}

		private function parse( $result ) {
		  // Parse result
			$parsed = [];
			foreach ( explode( "\n", $result ) as $line ) :
		    preg_match( '/^(\w+)=(.*)$/', $line, $matches );
		    if ( empty( $matches ) or empty( $matches[1] ) ) {
	        continue;
		    }
		    $parsed[ $matches[1] ] = $matches[2];
			endforeach;
			if ( 1 == $parsed['cb'] ) {
				return false;
			}
			return $parsed;
		}

	}