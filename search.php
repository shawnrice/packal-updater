<?php

require_once( __DIR__ . '/includes.php' );

$alphred = '';
// $separator = '›';
$separator = '>';

function main( $argv ) {

	global $alphred, $base_url, $separator;

	$alphred = new Alphred;

	$query = isset( $argv[1] ) ? $argv[1] : '';
	$icon_suffix = 'light' == $alphred->theme_background() ? '-dark.png' : '-light.png';

	$endpoints = [
		'workflow' => PACKAL_BASE_API_URL . 'workflow?all',
		'theme' => PACKAL_BASE_API_URL . 'theme?all'
	];

	if ( false !== strpos( $query, $separator ) ) {
		// Break it into parts
		$parts = explode( $separator, $query );
		// Trim the spaces
		array_walk( $parts, create_function( '&$val', '$val = trim($val);' ) );
		foreach ( $parts as $key => $part ) :
			if ( empty( $part ) ) {
				unset( $parts[ $key ] );
			}
		endforeach;
		// I should redo this menu system
		$parts = array_values( $parts );
		print_r( $parts );
		$main  = $parts[0];
		$sub   = $parts[1];
		$which = $parts[0] . '-single';
	} else if ( false !== strpos( $query, ' ' ) ) {
		$which = substr( $query, 0, strpos( $query, ' ') );
		$query = substr( $query, strpos( $query, ' ') );
	} else {
		$which = $query;
		$query = '';
	}

	if ( 'cc' == $which ) {
		$alphred->add_result([
      'title' => 'Clear Packal Manifest Caches',
      'valid' => true,
      'arg'   => json_encode([ 'action' => 'clear-caches', 'workflow' => [] ]),
		]);
	}

	switch ($which) {
		case 'workflow':
			$workflows = $alphred->get( $endpoints[ $which ], 3600, true );
			render_workflows( $workflows, $query );
			break;
		case 'theme':
			$themes = $alphred->get( $endpoints[ $which ], 3600, true );
			render_themes( $themes, $query );
			break;
		case 'workflow-single':
			$workflows = json_decode( $alphred->get( $endpoints['workflow'], 3600, true ), true );
			$workflows = $workflows['workflows'];
			foreach ( $workflows as $workflow ) :
				if ( $sub == $workflow['name'] ) {
					break;
				}
			endforeach;

			if ( is_array( $workflow ) && $workflow['name'] == $sub ) {
				$alphred->add_result([
					'title'    => "{$workflow['name']} (v{$workflow['version']}) by {$workflow['author']}",
					'icon' 		 => $request = get( $workflow['icon'], 3600 )[1],
					'subtitle' => $workflow['description'],
					'valid'    => false,
				]);
				$alphred->add_result([
					'title'    => "Download workflow to `~/Downloads`",
					'icon' 		 => 'assets/images/icons/download' . $icon_suffix,
					'valid'    => true,
					'arg'			 => json_encode([
	                        'action' => 'download',
	                        'target' => $workflow['file'],
	                        'workflow' => $workflow,
                        ]),
				]);
				$alphred->add_result([
					'title'    => "Install workflow {$workflow['name']}",
					'icon' 		 => 'assets/images/icons/install' . $icon_suffix,
					'valid'    => true,
					'arg'			 => json_encode([
	                        'action' => 'install',
	                        'target' => $workflow['file'],
	                        'workflow' => $workflow,
                        ]),
				]);
				$alphred->add_result([
					'title'    => "View workflow page on Packal.org",
					'icon' 		 => 'assets/images/icons/packal' . $icon_suffix,
					'valid'    => true,
					'arg'			 => json_encode([
	                        'action' => 'open',
	                        'target' => $workflow['url'],
	                        'workflow' => $workflow,
                        ]),
				]);
				$alphred->add_result([
					'title'    => "View author page on Packal.org",
					'icon' 		 => 'assets/images/icons/user' . $icon_suffix,
					'valid'    => true,
					'arg'			 => json_encode([
	                        'action' => 'open',
	                        'target' => $workflow['author_url'],
	                        'workflow' => $workflow,
                        ]),
				]);
				if ( isset( $workflow['github'] ) && ! empty( $workflow['github'] ) ) {
					$alphred->add_result([
						'title'    => "Open Github Repo Page",
						'icon' 		 => 'assets/images/icons/github' . $icon_suffix,
						'valid'    => true,
						'arg'			 => json_encode([
	                        'action' => 'open',
	                        'target' => "https://github.com/{$workflow['github']}",
	                        'workflow' => $workflow,
                        ]),
					]);
				}
				$alphred->add_result([
					'title'    => "Report Workflow",
					'icon' 		 => 'assets/images/icons/report' . $icon_suffix,
					'valid'    => true,
					'arg'			 => json_encode([
	                        'action' => 'report',
	                        'workflow' => $workflow,
                        ]),
				]);

			}
			break;
		case '':
			$alphred->add_result([
			  'title' => 'Search Themes',
			  'autocomplete' => "{$separator} themes",
			  'icon' => 'assets/images/icons/search' . $icon_suffix,
			  'valid' => false,
      ]);
			$alphred->add_result([
			  'title' => 'Search Workflows',
			  'autocomplete' => " {$separator} workflow",
			  'icon' => 'assets/images/icons/search' . $icon_suffix,
			  'valid' => false,
      ]);
			break;
		default:

			break;
	}
	$alphred->to_xml();
}

function render_workflows( $workflows, $query ) {
	global $alphred;
	$workflows = json_decode( $workflows, true );
	$workflows = $workflows['workflows'];

	if ( ! empty( $query ) ) {
		$workflows = $alphred->filter(
		  $workflows,
		  $query,
		  'name',
		  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
		);
	}

	foreach ( $workflows as $workflow ) :
		$request = get( $workflow['icon'], 86400 );
		$alphred->add_result([
	    'icon' => $request[1],
	    'title' => "{$workflow['name']} (v{$workflow['version']})",
	    'subtitle' => substr( "{$workflow['description']}", 0, 120 )
	    				. ' (last updated: ' . $alphred->fuzzy_time_diff( strtotime( $workflow['updated'] ) ) . ')',
	    'autocomplete' => '› workflow › ' . $workflow['name'],
	    'valid' => false,
		]);
	endforeach;
}

function render_themes( $themes, $query ) {
	global $alphred;
	$icon_suffix = 'light' == $alphred->theme_background() ? '-dark.png' : '-light.png';
	$themes = json_decode( $themes, true );
	$themes = $themes['themes'];
	if ( ! empty( $query ) ) {
		$themes = $alphred->filter(
		  $themes,
		  $query,
		  'name',
		  //  && MATCH_ATOM && MATCH_INITIALS  &&
		  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
		);
	}
	foreach ( $themes as $theme ) :
		// I need to do something to get the filepath instead
		// $icon = md5( json_encode( $alphred->get( $workflow['icon'], 3600 ) ) );
		$subtitle = scrub( urldecode( $theme['description'] ) );
		if ( strlen( $subtitle ) > 120 ) {
			$subtitle = substr( $subtitle, 0, 120) . '...';
		} else {
			$subtitle = substr( $subtitle, 0, 120);
		}

		$alphred->add_result([
	    // 'icon' => $icon,
	    'title' => "{$theme['name']}",
	    'subtitle' => $subtitle,
	    'icon' => 'assets/images/icons/bullet' . $icon_suffix,
	    'autocomplete' => '› theme › ' . $theme['name'],
	    'valid' => false,
		]);

	endforeach;
}



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


main( $argv );