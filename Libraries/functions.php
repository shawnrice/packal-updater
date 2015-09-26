<?php

// Placeholder used while refactoring...

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
