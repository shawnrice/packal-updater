<?php

require_once( __DIR__ . '/includes.php' );

class Submit {

	public function __construct( $type, $params ) {
		$types = [ 'workflow', 'theme', 'report' ];
		if ( ! in_array( $type, $types ) ) {
			die( "$type is not a valid type. Valid types are: " . implode( ', ', $types ) );
		}
		$this->params = $params;
		// Standard setup
		$this->ch = curl_init();
		// PACKAL_BASE_API_URL
		curl_setopt( $this->ch, CURLOPT_URL, 'http://localhost:3000/' . $type . '/submit' );
		curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->ch, CURLOPT_POST, true );
		curl_setopt( $this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' ) );

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
		$params['file'] = '@' . realpath( $params['file'] );
		$this->postData = [ 'workflow_revision' => $params, 'alfred_version' => 2 ];
		return true;
	}

	public function theme( $params ) {
		if ( ! $this->ensure_keys([ 'name', 'description', 'tags', 'uri' ], $params ) ) {
			return false;
		}
		$params['alfred2'] = true;
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
			'password' => $this->get_password()
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
		$result = curl_exec($this->ch);
		curl_close($this->ch);
		return $result;
	}

	private function build_data( ) {
		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, json_encode( $this->postData ) );
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

// $workflow = submit_workflow([
// 	'file' => '/Users/Sven/Desktop/Packal/data/pro.elms.paul.speedtest/speedtest.alfredworkflow'
// ]);
// print_r( json_decode( $workflow ), true );

// $report = submit_report([ 'workflow_revision_id' => 3,
//               	'report_type' => 'Malicious Code',
//               	'message' => 'This is not good.'
//               ]);

// print_r( json_decode( $report ), true );

// $theme = submit_theme([
// 	'name' => 'Glass',
// 	'description' => 'A beautiful, nearly transparent theme.',
// 	'tags' => implode(',', [ 'transparent', 'minimal', 'glass' ]),
// 	'uri' => 'alfred://theme/background=rgba(0,0,0,0.00)&border=rgba(169,189,222,0.30)&cornerRoundness=1&credits=Shawn%20Patrick%20Rice&imageStyle=4&name=Glass&resultPaddingSize=2&resultSelectedBackgroundColor=rgba(94,151,204,0.25)&resultSelectedSubtextColor=rgba(255,255,255,0.75)&resultSelectedTextColor=rgba(250,253,255,1.00)&resultSubtextColor=rgba(255,255,255,0.75)&resultSubtextFont=Helvetica&resultSubtextFontSize=0&resultTextColor=rgba(255,255,255,1.00)&resultTextFont=Helvetica&resultTextFontSize=1&scrollbarColor=rgba(255,255,255,0.50)&searchBackgroundColor=rgba(0,0,0,0.00)&searchFont=Helvetica&searchFontSize=2&searchForegroundColor=rgba(255,255,255,1.00)&searchPaddingSize=1&searchSelectionBackgroundColor=rgba(184,201,117,1.00)&searchSelectionForegroundColor=rgba(255,255,255,1.00)&separatorColor=rgba(117,120,112,0.20)&shortcutColor=rgba(255,255,255,0.50)&shortcutFont=Helvetica&shortcutFontSize=0&shortcutSelectedColor=rgba(255,255,255,0.50)&widthSize=4'
//          ]);

// print_r( json_decode( $theme ), true );