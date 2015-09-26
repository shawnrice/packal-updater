<?php

class Themes {

	function __construct( $environment ) {
		$this->alphred = new Alphred;
		$this->packal = new Packal( $environment );
	}

	public function get_theme_uri_by_slug( $slug ) {
		if ( false === $theme = $this->find_theme_by_slug( $slug ) ) {
			return "Error: there is no theme with the slug `{$slug}`";
		}
		return $theme['uri'];
	}

	public function install( $slug ) {
		if ( false === $uri = $this->get_theme_uri_by_slug( $slug ) ) {
			return [ false, "Cannot install theme with {$slug} because no theme with that slug exists." ];
		}
		$theme = $this->find_theme_by_slug( $slug );
		$this->packal->post_download( 'theme', $theme );
		exec("open '{$uri}'");
		return [ true, $theme['name'] ];
	}

	/**
	 * [find_one_theme description]
	 *
	 * There is probably a better way to do this.
	 *
	 * @param  [type] $slug [description]
	 * @return [type]       [description]
	 */
	public function find_theme_by_slug( $slug ) {
		$themes = $this->packal->download_theme_data();
		$themes = $this->alphred->filter( $themes['themes'], $slug, 'url', [ 'match_type' => MATCH_SUBSTRING ] );
		if ( 0 === count( $themes ) ) {
			return false;
		}
		if ( 1 === count( $themes ) ) {
			return $themes[0];
		}
		foreach( $themes as $theme ) {
			$tmp_slug = self::find_slug( $theme['url'] );
			if ( $tmp_slug == $slug ) {
				return $theme;
			}
		}
		return false;
	}

	/**
	 * Pulls the slug off the URL
	 *
	 * @param  string $url a url
	 * @return string      the slug
	 */
	public static function find_slug( $url ) {
		return substr( $url, strrpos( $url, '/') + 1 );
	}

}



