<?php

require_once( __DIR__ . '/../autoloader.php' );

class MapThemes {

	public function map() {
		self::make_data_directory();
		$themes = self::get_theme_json();
		if ( file_put_contents(
			"{$_SERVER['alfred_workflow_data']}/data/themes/theme_map.json",
			json_encode( $themes, JSON_PRETTY_PRINT )
		) ) {
			return true;
		}
		return false;
	}

	private function make_data_directory() {
		if ( ! file_exists( "{$_SERVER['alfred_workflow_data']}/data/themes/" ) ) {
			mkdir( "{$_SERVER['alfred_workflow_data']}/data/themes/", 0775, true );
		}
	}

	private function get_theme_json() {
		exec( '"' . __DIR__ . '/../Tools/ReadThemes"', $themes );
		$themes = '{' . substr( implode( "\n", $themes ), 0, -1 ) . '}';
		$themes = str_replace( ",\n}", "\n}", $themes );
		$themes = json_decode( $themes, true );
		return self::build_json_map( $themes );
	}

	private function build_json_map( $themes ) {
		$candidates = [];
		foreach ( $themes as $uid => $theme ) :
			if ( ! isset( $theme['credits'] ) ) {
				continue;
			}
			unset( $theme['uid'] );
			$candidates[ $uid ]['name'] = $theme['name'];
			$candidates[ $uid ]['author'] = $theme['credits'];
			$uri = 'alfred://theme/';

			foreach ( $theme as $key => $val ) :
				$uri .= $key . '=' . $val . '&';
				endforeach;

			$uri = substr( str_replace( [ "'", ';' ], '', $uri ), 0, -1 );

			// We just need to uriencode the spaces. I think...
			$uri = str_replace( ' ', '%20', $uri );
			$candidates[ $uid ]['uri'] = $uri;
			endforeach;
		return $candidates;
	}
}
