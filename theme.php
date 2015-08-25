<?php

require_once( __DIR__ . '/Libraries/Alphred.phar' );
$me = 'Shawn Patrick Rice';
print_r( array_values( encode_themes( get_themes(), $me ) ) );

function get_themes() {
	// So, I wrote this dumb little objective-c program to read the theme plists (to convert the colors
	// from NSColor objects to rgba), but I suck at objective-c, so I have to execute the program, clean
	// the output to make it real json, and then turn it into a usable array.
	exec( '"' . __DIR__ . '/Resources/ReadThemes"', $themes );
	$themes = '{' . substr( implode( "\n", $themes ), 0, -1 ) . '}';
	$themes = str_replace( ';', '', $themes );
	$themes = str_replace( ",\n}", "\n}", $themes );
	return json_decode( $themes, true );
}

function encode_themes( $themes, $me ) {
	$to_encode = [ 'credits', 'name', 'resultSubtextFont', 'resultTextFont', 'searchFont', 'shortcutFont' ];
	$colors = [
		'background',
		'border',
		'resultSelectedBackgroundColor',
		'resultSelectedSubtextColor',
		'resultSelectedTextColor',
		'resultSubtextColor',
		'resultTextColor',
		'scrollbarColor',
		'searchBackgroundColor',
		'searchForegroundColor',
		'searchSelectionBackgroundColor',
		'searchSelectionForegroundColor',
		'separatorColor',
		'shortcutColor',
		'shortcutSelectedColor',
	];

	// This is messy.
	$candidates = [];
	foreach ( $themes as $key => $theme ) :
		if ( ! isset( $theme['credits'] ) || ( $me != $theme['credits'] ) ) {
			unset( $themes[ $key ] );
			continue;
		}
		unset( $themes[ $key ]['uid'] );
		ksort( $themes[ $key ] );
		$candidates[ $key ]['name'] = $theme['name'];
		foreach ( $colors as $k ) :
			$themes[ $key ][ $k ] = str_replace( "'", '', $themes[ $key ][ $k ] );
		endforeach;
		foreach ( $to_encode as $k ) :
			$themes[ $key ][ $k ] = rawurlencode( $themes[ $key ][ $k ] );
		endforeach;
		$candidates[$key]['uri'] = 'alfred://theme/' . create_theme_uri( $themes[ $key ] );
	endforeach;

	return $candidates;
}

function create_theme_uri( $input ) {
	// Ugly, but effective
	return implode( '&', array_map( function ( $v, $k ) {
								                  	return sprintf( "%s=%s", $k, $v );
								                  },
								                  $input,
								                  array_keys( $input )
								                )
	              );
}