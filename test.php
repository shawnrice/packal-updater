<?php

require_once(__DIR__ . '/includes.php' );

class Menu {

	private static $separator = ':';

	public static function render( $menu, $wrapper, $query = '' ) {
		if ( empty( $query ) ) {
			foreach( $menu as $key => $val ) :
				if ( is_array( $val ) ) {
					$wrapper->add_result([
						'title' => $key,
						'autocomplete' => $key,
						'valid' => false,
					]);
				} else {
					$wrapper->add_result([
						'title' => $value,
						'autocomplete' => $value,
						'valid' => true,
					]);
				}
			endforeach;
		} else {
			$parts = explode( self::$separator, $query );
			array_walk( $parts, create_function( '&$val', '$val = strtolower(trim($val));' ) );
			if ( isset( $menu[ $parts[0] ] ) ) {

			} else {
				$parts = $wrapper->filter(
				  array_keys( $menu ),
				  $query,
				  false,
				  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
				);
				print_r( $menu );
			}
			// print_r( $parts );
		}



	}

} // class Menu

$alphred = new Alphred;
// So, I think that what we're doing is that everything that is an autocomplete
// but not valid is an array key, and when we finally get to a value that isn't
// an array, then it will be either a "render" function or a submit function. If
// it's prefixed with 'render', then we call that function with the rest of the
// query to finish populating the menu. Otherwise, we place it as the argument
// and mark it valid.
$menu = [
	'search' => [
		'_properties' => [
			'valid' => false,
		],
		'workflows' => [
			'_properties' => [
				'valid' => true,
				'function' => 'render:search_workflows',
			],
		],
		'themes' => [
			'_properties' => [
				'valid' => true,
				'function' => 'render:search_themes',
			],
		],
	],
	'submit' => [
		'_properties' => [
			'valid' => false,
		],
		'workflows' => [
			'_properties' => [
				'function' => 'render:submit_workflows',
				'valid' => true,
			],
		],
		'themes' => [
			'_properties' => [
				'function' => 'render:submit_themes',
				'valid' => true,
			],
		],
	],
	'clear caches' => [
		'_properties' => [
			'valid' => true,
			'function' => 'clear-caches',
		],
	],
	'configure' => [
		'_properties' => [
			'valid' => false,
		],
	],
];
$separator = ':';
$query = 'submit';

if ( isset( $menu[ $query ] ) ) {
	render( $menu[ $query ], '', true );
}

function render( $menu, $query = '', $sub ) {
	if ( $sub ) {
		if ( isset( $menu['_properties'] ) ) {
			unset( $menu['_properties'] );
		}
	}
	foreach( $menu as $key => $value ) :
		echo $key . "\r\n";
		if ( $value['_properties']['valid'] ) {

		}
	endforeach;
}

// // Menu::render( $menu, $alphred, $query );
// // $alphred->to_xml();
// //
// $separator = ':';
// $t = testing_t( $menu, $query );
// if ( isset( $t[0] ) ) {
// 	$arg = $t[1];
// 	$function = $t[0];
// }
// if ( isset( $t['items'] ) ) {
// 	foreach( $t['items'] as $item ) :
// 		$alphred->add_result([
// 			'title' => $item,
// 			'autocomplete' => "p{$separator}{$item}{$separator}",
// 			'valid' => false,
// 			'uid' => "{$t['breadcrumb']}{$item}",
// 		]);
// 	endforeach;
// 	$alphred->to_xml();
// 	exit;
// }


// $function = substr( $function, strpos( $function, ':' ) + 1  );
// if ( function_exists( $function ) ) {
// 	call_user_func( $function );
// }

// function testing_t( $menu, $query, $breadcrumb = 'packal-', $levels = false) {
// 	global $alphred;
// 	$items = explode( ':', $query );
// 	$item = array_shift( $items );
// 	if ( isset( $menu[ $item ] ) ) {

// 		$breadcrumb .= "{$item}-";
// 		return testing_t( $menu[ $item ], implode( ':', $items ), $breadcrumb, $levels );
// 	} else {
// 		$options = $alphred->filter(
// 		  array_keys( $menu ),
// 		  $query,
// 		  false,
// 		  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
// 		);

// 		if ( 1 == count( $options ) ) {
// 			$breadcrumb .= "{$options[0]}";
// 			return [ $menu[$options[0]], $breadcrumb ];
// 		} else {
// 			return [ 'items' => $options, 'breadcrumb' => $breadcrumb ];
// 		}
// 		print_r( $items );
// 		print_r( array_search( $options[0], $items ) );
// 		return $options;
// 	}
// }

// function search_workflows() {
// 	print "Hello! This worked.\r\n";
// }

// // print_r( recurse_array( $menu ) );

// // print_r( parse_section( $query, '' ) );

// function recurse_array( $array, $query = '', $breadcrumb = false ) {
// 	$separator = ':';

// 	if ( false === strpos( $query, $separator ) ) {
// 		// This means that there is no necessary
// 	} else {

// 	}
// 	return $array;
// }


// function nest_array( $array, $values ) {
//   if ( empty( $array ) ) {
// 	  return $values;
//   }
//   return [ array_shift( $array ) => nest_array( $array, $values ) ];
// }

// function parse_section( $name, $values ) {
// 	if ( false !== strpos( $name, ':' ) ) {
// 		$pieces = explode( ':', $name );
// 		$pieces = array_filter( $pieces, 'trim' );
// 	} else {
// 		return [ $name => $values ];
// 	}
// 	return nest_array( $pieces, $values );
// }
















// // require_once( __DIR__ . '/Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php' );




// // use CFPropertyList\CFPropertyList as CFPropertyList;

// // class Plist {

// // 	public function __construct( $old, $new ) {

// // 		$old = new CFPropertyList( $old, CFPropertyList::FORMAT_XML);

// // 		$this->old_values = [];

// // 		foreach( $old->getValue(true) as $key => $value ) :
// // 			if ( $value instanceof \Iterator ) {
// // 				$this->recurse_plist( $value );
// // 			}
// // 		endforeach;
// // 		print_r( $this->old_values );
// // 		// print_r( $old->toXML(true) );
// // 	}


// // 	private function recurse_plist( $object, $values = false ) {

// // 		// I got tired of writing this string everywhere, so I just did this instead.
// // 		$vp = 'value:protected';

// // 		foreach( $object->getValue( true ) as $key => $value ) :
// // 				// if ( 'keyword' == $key && is_string( $value->getValue( $vp ) ) ) {
// // 				// 	// This is where we set a new value
// // 				// 	$value->setValue( $value->getValue( $vp ) . 's' );
// // 				// }
// // 				if ( isset( $value->getValue( $vp )['type'] ) && is_string( $value->getValue( $vp )['type']->getValue( $vp ) ) ) {
// // 					$uid = $value->getValue( $vp )['uid']->getValue( $vp );
// // 					switch ( $value->getValue( $vp )['type']->getValue( $vp ) ):
// // 						case 'alfred.workflow.input.scriptfilter' :
// // 						case 'alfred.workflow.input.keyword' :
// // 						case 'alfred.workflow.input.filefilter' :
// // 							$this->old_values[ $uid ]['keyword'] =
// // 								$value->getValue( $vp )['config']->getValue( $vp )['keyword']->getValue( $vp );
// // 							break;
// // 						case 'alfred.workflow.trigger.hotkey' :
// // 							$this->old_values[ $uid ]['hotkey'] =
// // 								$value->getValue( $vp )['config']->getValue( $vp )['hotkey']->getValue( $vp );
// // 							$this->old_values[ $uid ]['hotmod'] =
// // 								$value->getValue( $vp )['config']->getValue( $vp )['hotmod']->getValue( $vp );
// // 							$this->old_values[ $uid ]['hotstring'] =
// // 								$value->getValue( $vp )['config']->getValue( $vp )['hotstring']->getValue( $vp );
// // 					endswitch;
// // 			if ( $value instanceof \Iterator ) {
// // 				$this->recurse_plist( $value );
// // 			}
// // 		}
// // 		endforeach;
// // 	}
// // }

// // $test = new Plist( __DIR__ . "/assets/plists/st.old.plist", false );
// // $test = new Plist( __DIR__ . "/assets/plists/info.old.plist", false );