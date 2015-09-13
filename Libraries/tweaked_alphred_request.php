<?php
// These two functions recreate the Alphred wrapper's request, but I do this,
// so that I can get the cache file path out of it in order to get the damn
// icons to show. That's also why I need to use the damn globals.
function get( $url, $options = false, $cache_ttl = 600, $cache_bin = true ) {
	global $cache_key;
	$request = create_request( $url, $options, $cache_ttl, $cache_bin, 'get' );
	$cache_key = $request->get_cache_file();
	return [ $request->execute(), $cache_key ];
}
function create_request( $url, $options, $cache_ttl, $cache_bin, $type ) {

	if ( $cache_ttl > 0 ) {
		// Create an object with caching on
		$request = new Alphred\Request( $url, [ 'cache' => true,
		                               					'cache_ttl' => $cache_ttl,
		                               					'cache_bin' => $cache_bin ] );
	} else {
		// Create an object with caching off
		$request = new Alphred\Request( $url, [ 'cache' => false ] );
	}
	// Set it to `POST` if that's what they want
	if ( 'post' == $type ) {
		$request->use_post();
	}
	// If there are options, then go through them and set everything
	if ( $options ) {
		if ( isset( $options['params'] ) ) {
			if ( ! is_array( $options['params'] ) ) {
				throw new Alphred\Exception( 'Parameters must be passed as an array', 4 );
			}
			// Add the parameters
			$request->add_parameters( $options['params'] );
		}
		// For basic http authentication
		if ( isset( $options['auth'] ) ) {
			// Make sure that there are two options in the auth array
			if ( ! is_array( $options['auth'] ) || ( 2 !== count( $options['auth'] ) ) ) {
				throw new Alphred\Exception( 'You need two arguments in the auth array.', 4 );
			}
			// Set the options
			$request->set_auth( $options['auth'][0], $options['auth'][1] );
		}
		// If we need a user agent
		if ( isset( $options['user_agent'] ) ) {
			// Make sure that the user agent is a string
			if ( ! is_string( $options['user_agent'] ) ) {
				// It's not, so throw an exception
				throw new Alphred\Exception( 'The user agent must be a string', 4 );
			}
			// Set the user agent
			$request->set_user_agent( $options['user_agent'] );
		}
		// If we need to add headers
		if ( isset( $options['headers'] ) ) {
			if ( ! is_array( $options['headers'] ) ) {
				throw new Alphred\Exception( 'Headers must be passed as an array', 4 );
			} else {
				$request->set_headers( $options['headers'] );
			}
		}
	}
	return $request;
}