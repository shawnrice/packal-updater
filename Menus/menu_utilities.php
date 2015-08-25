<?php

/**
 * Utility Functions
 */

function scrub( $text ) {
	$characters = [
		'&nbsp;' => ' ',
		'&mdash;' => '—',
		'&bull;' => '•',
		'&#39;' => '\'',
		'&hellip;' => '…',
	];
	foreach ( $characters as $c => $r ) :
		$text = str_replace( $c, $r, $text);
	endforeach;
	$text = cleaner($text);
	if ( ':' == substr( $text, -1 ) ) {
		$text = substr( $text, 0, strlen($text) -1 ) . '.';
	}
	return $text;
}

// I found this on stackoverflow. I love that place
function containsTLD( $string ) {
  preg_match(
    "/(AC($|\/)|\.AD($|\/)|\.AE($|\/)|\.AERO($|\/)|\.AF($|\/)|\.AG($|\/)|\.AI($|\/)|\.AL($|\/)|\.AM($|\/)|\.AN($|\/)|\.AO($|\/)|\.AQ($|\/)|\.AR($|\/)|\.ARPA($|\/)|\.AS($|\/)|\.ASIA($|\/)|\.AT($|\/)|\.AU($|\/)|\.AW($|\/)|\.AX($|\/)|\.AZ($|\/)|\.BA($|\/)|\.BB($|\/)|\.BD($|\/)|\.BE($|\/)|\.BF($|\/)|\.BG($|\/)|\.BH($|\/)|\.BI($|\/)|\.BIZ($|\/)|\.BJ($|\/)|\.BM($|\/)|\.BN($|\/)|\.BO($|\/)|\.BR($|\/)|\.BS($|\/)|\.BT($|\/)|\.BV($|\/)|\.BW($|\/)|\.BY($|\/)|\.BZ($|\/)|\.CA($|\/)|\.CAT($|\/)|\.CC($|\/)|\.CD($|\/)|\.CF($|\/)|\.CG($|\/)|\.CH($|\/)|\.CI($|\/)|\.CK($|\/)|\.CL($|\/)|\.CM($|\/)|\.CN($|\/)|\.CO($|\/)|\.COM($|\/)|\.COOP($|\/)|\.CR($|\/)|\.CU($|\/)|\.CV($|\/)|\.CX($|\/)|\.CY($|\/)|\.CZ($|\/)|\.DE($|\/)|\.DJ($|\/)|\.DK($|\/)|\.DM($|\/)|\.DO($|\/)|\.DZ($|\/)|\.EC($|\/)|\.EDU($|\/)|\.EE($|\/)|\.EG($|\/)|\.ER($|\/)|\.ES($|\/)|\.ET($|\/)|\.EU($|\/)|\.FI($|\/)|\.FJ($|\/)|\.FK($|\/)|\.FM($|\/)|\.FO($|\/)|\.FR($|\/)|\.GA($|\/)|\.GB($|\/)|\.GD($|\/)|\.GE($|\/)|\.GF($|\/)|\.GG($|\/)|\.GH($|\/)|\.GI($|\/)|\.GL($|\/)|\.GM($|\/)|\.GN($|\/)|\.GOV($|\/)|\.GP($|\/)|\.GQ($|\/)|\.GR($|\/)|\.GS($|\/)|\.GT($|\/)|\.GU($|\/)|\.GW($|\/)|\.GY($|\/)|\.HK($|\/)|\.HM($|\/)|\.HN($|\/)|\.HR($|\/)|\.HT($|\/)|\.HU($|\/)|\.ID($|\/)|\.IE($|\/)|\.IL($|\/)|\.IM($|\/)|\.IN($|\/)|\.INFO($|\/)|\.INT($|\/)|\.IO($|\/)|\.IQ($|\/)|\.IR($|\/)|\.IS($|\/)|\.IT($|\/)|\.JE($|\/)|\.JM($|\/)|\.JO($|\/)|\.JOBS($|\/)|\.JP($|\/)|\.KE($|\/)|\.KG($|\/)|\.KH($|\/)|\.KI($|\/)|\.KM($|\/)|\.KN($|\/)|\.KP($|\/)|\.KR($|\/)|\.KW($|\/)|\.KY($|\/)|\.KZ($|\/)|\.LA($|\/)|\.LB($|\/)|\.LC($|\/)|\.LI($|\/)|\.LK($|\/)|\.LR($|\/)|\.LS($|\/)|\.LT($|\/)|\.LU($|\/)|\.LV($|\/)|\.LY($|\/)|\.MA($|\/)|\.MC($|\/)|\.MD($|\/)|\.ME($|\/)|\.MG($|\/)|\.MH($|\/)|\.MIL($|\/)|\.MK($|\/)|\.ML($|\/)|\.MM($|\/)|\.MN($|\/)|\.MO($|\/)|\.MOBI($|\/)|\.MP($|\/)|\.MQ($|\/)|\.MR($|\/)|\.MS($|\/)|\.MT($|\/)|\.MU($|\/)|\.MUSEUM($|\/)|\.MV($|\/)|\.MW($|\/)|\.MX($|\/)|\.MY($|\/)|\.MZ($|\/)|\.NA($|\/)|\.NAME($|\/)|\.NC($|\/)|\.NE($|\/)|\.NET($|\/)|\.NF($|\/)|\.NG($|\/)|\.NI($|\/)|\.NL($|\/)|\.NO($|\/)|\.NP($|\/)|\.NR($|\/)|\.NU($|\/)|\.NZ($|\/)|\.OM($|\/)|\.ORG($|\/)|\.PA($|\/)|\.PE($|\/)|\.PF($|\/)|\.PG($|\/)|\.PH($|\/)|\.PK($|\/)|\.PL($|\/)|\.PM($|\/)|\.PN($|\/)|\.PR($|\/)|\.PRO($|\/)|\.PS($|\/)|\.PT($|\/)|\.PW($|\/)|\.PY($|\/)|\.QA($|\/)|\.RE($|\/)|\.RO($|\/)|\.RS($|\/)|\.RU($|\/)|\.RW($|\/)|\.SA($|\/)|\.SB($|\/)|\.SC($|\/)|\.SD($|\/)|\.SE($|\/)|\.SG($|\/)|\.SH($|\/)|\.SI($|\/)|\.SJ($|\/)|\.SK($|\/)|\.SL($|\/)|\.SM($|\/)|\.SN($|\/)|\.SO($|\/)|\.SR($|\/)|\.ST($|\/)|\.SU($|\/)|\.SV($|\/)|\.SY($|\/)|\.SZ($|\/)|\.TC($|\/)|\.TD($|\/)|\.TEL($|\/)|\.TF($|\/)|\.TG($|\/)|\.TH($|\/)|\.TJ($|\/)|\.TK($|\/)|\.TL($|\/)|\.TM($|\/)|\.TN($|\/)|\.TO($|\/)|\.TP($|\/)|\.TR($|\/)|\.TRAVEL($|\/)|\.TT($|\/)|\.TV($|\/)|\.TW($|\/)|\.TZ($|\/)|\.UA($|\/)|\.UG($|\/)|\.UK($|\/)|\.US($|\/)|\.UY($|\/)|\.UZ($|\/)|\.VA($|\/)|\.VC($|\/)|\.VE($|\/)|\.VG($|\/)|\.VI($|\/)|\.VN($|\/)|\.VU($|\/)|\.WF($|\/)|\.WS($|\/)|\.XN--0ZWM56D($|\/)|\.XN--11B5BS3A9AJ6G($|\/)|\.XN--80AKHBYKNJ4F($|\/)|\.XN--9T4B11YI5A($|\/)|\.XN--DEBA0AD($|\/)|\.XN--G6W251D($|\/)|\.XN--HGBK6AJ7F53BBA($|\/)|\.XN--HLCJ6AYA9ESC7A($|\/)|\.XN--JXALPDLP($|\/)|\.XN--KGBECHTV($|\/)|\.XN--ZCKZAH($|\/)|\.YE($|\/)|\.YT($|\/)|\.YU($|\/)|\.ZA($|\/)|\.ZM($|\/)|\.ZW)/i",
    $string,
    $M );
  return ( count( $M ) > 0 ) ? true : false;
}

function cleaner( $url ) {
  $U = explode( ' ', $url );

  $W = [];
  foreach ( $U as $k => $u ) :
    if ( stristr( $u, '.' ) ) { //only preg_match if there is a dot
      if ( containsTLD( $u ) === true) {
	      unset( $U[ $k ] );
	      return cleaner( implode( ' ', $U ) );
	    }
    }
  endforeach;
  return implode( ' ', $U );
}


/**
 * Utilities for Theme Menus
 */

function get_themes() {
	// So, I wrote this dumb little objective-c program to read the theme plists (to convert the colors
	// from NSColor objects to rgba), but I suck at objective-c, so I have to execute the program, clean
	// the output to make it real json, and then turn it into a usable array.
	exec( '"' . __DIR__ . '/../Resources/ReadThemes"', $themes );
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