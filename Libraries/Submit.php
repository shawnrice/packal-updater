<?php

class Submit {

	public function __construct( $type, $params ) {
		$this->alphred = new Alphred;
		$types = [ 'workflow', 'theme', 'report' ];
		if ( ! in_array( $type, $types ) ) {
			die( "$type is not a valid type. Valid types are: " . implode( ', ', $types ) );
		}
		$this->params = $params;
		// Standard setup
		$this->ch = curl_init();
		curl_setopt( $this->ch, CURLOPT_URL, BASE_API_URL . 'alfred2/' . $type . '/submit' );
		curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->ch, CURLOPT_POST, true );
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
		return $this->alphred->config_read( 'username' );
	}

	private function get_password() {
		return $this->alphred->get_password( 'packal.org' );
	}

	public function execute() {
		// I should add some error handling in this
		$result = curl_exec( $this->ch );
		curl_close( $this->ch );
		return $result;
	}

	private function build_data( ) {
		if ( isset( $this->postData['workflow_revision'] ) ) {
			$this->postData['workflow_revision[version]'] = $this->postData['workflow_revision']['version'];
			unset( $this->postData['workflow_revision'] );
			// Pass to a weird function that I did not write but that seems to work
			curl_custom_postfields( $this->ch, $this->postData, [ $this->params['file'] ] );
		} else {
			curl_setopt( $this->ch, CURLOPT_POSTFIELDS,  http_build_query( $this->postData ) );
		}
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

// FROM PHP.NET, this is kind of a hacky way to get it to work, but it works!
/**
 * For safe multipart POST request for PHP5.3 ~ PHP 5.4.
 *
 * @param resource $ch cURL resource
 * @param array $assoc "name => value"
 * @param array $files "name => path"
 * @return bool
 */
function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {

    // invalid characters for "name" and "filename"
    static $disallow = array("\0", "\"", "\r", "\n");

    // initialize body
    $body = array();

    // build normal parameters
    foreach ($assoc as $k => $v) {
        $k = str_replace($disallow, "_", $k);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"",
            "",
            filter_var($v),
        ));
    }

    // build file parameters
    foreach ($files as $k => $v) {
        switch (true) {
            case false === $v = realpath(filter_var($v)):
            case !is_file($v):
            case !is_readable($v):
                continue; // or return false, throw new InvalidArgumentException
        }
        $data = file_get_contents($v);
        $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));

        // THIS IS A TERRIBLE HACK
        $k = 'workflow_revision[file]';
        list($k, $v) = str_replace($disallow, "_", array($k, $v));
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
            "Content-Type: application/octet-stream",
            "",
            $data,
        ));
    }

    // generate safe boundary
    do {
        $boundary = "---------------------" . md5(mt_rand() . microtime());
    } while (preg_grep("/{$boundary}/", $body));

    // add boundary for each parameters
    array_walk($body, function (&$part) use ($boundary) {
        $part = "--{$boundary}\r\n{$part}";
    });

    // add final boundary
    $body[] = "--{$boundary}--";
    $body[] = "";

    // set options
    return curl_setopt_array($ch, array(
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => implode("\r\n", $body),
        CURLOPT_HTTPHEADER => array(
            "Expect: 100-continue",
            "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
        ),
    ));

}