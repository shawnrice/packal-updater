<?php

require_once( __DIR__ . '/includes.php' );
require_once( __DIR__ . '/vendor/autoload.php' );

class Submit {

	public function __construct( $type, $params ) {
		$types = [ 'workflow', 'theme', 'report' ];
		if ( ! in_array( $type, $types ) ) {
			die( "$type is not a valid type. Valid types are: " . implode( ', ', $types ) );
		}
		$this->params = $params;
		// Standard setup
		$this->ch = curl_init();

		curl_setopt( $this->ch, CURLOPT_URL, PACKAL_BASE_API_URL . 'alfred2/' . $type . '/submit' );
		curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->ch, CURLOPT_POST, true );
		curl_setopt( $this->ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data' ) );
		curl_setopt( $this->ch, CURLOPT_SAFE_UPLOAD, true );


		// Call the submit method
		if ( ! call_user_func_array( [ $this, $type ], [ $params ] ) ) {
			die( 'Could not call user method' );
			// This should be an exception
			return false;
		}

		// Add in username and password
		$this->postData = array_merge( $this->postData, $this->standard() );
		$this->build_data();
	}

	public function workflow( $params ) {
		if ( ! $this->ensure_keys([ 'file', 'version' ], $params ) ) {
			return false;
		}
		$alphred = new Alphred;
		$params['file'] = getCurlValue( $params['file'], 'application/zip', 'workflow.alfredworkflow' ) ;
		$alphred->console( print_r( $params, true ), 4 );
		$this->postData = [ 'workflow_revision' => $params ];
		return true;
	}

	public function theme( $params ) {
		if ( ! $this->ensure_keys([ 'name', 'description', 'tags', 'uri' ], $params ) ) {
			return false;
		}
		$params['tags'] = implode( ',', $params['tags'] );
		$this->postData = [ 'theme' => $params ];
		return true;
	}

	public function report( $params ) {
		if ( ! $this->ensure_keys([ 'workflow_revision_id', 'report_type', 'message' ], $params ) ) {
			return false;
		}
		$this->postData = [ 'report' => $params ];
		return true;
	}

	private function standard() {
		return [
			'username' => $this->get_username(),
			'password' => $this->get_password(),
			'alfred_version' => 'alfred2',
		];
	}

	private function get_username() {
		// This is obviously temporary
		return 'Shawn Patrick Rice';
	}

	private function get_password() {
		// This is obviously temporary
		return '12345678';
	}

	public function execute() {
		// I should add some error handling in this
		print_r( var_dump($this->ch), true );
		$result = curl_exec( $this->ch );
		curl_close( $this->ch );
		return $result;
	}

	private function build_data( ) {
		$alphred = new Alphred;

		curl_setopt( $this->ch, CURLOPT_POSTFIELDS,  http_build_query($this->postData) );
	}

	private function ensure_keys( $keys, $params ) {
		foreach ( $keys as $key ) :
			if ( ! isset( $params[ $key ] ) ) {
				return false;
			}
		endforeach;
		return true;
	}
} // End Submit class

function submit_theme( $params ) {
	$submission = new Submit( 'theme', $params );
	return $submission->execute();
}

function submit_workflow( $params ) {
	$submission = new Submit( 'workflow', $params );
	return $submission->execute();
}

function submit_report( $params ) {
	$submission = new Submit( 'report', $params );
	return $submission->execute();
}


// Helper function courtesy of https://github.com/guzzle/guzzle/blob/3a0787217e6c0246b457e637ddd33332efea1d2a/src/Guzzle/Http/Message/PostFile.php#L90
function getCurlValue($filename, $contentType, $postname)
{
    // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
    // See: https://wiki.php.net/rfc/curl-file-upload
    // if (function_exists('curl_file_create')) {
    //     return curl_file_create($filename);
    // }

    // Use the old style if using an older version of PHP
    $value = $filename; //;filename=" . $postname;
    // if ($contentType) {
    //     $value .= ';type=' . $contentType;
    // }

    return $value;
}