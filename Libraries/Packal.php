<?php

class Packal {

	function __construct( $environment ) {
		$this->environment = $environment;
		$this->alphred = new Alphred;
	}

	public static function ping() {
		if ( 'pong' == @file_get_contents( BASE_URL . '/ping' ) ) {
			return true;
		}
		return false;
	}

	public function download_theme_data() {
		$themes = $this->alphred->get( BASE_API_URL . '/theme?all' );
		file_put_contents( DATA . ENVIRONMENT . '/data/themes.json', $themes );
		return json_decode( $themes, true );
	}

	public function download_workflow_data() {
		$workflows = $this->alphred->get( BASE_API_URL . '/workflow?all' );
		file_put_contents( DATA . ENVIRONMENT . '/data/workflow.json', $workflows );
		return json_decode( $workflows, true );
	}

	/**
	 * [post_download description]
	 *
	 * Sends a POST request to track downloads / installs on workflows and themes.
	 *
	 * @param  [type] $type       [description]
	 * @param  [type] $properties [description]
	 * @return [type]             [description]
	 */
	public function post_download( $type, $properties ) {
		$json = [
				'id'                   => self::uuid(),
				'visit_id'             => self::uuid(),
				'user_id'              => null,
				'name'                 => "{$type}-{$properties['id']}",
				'time'                 => date_format( date_create( 'now', new DateTimeZone( 'Etc/UTC' ) ), 'Y-m-d H:i:s' ),
				'theme_id'             => ($type == 'theme') ? $properties['id'] : null,
				'workflow_revision_id' => ($type == 'workflow') ? (int) $properties['revision_id'] : null,
				'workflow_id'          => ($type == 'workflow') ? $properties['id'] : null,
		];
		if ( 'workflow' === $type ) {
			$properties = [
				'bundle'   => $properties['bundle'],
				'revision' => $properties['revision_id'],
				'workflow' => $properties['id'],
			];
		} elseif ( 'theme' === $type ) {
			$properties = [
				'theme' => $properties['id'],
				'name'  => $properties['name'],
			];
		}
		$json['properties'] = $properties;

		// I wanted to use $alphred->post, but it doesn't encode the query fields correctly.
		$c = curl_init( BASE_URL . '/ahoy/events' );
		curl_setopt( $c, CURLOPT_POST, true );
		curl_setopt( $c, CURLOPT_POSTFIELDS, json_encode( $json ) );
		curl_setopt( $c, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ] );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_exec( $c );
		curl_close( $c );
	}

	private static function uuid() {
		$output = '';
		foreach ( [ 8, 4, 4, 4, 12 ] as $number ) :
			$output .= self::random( $number ) . '-';
		endforeach;
		return substr( $output, 0, strlen( $output ) - 1 );
	}

	private static function random( $length ) {
		$string = 'abcdef0123456789';
		$value = '';
		for ( $i = 0; $i < $length; $i++ ) :
			$value .= substr( $string, rand( 0, 15 ), 1 );
		endfor;
		return $value;
	}

	/**
	 * Abstracted Search method
	 *
	 * Searches for both workflows and themes depending on the input.
	 *
	 * @param  string $search    search term
	 * @param  string $key       the array sub-key to search through
	 * @param  string $type      which type to search (workflow/theme)
	 * @param  string $identifer name of identifier (bundle/slug)
	 * @return string            the text of the output
	 */
	function search( $search, $key, $type, $identifer ) {
		$items = call_user_func( [ $this, "download_{$type}_data" ] );
		$items = $this->alphred->filter(
			$items[ "{$type}s" ],
			$search,
			$key,
			[ 'match_type' => MATCH_SUBSTRING | MATCH_STARTSWITH | MATCH_ATOM ]
		);
		return $items;
	}
}
