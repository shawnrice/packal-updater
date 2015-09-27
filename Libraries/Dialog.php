<?php

class Dialog {

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

}